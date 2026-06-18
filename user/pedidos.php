<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login('/user/pedidos.php');

$user_id  = $_SESSION['user_id'];
$view_id  = (int)($_GET['id'] ?? 0);

$orders = $pdo->prepare(
    "SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$orders->execute([$user_id]);
$orders = $orders->fetchAll();

$view_order = null;
$view_items = [];
if ($view_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $stmt->execute([$view_id, $user_id]);
    $view_order = $stmt->fetch();
    if ($view_order) {
        $stmt2 = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
        $stmt2->execute([$view_id]);
        $view_items = $stmt2->fetchAll();
    }
}

$status_info = [
    'pending'    => ['Aguardando',  '#F39C12'],
    'processing' => ['Processando', '#3498DB'],
    'shipped'    => ['Enviado',     '#3498DB'],
    'delivered'  => ['Entregue',    '#27AE60'],
    'cancelled'  => ['Cancelado',   '#E74C3C'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos | Iron &amp; Stone</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        .order-card{background:#fff;border-radius:10px;border:1px solid var(--borda);padding:20px;margin-bottom:16px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;cursor:pointer;transition:border-color .2s,box-shadow .2s;text-decoration:none;color:inherit}
        .order-card:hover{border-color:var(--destaque);box-shadow:0 4px 16px rgba(0,0,0,.08)}
        .order-card.active{border-color:var(--destaque);background:#fffbf7}
        .o-id{font-size:18px;font-weight:900;color:var(--primaria)}
        .o-date{font-size:13px;color:var(--texto-claro)}
        .o-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;color:#fff}
        .detail-box{background:#fff;border-radius:12px;border:1px solid var(--borda);padding:24px;margin-top:4px}
        .detail-item{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--borda)}
        .detail-item:last-child{border-bottom:none}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:14px}
        .alert-success{background:#D5F5E3;border:1px solid #A9DFBF;color:#1E8449}
        .alert-danger{background:#FDECEA;border:1px solid #F5B7B1;color:#C0392B}
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
                <li><a href="/produtos.php">Produtos</a></li>
                <li><a href="/user/carrinho.php">🛒 Carrinho</a></li>
                <li><a href="/user/perfil.php">Perfil</a></li>
                <li><a href="/auth/logout.php" class="btn-nav">Sair</a></li>
            </ul>
        </nav>
    </div>
</header>

<section class="section-padding page-content">
    <div class="container">
        <h2 class="section-title">Meus Pedidos</h2>

        <?php show_flash('success'); show_flash('error'); ?>

        <?php if(empty($orders)): ?>
        <div style="text-align:center;padding:80px 20px">
            <h3>Nenhum pedido realizado ainda</h3>
            <p style="color:var(--texto-claro);margin-bottom:28px">Explore nossos produtos e faça seu primeiro pedido.</p>
            <a href="/produtos.php" class="btn-primary">Ver Produtos</a>
        </div>
        <?php else: ?>

        <?php foreach($orders as $o):
            [$lbl,$cor] = $status_info[$o['status']] ?? ['—','#999'];
        ?>
        <a href="?id=<?= $o['id'] ?>" class="order-card <?= $view_id==$o['id']?'active':'' ?>">
            <div style="flex:1">
                <div class="o-id">Pedido #<?= $o['id'] ?></div>
                <div class="o-date"><?= date('d/m/Y \à\s H:i', strtotime($o['created_at'])) ?> &bull; <?= $o['item_count'] ?> item(s)</div>
            </div>
            <div>
                <div style="font-size:20px;font-weight:900;color:var(--primaria);text-align:right"><?= format_price($o['total']) ?></div>
                <div style="text-align:right;margin-top:4px">
                    <span class="o-badge" style="background:<?= $cor ?>"><?= $lbl ?></span>
                </div>
            </div>
        </a>

        <?php if($view_id == $o['id'] && $view_order): ?>
        <div class="detail-box" style="margin-bottom:16px">
            <h3 style="margin-bottom:16px">Itens do Pedido #<?= $view_order['id'] ?></h3>
            <?php foreach($view_items as $item): ?>
            <div class="detail-item">
                <div style="flex:1">
                    <strong><?= e($item['product_name']) ?></strong><br>
                    <small style="color:var(--texto-claro)"><?= $item['quantity'] ?> × <?= format_price($item['price']) ?></small>
                </div>
                <strong><?= format_price($item['price'] * $item['quantity']) ?></strong>
            </div>
            <?php endforeach; ?>
            <div style="text-align:right;padding-top:14px;font-size:18px;font-weight:900">
                Total: <?= format_price($view_order['total']) ?>
            </div>
            <?php if($view_order['notes']): ?>
            <p style="margin-top:12px;font-size:13px;color:var(--texto-claro)">Obs: <?= e($view_order['notes']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<footer class="footer"><p>&copy; 2026 Iron &amp; Stone. Todos os direitos reservados.</p></footer>
</body>
</html>
