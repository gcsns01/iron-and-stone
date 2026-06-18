<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title  = 'Mensagens';
$active_page = 'mensagens';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'mark_read') {
        $pdo->prepare("UPDATE contact_messages SET status='read' WHERE id=?")->execute([$id]);
    } elseif ($action === 'mark_replied') {
        $pdo->prepare("UPDATE contact_messages SET status='replied' WHERE id=?")->execute([$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$id]);
        flash('success', 'Mensagem excluída.');
        redirect('/admin/mensagens.php');
    }
    redirect('/admin/mensagens.php' . ($id && $action!=='delete' ? '?view='.$id : ''));
}

$filter     = $_GET['status'] ?? '';
$view_id    = (int)($_GET['view'] ?? 0);
$view_msg   = null;

if ($view_id) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id=?");
    $stmt->execute([$view_id]);
    $view_msg = $stmt->fetch();
    if ($view_msg && $view_msg['status'] === 'unread') {
        $pdo->prepare("UPDATE contact_messages SET status='read' WHERE id=?")->execute([$view_id]);
        $view_msg['status'] = 'read';
    }
}

$sql    = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];
if ($filter) { $sql .= " AND status=?"; $params[] = $filter; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$badge_map = [
    'unread'  => ['Nova',       'b-wn'],
    'read'    => ['Lida',       'b-gr'],
    'replied' => ['Respondida', 'b-ok'],
];

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;gap:22px;align-items:flex-start">

<!-- Lista -->
<div style="flex:1;min-width:0">
    <div class="search-bar" style="margin-bottom:16px">
        <a href="/admin/mensagens.php" class="btn <?= !$filter?'btn-pr':'btn-se' ?> btn-sm">Todas</a>
        <a href="?status=unread"  class="btn <?= $filter==='unread'?'btn-pr':'btn-se' ?> btn-sm">Novas</a>
        <a href="?status=read"    class="btn <?= $filter==='read'?'btn-pr':'btn-se' ?> btn-sm">Lidas</a>
        <a href="?status=replied" class="btn <?= $filter==='replied'?'btn-pr':'btn-se' ?> btn-sm">Respondidas</a>
    </div>

    <div class="card">
        <div class="tbl-wrap">
            <?php if($messages): ?>
            <table>
                <thead><tr><th>Nome</th><th>E-mail</th><th>Setor</th><th>Status</th><th>Data</th><th></th></tr></thead>
                <tbody>
                <?php foreach($messages as $m):
                    [$lbl,$cls] = $badge_map[$m['status']] ?? ['—','b-gr'];
                    $is_viewing = $view_id == $m['id'];
                ?>
                <tr style="<?= $is_viewing?'background:rgba(230,126,34,.05)':'' ?>">
                    <td><strong><?= e($m['name']) ?></strong></td>
                    <td style="font-size:12px"><?= e($m['email']) ?></td>
                    <td><?= e($m['sector']??'—') ?></td>
                    <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
                    <td style="font-size:12px;white-space:nowrap"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                    <td>
                        <a href="?view=<?= $m['id'] ?>" class="btn btn-in btn-sm">Ver</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Excluir mensagem?')">
                            <?php csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-ng btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty"><p>Nenhuma mensagem encontrada.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detalhe -->
<?php if($view_msg): ?>
<div style="width:360px;flex-shrink:0">
    <div class="card">
        <div class="card-head">
            <h2>Detalhes</h2>
            <a href="/admin/mensagens.php" style="text-decoration:none;color:var(--tx2);font-size:18px">✕</a>
        </div>
        <div class="card-body">
            <p><strong>Nome:</strong> <?= e($view_msg['name']) ?></p>
            <p style="margin-top:8px"><strong>E-mail:</strong> <a href="mailto:<?= e($view_msg['email']) ?>"><?= e($view_msg['email']) ?></a></p>
            <p style="margin-top:8px"><strong>Setor:</strong> <?= e($view_msg['sector']??'—') ?></p>
            <p style="margin-top:8px"><strong>Data:</strong> <?= date('d/m/Y \à\s H:i', strtotime($view_msg['created_at'])) ?></p>
            <hr style="margin:16px 0;border:none;border-top:1px solid var(--bd)">
            <p style="margin-bottom:8px"><strong>Mensagem:</strong></p>
            <div style="background:var(--bg);border-radius:8px;padding:14px;font-size:13px;line-height:1.6;white-space:pre-wrap"><?= e($view_msg['message']) ?></div>
            <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap">
                <?php if($view_msg['status']!=='replied'): ?>
                <form method="post" style="display:inline">
                    <?php csrf_field() ?>
                    <input type="hidden" name="action" value="mark_replied">
                    <input type="hidden" name="id" value="<?= $view_msg['id'] ?>">
                    <button class="btn btn-ok btn-sm">✓ Marcar respondida</button>
                </form>
                <?php endif; ?>
                <a href="mailto:<?= e($view_msg['email']) ?>?subject=Re: <?= urlencode('Contato Iron & Stone') ?>" class="btn btn-in btn-sm">Responder e-mail</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
