<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title  = 'Dashboard';
$active_page = 'dashboard';

$total_products = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_users    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$total_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_msgs     = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status='unread'")->fetchColumn();
$total_revenue  = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled')")->fetchColumn();

$recent_orders = $pdo->query(
    "SELECT o.*, u.name as user_name FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 5"
)->fetchAll();

$recent_msgs = $pdo->query(
    "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$status_labels = [
    'pending'    => ['Pendente',   'b-wn'],
    'processing' => ['Processando','b-in'],
    'shipped'    => ['Enviado',    'b-in'],
    'delivered'  => ['Entregue',   'b-ok'],
    'cancelled'  => ['Cancelado',  'b-ng'],
];

include __DIR__ . '/includes/header.php';
?>

<div class="stats">
    <div class="stat">
        <div class="stat-val"><?= $total_products ?></div>
        <div class="stat-lbl">Produtos Ativos</div>
    </div>
    <div class="stat">
        <div class="stat-val"><?= $total_users ?></div>
        <div class="stat-lbl">Usuários</div>
    </div>
    <div class="stat">
        <div class="stat-val"><?= $total_orders ?></div>
        <div class="stat-lbl">Pedidos</div>
    </div>
    <div class="stat">
        <div class="stat-val"><?= $total_msgs ?></div>
        <div class="stat-lbl">Msgs Não Lidas</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;flex-wrap:wrap">

<div class="card">
    <div class="card-head">
        <h2>Pedidos Recentes</h2>
        <a href="#" class="btn btn-se btn-sm">Ver todos</a>
    </div>
    <div class="tbl-wrap">
        <?php if($recent_orders): ?>
        <table>
            <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($recent_orders as $o):
                [$lbl,$cls] = $status_labels[$o['status']] ?? ['—','b-gr'];
            ?>
            <tr>
                <td><strong>#<?= $o['id'] ?></strong></td>
                <td><?= e($o['user_name']) ?></td>
                <td><?= format_price($o['total']) ?></td>
                <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty"><p>Nenhum pedido ainda.</p></div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h2>Mensagens Recentes</h2>
        <a href="/admin/mensagens.php" class="btn btn-se btn-sm">Ver todas</a>
    </div>
    <div class="tbl-wrap">
        <?php if($recent_msgs): ?>
        <table>
            <thead><tr><th>Nome</th><th>Setor</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach($recent_msgs as $m): ?>
            <tr>
                <td><?= e($m['name']) ?><br><small style="color:var(--tx2)"><?= time_ago($m['created_at']) ?></small></td>
                <td><?= e($m['sector']??'—') ?></td>
                <td>
                    <?php if($m['status']==='unread'): ?>
                        <span class="badge b-wn">Nova</span>
                    <?php elseif($m['status']==='replied'): ?>
                        <span class="badge b-ok">Respondida</span>
                    <?php else: ?>
                        <span class="badge b-gr">Lida</span>
                    <?php endif; ?>
                </td>
                <td><a href="/admin/mensagens.php?view=<?= $m['id'] ?>" class="btn btn-in btn-sm">Ver</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty"><p>Nenhuma mensagem.</p></div>
        <?php endif; ?>
    </div>
</div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
