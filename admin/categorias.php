<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title  = 'Categorias';
$active_page = 'categorias';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        if (!$name) {
            flash('error', 'Nome é obrigatório.');
        } else {
            $slug = slugify($name);
            if ($id) {
                $pdo->prepare("UPDATE categories SET name=?,slug=?,active=? WHERE id=?")->execute([$name,$slug,$active,$id]);
                flash('success', 'Categoria atualizada.');
            } else {
                try {
                    $pdo->prepare("INSERT INTO categories (name,slug,active) VALUES (?,?,?)")->execute([$name,$slug,$active]);
                    flash('success', 'Categoria criada.');
                } catch (PDOException $e) {
                    flash('error', 'Já existe uma categoria com este nome.');
                }
            }
        }
        redirect('/admin/categorias.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE products SET category_id=NULL WHERE category_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        flash('success', 'Categoria excluída.');
        redirect('/admin/categorias.php');
    }
}

$categories = $pdo->query(
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name"
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="text-align:right;margin-bottom:18px">
    <button class="btn btn-pr btn-lg" onclick="openNew()">+ Nova Categoria</button>
</div>

<div class="card">
    <div class="tbl-wrap">
        <?php if($categories): ?>
        <table>
            <thead>
                <tr><th>Nome</th><th>Slug</th><th>Produtos</th><th>Status</th><th>Ações</th></tr>
            </thead>
            <tbody>
            <?php foreach($categories as $cat): ?>
            <tr>
                <td><strong><?= e($cat['name']) ?></strong></td>
                <td><code style="font-size:12px;color:var(--tx2)"><?= e($cat['slug']) ?></code></td>
                <td><?= $cat['product_count'] ?></td>
                <td>
                    <?php if($cat['active']): ?>
                        <span class="badge b-ok">Ativa</span>
                    <?php else: ?>
                        <span class="badge b-ng">Inativa</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-in btn-sm" onclick='editCat(<?= json_encode($cat,JSON_HEX_APOS) ?>)'>Editar</button>
                    <?php if($cat['product_count']==0): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Excluir categoria?')">
                        <?php csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-ng btn-sm">Excluir</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty"><p>Nenhuma categoria cadastrada.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="mo" id="mo-cat">
    <div class="mo-box">
        <div class="mo-head">
            <h3 id="cat-title">Nova Categoria</h3>
            <button class="mo-x" onclick="closeModal('mo-cat')">✕</button>
        </div>
        <form method="post">
            <?php csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="c-id" value="0">
            <div class="mo-body">
                <div class="fg">
                    <label class="fl">Nome *</label>
                    <input type="text" name="name" id="c-name" class="fc" required placeholder="Ex: Ferramentas">
                </div>
                <div class="fg" style="display:flex;align-items:center;gap:10px">
                    <label class="tog">
                        <input type="checkbox" name="active" id="c-active" checked>
                        <span class="tog-sl"></span>
                    </label>
                    <span class="fl" style="margin:0">Categoria ativa</span>
                </div>
            </div>
            <div class="mo-foot">
                <button type="button" class="btn btn-se" onclick="closeModal('mo-cat')">Cancelar</button>
                <button type="submit" class="btn btn-pr">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNew() {
    document.getElementById('cat-title').textContent = 'Nova Categoria';
    document.getElementById('c-id').value = '0';
    document.getElementById('c-name').value = '';
    document.getElementById('c-active').checked = true;
    openModal('mo-cat');
}
function editCat(c) {
    document.getElementById('cat-title').textContent = 'Editar Categoria';
    document.getElementById('c-id').value = c.id;
    document.getElementById('c-name').value = c.name;
    document.getElementById('c-active').checked = c.active == 1;
    openModal('mo-cat');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
