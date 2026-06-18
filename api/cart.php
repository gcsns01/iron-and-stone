<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(bool $ok, string $message, array $data = []): never
{
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $data));
    exit;
}

if (!is_logged_in()) {
    json_out(false, 'Faça login para usar o carrinho.', ['redirect' => '/auth/login.php']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Método inválido.');
}

$body   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $body['action'] ?? '';
$uid    = $_SESSION['user_id'];

// CSRF – allow token from header or body
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $body['csrf_token'] ?? '';
if (!hash_equals(csrf_token(), $token)) {
    json_out(false, 'Token inválido.');
}

$cart_id = get_or_create_cart($pdo, $uid);

/* ── add ── */
if ($action === 'add') {
    $product_id = (int)($body['product_id'] ?? 0);
    $quantity   = max(1, (int)($body['quantity'] ?? 1));

    $stmt = $pdo->prepare("SELECT id,name,price,stock,active FROM products WHERE id=?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product || !$product['active']) json_out(false, 'Produto não encontrado.');
    if ($product['stock'] < 1)           json_out(false, 'Produto sem estoque.');

    // Upsert
    $pdo->prepare(
        "INSERT INTO cart_items (cart_id,product_id,quantity)
         VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
    )->execute([$cart_id, $product_id, $quantity]);

    $count = cart_count();
    json_out(true, "\"" . $product['name'] . "\" adicionado ao carrinho!", ['cart_count' => $count]);
}

/* ── remove ── */
if ($action === 'remove') {
    $item_id = (int)($body['item_id'] ?? 0);
    $pdo->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?")->execute([$item_id, $cart_id]);
    json_out(true, 'Item removido.', ['cart_count' => cart_count()]);
}

/* ── update ── */
if ($action === 'update') {
    $item_id  = (int)($body['item_id'] ?? 0);
    $quantity = max(1, (int)($body['quantity'] ?? 1));
    $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND cart_id=?")->execute([$quantity,$item_id,$cart_id]);
    json_out(true, 'Quantidade atualizada.', ['cart_count' => cart_count()]);
}

/* ── count ── */
if ($action === 'count') {
    json_out(true, '', ['cart_count' => cart_count()]);
}

json_out(false, 'Ação desconhecida.');
