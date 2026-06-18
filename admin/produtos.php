<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title  = 'Produtos';
$active_page = 'produtos';

/* ── Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null;
        $price       = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $stock       = (int)($_POST['stock'] ?? 0);
        $active      = isset($_POST['active']) ? 1 : 0;
        $image_url   = trim($_POST['image_url'] ?? '');

        if (!$name || !$description) {
            flash('error', 'Nome e descrição são obrigatórios.');
        } else {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE products SET name=?,description=?,category_id=?,price=?,stock=?,active=? WHERE id=?");
                $stmt->execute([$name,$description,$category_id,$price,$stock,$active,$id]);
                if ($image_url) {
                    $pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$id]);
                    $pdo->prepare("INSERT INTO product_images (product_id,image_url,is_main) VALUES (?,?,1)")->execute([$id,$image_url]);
                }
                flash('success', 'Produto atualizado.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name,description,category_id,price,stock,active) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$name,$description,$category_id,$price,$stock,$active]);
                $pid = $pdo->lastInsertId();
                if ($image_url) {
                    $pdo->prepare("INSERT INTO product_images (product_id,image_url,is_main) VALUES (?,?,1)")->execute([$pid,$image_url]);
                }
                flash('success', 'Produto criado com sucesso.');
            }
        }
        redirect('/admin/produtos.php');
    }

    if ($action === 'toggle') {
        $id  = (int)$_POST['id'];
        $val = (int)$_POST['value'];
        $pdo->prepare("UPDATE products SET active=? WHERE id=?")->execute([$val,$id]);
        redirect('/admin/produtos.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        flash('success', 'Produto excluído.');
        redirect('/admin/produtos.php');
    }
}

/* ── Fetch ── */
$search = trim($_GET['q'] ?? '');
$cat_f  = (int)($_GET['cat'] ?? 0);

$sql    = "SELECT p.*,
                  c.name AS cat_name,
                  (SELECT pi.image_url FROM product_images pi WHERE pi.product_id=p.id AND pi.is_main=1 LIMIT 1) AS main_img
           FROM products p
           LEFT JOIN categories c ON c.id = p.category_id
           WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($cat_f)  { $sql .= " AND p.category_id = ?"; $params[] = $cat_f; }
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="card-head" style="background:#fff;border-radius:12px;padding:18px 22px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(0,0,0,.06);flex-wrap:wrap;gap:10px">
    <form method="get" class="search-bar" style="margin:0;flex:1">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar produto...">
        <select name="cat" class="fs">
            <option value="">Todas as categorias</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat_f==$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-se">Filtrar</button>
        <?php if($search||$cat_f): ?><a href="/admin/produtos.php" class="btn btn-se">Limpar</a><?php endif; ?>
    </form>
    <button class="btn btn-pr btn-lg" onclick="openModal('mo-add')">+ Novo Produto</button>
</div>

<div class="card">
    <div class="tbl-wrap">
        <?php if($products): ?>
        <table>
            <thead>
                <tr>
                    <th>Imagem</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($products as $p): ?>
            <tr>
                <td>
                    <?php if($p['main_img']): ?>
                        <img src="<?= e($p['main_img']) ?>" class="thumb" alt="">
                    <?php else: ?>
                        <div class="thumb" style="background:var(--bg)"></div>
                    <?php endif; ?>
                </td>
                <td><strong><?= e($p['name']) ?></strong><br><small style="color:var(--tx2)"><?= mb_strimwidth(e($p['description']),0,60,'...') ?></small></td>
                <td><?= $p['cat_name'] ? e($p['cat_name']) : '<span style="color:var(--tx2)">—</span>' ?></td>
                <td><strong><?= format_price($p['price']) ?></strong></td>
                <td>
                    <?php if($p['stock']<=0): ?>
                        <span class="badge b-ng">Esgotado</span>
                    <?php elseif($p['stock']<=5): ?>
                        <span class="badge b-wn"><?= $p['stock'] ?></span>
                    <?php else: ?>
                        <span><?= $p['stock'] ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" style="display:inline">
                        <?php csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="value" value="<?= $p['active']?0:1 ?>">
                        <label class="tog">
                            <input type="checkbox" <?= $p['active']?'checked':'' ?> onchange="this.form.submit()">
                            <span class="tog-sl"></span>
                        </label>
                    </form>
                </td>
                <td>
                    <button class="btn btn-in btn-sm" onclick='editProduct(<?= json_encode($p,JSON_HEX_APOS) ?>)'>Editar</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('Excluir este produto?')">
                        <?php csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-ng btn-sm">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">
            <p><?= $search||$cat_f ? 'Nenhum produto encontrado.' : 'Nenhum produto cadastrado ainda.' ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Adicionar/Editar -->
<div class="mo" id="mo-add">
    <div class="mo-box">
        <div class="mo-head">
            <h3 id="mo-title">Novo Produto</h3>
            <button class="mo-x" onclick="closeModal('mo-add')">✕</button>
        </div>
        <form method="post" id="product-form">
            <?php csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="f-id" value="0">
            <div class="mo-body">
                <div class="fg">
                    <label class="fl">Nome do Produto *</label>
                    <input type="text" name="name" id="f-name" class="fc" required placeholder="Ex: Martelo Stanley 20oz">
                </div>
                <div class="fg">
                    <label class="fl">Descrição *</label>
                    <textarea name="description" id="f-desc" class="fta" rows="3" required placeholder="Descrição do produto..."></textarea>
                </div>
                <div class="frow">
                    <div class="fg">
                        <label class="fl">Categoria</label>
                        <select name="category_id" id="f-cat" class="fs">
                            <option value="">Sem categoria</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Preço (R$) *</label>
                        <input type="number" step="0.01" min="0" name="price" id="f-price" class="fc" required placeholder="0.00">
                    </div>
                </div>
                <div class="frow">
                    <div class="fg">
                        <label class="fl">Estoque</label>
                        <input type="number" min="0" name="stock" id="f-stock" class="fc" value="0">
                    </div>
                    <div class="fg" style="display:flex;align-items:center;gap:10px;padding-top:22px">
                        <label class="tog">
                            <input type="checkbox" name="active" id="f-active" checked>
                            <span class="tog-sl"></span>
                        </label>
                        <span class="fl" style="margin:0">Produto ativo</span>
                    </div>
                </div>
                <div class="fg">
                    <label class="fl">URL da Imagem</label>
                    <input type="url" name="image_url" id="f-img" class="fc" placeholder="https://...">
                </div>
            </div>
            <div class="mo-foot">
                <button type="button" class="btn btn-se" onclick="closeModal('mo-add')">Cancelar</button>
                <button type="submit" class="btn btn-pr">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('mo-title').textContent = 'Editar Produto';
    document.getElementById('f-id').value     = p.id;
    document.getElementById('f-name').value   = p.name;
    document.getElementById('f-desc').value   = p.description;
    document.getElementById('f-cat').value    = p.category_id || '';
    document.getElementById('f-price').value  = p.price;
    document.getElementById('f-stock').value  = p.stock;
    document.getElementById('f-active').checked = p.active == 1;
    document.getElementById('f-img').value    = p.main_img || '';
    openModal('mo-add');
}
document.querySelector('button[onclick="openModal(\'mo-add\')"]').addEventListener('click',function(){
    document.getElementById('mo-title').textContent = 'Novo Produto';
    document.getElementById('product-form').reset();
    document.getElementById('f-id').value = '0';
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
