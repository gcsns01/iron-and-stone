<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Handle legacy POST from old modal (redirect to admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect('/admin/produtos.php');
}

$search  = trim($_GET['q']   ?? '');
$cat_id  = (int)($_GET['cat'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$where    = "FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active = 1";
$params   = [];
if ($search) { $where .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($cat_id) { $where .= " AND p.category_id = ?"; $params[] = $cat_id; }

$count_stmt = $pdo->prepare("SELECT COUNT(*) $where");
$count_stmt->execute($params);
$total_count = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_count / $per_page));
$page = min($page, $total_pages);

$sql  = "SELECT p.*, c.name AS cat_name,
                (SELECT pi.image_url FROM product_images pi WHERE pi.product_id=p.id AND pi.is_main=1 LIMIT 1) AS img
         $where ORDER BY p.created_at DESC
         LIMIT $per_page OFFSET " . (($page - 1) * $per_page);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE active=1 ORDER BY name")->fetchAll();
$cart_count = cart_count();

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iron &amp; Stone | Produtos</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        .products-layout{display:grid;grid-template-columns:240px 1fr;gap:32px;align-items:start}
        .filters-panel{background:#fff;border-radius:10px;padding:22px;border:1px solid var(--borda);position:sticky;top:100px}
        .filters-panel h3{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--primaria);margin-bottom:14px}
        .filter-item{display:block;padding:9px 12px;border-radius:7px;text-decoration:none;color:var(--texto);font-size:14px;font-weight:500;transition:all .2s;margin-bottom:4px}
        .filter-item:hover{background:var(--fundo-secao);color:var(--destaque)}
        .filter-item.active{background:var(--destaque);color:#fff;font-weight:700}
        .search-wrap{margin-bottom:22px}
        .search-wrap form{display:flex;gap:10px}
        .search-wrap input{flex:1;padding:11px 14px;border:2px solid var(--borda);border-radius:8px;font-size:14px;font-family:inherit;transition:border-color .2s}
        .search-wrap input:focus{outline:none;border-color:var(--destaque)}
        .search-wrap button{padding:11px 18px;background:var(--destaque);color:#fff;border:none;border-radius:8px;font-weight:700;font-family:inherit;cursor:pointer;transition:background .2s}
        .search-wrap button:hover{background:var(--destaque-hover)}
        .results-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
        .results-count{font-size:13px;color:var(--texto-claro)}
        .btn-add-cart{width:100%;padding:11px;background:var(--destaque);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .2s;margin-top:auto}
        .btn-add-cart:hover{background:var(--destaque-hover);transform:scale(1.02)}
        .btn-add-cart:disabled{background:#ccc;cursor:not-allowed;transform:none}
        .card{display:flex;flex-direction:column;height:100%;position:relative}
        .card-badge{position:absolute;top:10px;left:10px;background:var(--destaque);color:#fff;font-size:10px;font-weight:700;padding:4px 8px;border-radius:20px;text-transform:uppercase}
        .pagination{display:flex;gap:8px;justify-content:center;margin-top:32px;flex-wrap:wrap}
        .pagination a,.pagination span{padding:8px 14px;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none;border:2px solid var(--borda);color:var(--texto);transition:all .2s}
        .pagination a:hover{border-color:var(--destaque);color:var(--destaque)}
        .pagination span.cur{background:var(--destaque);border-color:var(--destaque);color:#fff}
        .nav-auth a{font-size:14px;color:var(--texto);text-decoration:none;font-weight:500;transition:color .2s}
        .nav-auth a:hover{color:var(--destaque)}
        .cart-badge{background:var(--destaque);color:#fff;border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:2px;vertical-align:top}
        .toast-msg{visibility:hidden;min-width:260px;background:var(--primaria);color:#fff;text-align:center;border-radius:8px;padding:14px 20px;position:fixed;z-index:9999;left:50%;bottom:30px;transform:translateX(-50%);font-size:14px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.2);opacity:0;transition:opacity .4s,bottom .4s}
        .toast-msg.show{visibility:visible;opacity:1;bottom:50px}
        .toast-msg.ok{background:#27AE60}
        .toast-msg.err{background:#C0392B}
        .empty-state{text-align:center;padding:60px 20px;color:var(--texto-claro)}
        @media(max-width:900px){.products-layout{grid-template-columns:1fr}.filters-panel{position:static}}
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="/index.html">
                <img src="/logo_iron_stone.png" alt="Logo Iron &amp; Stone" class="logo-img">
                <span class="logo-text">Iron & Stone</span>
            </a>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="/index.html">Início</a></li>
                <li><a href="/quem-somos.php">Quem Somos</a></li>
                <li><a href="/produtos.php">Produtos</a></li>
                <li><a href="/servicos.html">Serviços</a></li>
                <li><a href="/fale-conosco.php" class="btn-nav">Fale Conosco</a></li>
                <?php if (is_logged_in()): ?>
                    <li>
                        <a href="/user/carrinho.php">
                            🛒<?php if($cart_count>0): ?><span class="cart-badge" id="cart-badge"><?= $cart_count ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li><a href="/user/perfil.php"><?= e($_SESSION['user_name']) ?></a></li>
                    <?php if(is_admin()): ?><li><a href="/admin/index.php" style="color:var(--destaque);font-weight:700">Admin</a></li><?php endif; ?>
                    <li><a href="/auth/logout.php">Sair</a></li>
                <?php else: ?>
                    <li><a href="/auth/login.php">Login</a></li>
                    <li><a href="/auth/register.php">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<section class="section-padding page-content">
    <div class="container">

        <div class="search-wrap">
            <form method="get">
                <?php if($cat_id): ?><input type="hidden" name="cat" value="<?= $cat_id ?>"><?php endif; ?>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar produtos...">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <div class="products-layout">
            <!-- Filtros -->
            <aside class="filters-panel">
                <h3>Categorias</h3>
                <a href="/produtos.php<?= $search?'?q='.urlencode($search):'' ?>" class="filter-item <?= !$cat_id?'active':'' ?>">Todos os Produtos</a>
                <?php foreach($categories as $cat): ?>
                    <a href="?cat=<?= $cat['id'] ?><?= $search?'&q='.urlencode($search):'' ?>" class="filter-item <?= $cat_id==$cat['id']?'active':'' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>

                <?php if($search || $cat_id): ?>
                    <hr style="margin:14px 0;border:none;border-top:1px solid var(--borda)">
                    <a href="/produtos.php" style="display:block;text-align:center;font-size:13px;color:var(--texto-claro);text-decoration:none">Limpar filtros</a>
                <?php endif; ?>
            </aside>

            <!-- Grid -->
            <div>
                <div class="results-header">
                    <span class="results-count">
                        <?php if($search): ?>
                            <?= $total_count ?> resultado(s) para "<strong><?= e($search) ?></strong>"
                        <?php elseif($cat_id): ?>
                            <?= $total_count ?> produto(s)
                        <?php else: ?>
                            <?= $total_count ?> produto(s) disponíveis
                        <?php endif; ?>
                    </span>
                    <?php if(is_admin()): ?>
                        <a href="/admin/produtos.php" style="font-size:13px;color:var(--destaque);text-decoration:none;font-weight:700">+ Gerenciar Produtos</a>
                    <?php endif; ?>
                </div>

                <?php if(empty($products)): ?>
                <div class="empty-state">
                    <h3>Nenhum produto encontrado</h3>
                    <?php if($search||$cat_id): ?>
                        <p>Tente outros termos ou <a href="/produtos.php" style="color:var(--destaque)">veja todos os produtos</a>.</p>
                    <?php else: ?>
                        <p>Nenhum produto cadastrado ainda.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="grid-cards">
                    <?php foreach($products as $p): ?>
                    <div class="card">
                        <?php if($p['stock'] <= 0): ?>
                            <span class="card-badge" style="background:#999">Esgotado</span>
                        <?php elseif($p['stock'] <= 3): ?>
                            <span class="card-badge" style="background:var(--destaque-hover)">Últimas unidades</span>
                        <?php endif; ?>

                        <?php if($p['img']): ?>
                            <img src="<?= e($p['img']) ?>" alt="<?= e($p['name']) ?>" class="product-img">
                        <?php else: ?>
                            <div class="product-img" style="background:var(--fundo-secao)"></div>
                        <?php endif; ?>

                        <p style="padding:0 20px;margin-bottom:6px"><?= e($p['cat_name'] ?? 'Sem categoria') ?></p>
                        <h3><?= e($p['name']) ?></h3>
                        <p style="font-size:13px;margin-bottom:10px"><?= mb_strimwidth(e($p['description']),0,80,'...') ?></p>
                        <div class="preco"><?= format_price($p['price']) ?></div>

                        <div style="padding:0 20px 20px;margin-top:auto">
                            <?php if(is_logged_in()): ?>
                                <?php if($p['stock'] > 0): ?>
                                    <button class="btn-add-cart"
                                            data-id="<?= $p['id'] ?>"
                                            data-csrf="<?= $csrf ?>"
                                            onclick="addToCart(this)">
                                        Adicionar ao Carrinho
                                    </button>
                                <?php else: ?>
                                    <button class="btn-add-cart" disabled>Sem estoque</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="/auth/login.php?redirect=/produtos.php" style="display:block;text-align:center;padding:11px;background:var(--fundo-secao);border-radius:6px;font-size:13px;font-weight:700;color:var(--primaria);text-decoration:none;border:2px solid var(--borda);transition:all .2s" onmouseover="this.style.borderColor='var(--destaque)'" onmouseout="this.style.borderColor='var(--borda)'">
                                    Login para comprar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $base = '?' . http_build_query(array_filter(['q'=>$search,'cat'=>$cat_id]));
                    $base .= $base==='?' ? '' : '&';
                    if($page>1) echo "<a href='{$base}page=".($page-1)."'>‹</a>";
                    for($i=1;$i<=$total_pages;$i++):
                        if($i==$page) echo "<span class='cur'>$i</span>";
                        else echo "<a href='{$base}page=$i'>$i</a>";
                    endfor;
                    if($page<$total_pages) echo "<a href='{$base}page=".($page+1)."'>›</a>";
                    ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<footer class="footer"><p>&copy; 2026 Iron &amp; Stone. Todos os direitos reservados.</p></footer>

<!-- Toast -->
<div id="toast" class="toast-msg"></div>

<script>
const CSRF = <?= json_encode($csrf) ?>;

async function addToCart(btn) {
    btn.disabled = true;
    btn.textContent = 'Adicionando...';
    try {
        const res = await fetch('/api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF
            },
            body: JSON.stringify({
                action: 'add',
                product_id: parseInt(btn.dataset.id),
                quantity: 1,
                csrf_token: CSRF
            })
        });
        const data = await res.json();
        showToast(data.message, data.ok ? 'ok' : 'err');
        if (data.ok) {
            updateCartBadge(data.cart_count);
            btn.textContent = 'Adicionado!';
            setTimeout(() => { btn.textContent = 'Adicionar ao Carrinho'; btn.disabled = false; }, 2000);
        } else {
            if (data.redirect) window.location = data.redirect;
            btn.textContent = 'Adicionar ao Carrinho';
            btn.disabled = false;
        }
    } catch(e) {
        showToast('Erro de conexão.', 'err');
        btn.textContent = 'Adicionar ao Carrinho';
        btn.disabled = false;
    }
}

function updateCartBadge(count) {
    let badge = document.getElementById('cart-badge');
    const cart_link = document.querySelector('a[href="/user/carrinho.php"]');
    if (count > 0) {
        if (!badge && cart_link) {
            badge = document.createElement('span');
            badge.id = 'cart-badge';
            badge.className = 'cart-badge';
            cart_link.appendChild(badge);
        }
        if (badge) badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast-msg ' + type + ' show';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
</body>
</html>
