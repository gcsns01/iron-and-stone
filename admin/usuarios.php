<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title  = 'Usuários';
$active_page = 'usuarios';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = in_array($_POST['role']??'',['admin','user']) ? $_POST['role'] : 'user';
        $active   = isset($_POST['active']) ? 1 : 0;
        $password = trim($_POST['password'] ?? '');

        if (!$name || !$email) {
            flash('error', 'Nome e e-mail são obrigatórios.');
        } else {
            if ($id) {
                // Don't allow removing last admin
                if ($role !== 'admin') {
                    $is_last = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() == 1
                               && (int)$pdo->prepare("SELECT role='admin' FROM users WHERE id=?")->execute([$id]);
                }
                $sql    = "UPDATE users SET name=?,email=?,role=?,active=? WHERE id=?";
                $params = [$name,$email,$role,$active,$id];
                if ($password) {
                    $sql    = "UPDATE users SET name=?,email=?,role=?,active=?,password=? WHERE id=?";
                    $params = [$name,$email,$role,$active,password_hash($password,PASSWORD_DEFAULT),$id];
                }
                try {
                    $pdo->prepare($sql)->execute($params);
                    flash('success', 'Usuário atualizado.');
                } catch (PDOException $e) {
                    flash('error', 'E-mail já está em uso por outro usuário.');
                }
            }
        }
        redirect('/admin/usuarios.php');
    }

    if ($action === 'toggle') {
        $val = (int)$_POST['value'];
        // Prevent disabling own account
        if ($id == $_SESSION['user_id'] && $val == 0) {
            flash('error', 'Você não pode desativar sua própria conta.');
        } else {
            $pdo->prepare("UPDATE users SET active=? WHERE id=?")->execute([$val,$id]);
        }
        redirect('/admin/usuarios.php');
    }

    if ($action === 'delete') {
        if ($id == $_SESSION['user_id']) {
            flash('error', 'Você não pode excluir sua própria conta.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            flash('success', 'Usuário excluído.');
        }
        redirect('/admin/usuarios.php');
    }
}

$search = trim($_GET['q'] ?? '');
$sql    = "SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count FROM users u WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY u.role DESC, u.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom:18px">
    <form method="get" class="search-bar">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome ou e-mail...">
        <button type="submit" class="btn btn-se">Buscar</button>
        <?php if($search): ?><a href="/admin/usuarios.php" class="btn btn-se">Limpar</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="tbl-wrap">
        <?php if($users): ?>
        <table>
            <thead>
                <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Pedidos</th><th>Cadastro</th><th>Ativo</th><th>Ações</th></tr>
            </thead>
            <tbody>
            <?php foreach($users as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:32px;height:32px;background:<?= $u['role']==='admin'?'var(--ac)':'var(--in)' ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0">
                            <?= strtoupper(mb_substr($u['name'],0,1)) ?>
                        </div>
                        <strong><?= e($u['name']) ?></strong>
                        <?php if($u['id']==$_SESSION['user_id']): ?><span class="badge b-or" style="background:rgba(230,126,34,.12);color:#9A3A00">Você</span><?php endif; ?>
                    </div>
                </td>
                <td style="font-size:12px"><?= e($u['email']) ?></td>
                <td>
                    <?php if($u['role']==='admin'): ?>
                        <span class="badge b-wn">Admin</span>
                    <?php else: ?>
                        <span class="badge b-in">Usuário</span>
                    <?php endif; ?>
                </td>
                <td><?= $u['order_count'] ?></td>
                <td style="font-size:12px;white-space:nowrap"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                    <form method="post" style="display:inline">
                        <?php csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="value" value="<?= $u['active']?0:1 ?>">
                        <label class="tog">
                            <input type="checkbox" <?= $u['active']?'checked':'' ?> onchange="this.form.submit()">
                            <span class="tog-sl"></span>
                        </label>
                    </form>
                    <?php else: ?>
                        <label class="tog"><input type="checkbox" checked disabled><span class="tog-sl"></span></label>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-in btn-sm" onclick='editUser(<?= json_encode($u,JSON_HEX_APOS) ?>)'>Editar</button>
                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Excluir este usuário?')">
                        <?php csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-ng btn-sm">Excluir</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty"><p>Nenhum usuário encontrado.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Editar -->
<div class="mo" id="mo-user">
    <div class="mo-box">
        <div class="mo-head">
            <h3>Editar Usuário</h3>
            <button class="mo-x" onclick="closeModal('mo-user')">✕</button>
        </div>
        <form method="post">
            <?php csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="u-id">
            <div class="mo-body">
                <div class="fg">
                    <label class="fl">Nome *</label>
                    <input type="text" name="name" id="u-name" class="fc" required>
                </div>
                <div class="fg">
                    <label class="fl">E-mail *</label>
                    <input type="email" name="email" id="u-email" class="fc" required>
                </div>
                <div class="frow">
                    <div class="fg">
                        <label class="fl">Perfil</label>
                        <select name="role" id="u-role" class="fs">
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="fg" style="display:flex;align-items:center;gap:10px;padding-top:22px">
                        <label class="tog">
                            <input type="checkbox" name="active" id="u-active">
                            <span class="tog-sl"></span>
                        </label>
                        <span class="fl" style="margin:0">Conta ativa</span>
                    </div>
                </div>
                <div class="fg">
                    <label class="fl">Nova senha <small style="font-weight:400;text-transform:none">(deixe em branco para manter)</small></label>
                    <input type="password" name="password" class="fc" placeholder="••••••••" minlength="6">
                </div>
            </div>
            <div class="mo-foot">
                <button type="button" class="btn btn-se" onclick="closeModal('mo-user')">Cancelar</button>
                <button type="submit" class="btn btn-pr">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(u) {
    document.getElementById('u-id').value       = u.id;
    document.getElementById('u-name').value     = u.name;
    document.getElementById('u-email').value    = u.email;
    document.getElementById('u-role').value     = u.role;
    document.getElementById('u-active').checked = u.active == 1;
    openModal('mo-user');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
