<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login('/user/carrinho.php');

$user_id = $_SESSION['user_id'];

/* ── Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    $cart_id = get_or_create_cart($pdo, $user_id);

    if ($action === 'update') {
        $item_id  = (int)$_POST['item_id'];
        $quantity = max(1, (int)$_POST['quantity']);
        $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND cart_id=?")->execute([$quantity,$item_id,$cart_id]);
    }

    if ($action === 'remove') {
        $item_id = (int)$_POST['item_id'];
        $pdo->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?")->execute([$item_id,$cart_id]);
    }

    if ($action === 'clear') {
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cart_id]);
    }

    if ($action === 'checkout') {
        $stmt = $pdo->prepare(
            "SELECT ci.*, p.name, p.price, p.stock
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ? AND p.active = 1"
        );
        $stmt->execute([$cart_id]);
        $items = $stmt->fetchAll();

        if (empty($items)) {
            flash('error', 'Seu carrinho está vazio.');
        } else {
            $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
            $pdo->beginTransaction();
            try {
                $ord = $pdo->prepare("INSERT INTO orders (user_id,total) VALUES (?,?)");
                $ord->execute([$user_id, $total]);
                $oid = $pdo->lastInsertId();
                $oi  = $pdo->prepare("INSERT INTO order_items (order_id,product_id,product_name,quantity,price) VALUES (?,?,?,?,?)");
                foreach ($items as $item) {
                    $oi->execute([$oid,$item['product_id'],$item['name'],$item['quantity'],$item['price']]);
                    // Reduce stock
                    $pdo->prepare("UPDATE products SET stock=GREATEST(0,stock-?) WHERE id=?")->execute([$item['quantity'],$item['product_id']]);
                }
                $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cart_id]);
                $pdo->commit();
                flash('success', 'Pedido #' . $oid . ' realizado com sucesso!');
                redirect('/user/pedidos.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Erro ao finalizar pedido. Tente novamente.');
            }
        }
    }

    redirect('/user/carrinho.php');
}

/* ── Load cart ── */
$cart_id = get_or_create_cart($pdo, $user_id);
$stmt = $pdo->prepare(
    "SELECT ci.id as item_id, ci.quantity,
            p.id as product_id, p.name, p.price, p.stock, p.active,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id=p.id AND pi.is_main=1 LIMIT 1) AS img
     FROM cart_items ci
     JOIN products p ON p.id = ci.product_id
     WHERE ci.cart_id = ?
     ORDER BY ci.id"
);
$stmt->execute([$cart_id]);
$items = $stmt->fetchAll();
$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$count = cart_count();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Carrinho | Iron &amp; Stone</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        .cart-layout{display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start}
        .cart-item{display:flex;gap:16px;padding:20px 0;border-bottom:1px solid var(--borda);align-items:center}
        .cart-item:last-child{border-bottom:none}
        .cart-img{width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--borda);flex-shrink:0}
        .cart-img-ph{width:80px;height:80px;border-radius:8px;background:var(--fundo-secao);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0}
        .cart-info{flex:1}
        .cart-info h3{font-size:15px;margin-bottom:4px}
        .cart-info .price{font-size:16px;font-weight:900;color:var(--primaria);margin-top:6px}
        .qty-wrap{display:flex;align-items:center;gap:8px}
        .qty-btn{width:28px;height:28px;border:2px solid var(--borda);border-radius:6px;background:#fff;cursor:pointer;font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;transition:border-color .2s}
        .qty-btn:hover{border-color:var(--destaque)}
        .qty-val{width:42px;text-align:center;border:2px solid var(--borda);border-radius:6px;padding:4px;font-size:14px;font-weight:700;font-family:inherit}
        .qty-val:focus{outline:none;border-color:var(--destaque)}
        .btn-remove{background:none;border:none;color:#ccc;cursor:pointer;font-size:18px;transition:color .2s;padding:4px}
        .btn-remove:hover{color:var(--destaque-hover)}
        .summary-card{background:#fff;border-radius:12px;padding:24px;border:1px solid var(--borda);position:sticky;top:100px}
        .summary-card h2{font-size:17px;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--borda)}
        .summary-row{display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px}
        .summary-row.total{font-size:18px;font-weight:900;border-top:2px solid var(--primaria);padding-top:12px;margin-top:12px}
        .btn-checkout{width:100%;padding:16px;background:var(--destaque);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:background .2s;margin-top:16px}
        .btn-checkout:hover{background:var(--destaque-hover)}
        .btn-checkout:disabled{background:#ccc;cursor:not-allowed}
        .cart-empty{text-align:center;padding:80px 20px}
        .cart-empty h2{margin-bottom:12px}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:14px}
        .alert-success{background:#D5F5E3;border:1px solid #A9DFBF;color:#1E8449}
        .alert-danger{background:#FDECEA;border:1px solid #F5B7B1;color:#C0392B}
        @media(max-width:768px){.cart-layout{grid-template-columns:1fr}.summary-card{position:static}}
    </style>
</head>
<body>
<header class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="/index.html">
                <img src="/logo_iron_stone.png" alt="Logo" class="logo-img">
                <span class="logo-text">Iron & Stone</span>
            </a>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="/index.html">Início</a></li>
                <li><a href="/quem-somos.php">Quem Somos</a></li>
                <li><a href="/produtos.php">Produtos</a></li>
                <li><a href="/servicos.html">Serviços</a></li>
                <li><a href="/fale-conosco.php">Fale Conosco</a></li>
                <li><a href="/user/pedidos.php">Pedidos</a></li>
                <li><a href="/auth/logout.php" class="btn-nav">Sair</a></li>
            </ul>
        </nav>
    </div>
</header>

<section class="section-padding page-content">
    <div class="container">
        <h2 class="section-title">Meu Carrinho</h2>

        <?php show_flash('success'); show_flash('error'); ?>

        <?php if(empty($items)): ?>
        <div class="cart-empty">
            <h2>Seu carrinho está vazio</h2>
            <p style="color:var(--texto-claro);margin-bottom:28px">Adicione produtos para continuar.</p>
            <a href="/produtos.php" class="btn-primary">Ver Produtos</a>
        </div>
        <?php else: ?>
        <div class="cart-layout">
            <!-- Items -->
            <div>
                <div style="background:#fff;border-radius:12px;padding:24px;border:1px solid var(--borda)">
                    <?php foreach($items as $item): ?>
                    <div class="cart-item">
                        <?php if($item['img']): ?>
                            <img src="<?= e($item['img']) ?>" class="cart-img" alt="">
                        <?php else: ?>
                            <div class="cart-img-ph"></div>
                        <?php endif; ?>
                        <div class="cart-info">
                            <h3><?= e($item['name']) ?></h3>
                            <div class="price"><?= format_price($item['price']) ?> <small style="font-weight:400;font-size:13px">/ un.</small></div>
                            <div class="qty-wrap" style="margin-top:10px">
                                <form method="post" style="display:flex;align-items:center;gap:8px">
                                    <?php csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                    <button type="button" class="qty-btn" onclick="adjustQty(this,-1)">−</button>
                                    <input type="number" name="quantity" class="qty-val" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock']>0?$item['stock']:999 ?>" onchange="this.form.submit()">
                                    <button type="button" class="qty-btn" onclick="adjustQty(this,1)">+</button>
                                </form>
                                <span style="font-size:13px;color:var(--texto-claro);margin-left:8px">
                                    Subtotal: <strong><?= format_price($item['price']*$item['quantity']) ?></strong>
                                </span>
                            </div>
                        </div>
                        <form method="post">
                            <?php csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                            <button type="submit" class="btn-remove" title="Remover">✕</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:14px">
                    <form method="post" onsubmit="return confirm('Limpar todo o carrinho?')">
                        <?php csrf_field() ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" style="background:none;border:none;color:var(--texto-claro);cursor:pointer;font-size:13px;font-family:inherit">Esvaziar carrinho</button>
                    </form>
                </div>
            </div>

            <!-- Summary -->
            <div class="summary-card">
                <h2>Resumo do Pedido</h2>
                <div class="summary-row"><span><?= count($items) ?> item(s)</span><span><?= format_price($total) ?></span></div>
                <div class="summary-row"><span>Frete</span><span style="color:var(--texto-claro)">A combinar</span></div>
                <div class="summary-row total"><span>Total</span><span><?= format_price($total) ?></span></div>
                <form method="post">
                    <?php csrf_field() ?>
                    <input type="hidden" name="action" value="checkout">
                    <button type="submit" class="btn-checkout" onclick="return confirm('Confirmar pedido de <?= e(format_price($total)) ?>?')">
                        Finalizar Pedido
                    </button>
                </form>
                <a href="/produtos.php" style="display:block;text-align:center;margin-top:12px;font-size:13px;color:var(--texto-claro);text-decoration:none">← Continuar comprando</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<footer class="footer"><p>&copy; 2026 Iron &amp; Stone. Todos os direitos reservados.</p></footer>

<script>
function adjustQty(btn, delta) {
    const form = btn.closest('form');
    const input = form.querySelector('input[name="quantity"]');
    const newVal = Math.max(1, parseInt(input.value) + delta);
    input.value = newVal;
    form.submit();
}
</script>
</body>
</html>
