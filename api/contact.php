<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(bool $ok, string $msg): never
{
    echo json_encode(['ok' => $ok, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Método inválido.');
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$sector  = trim($_POST['sector'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$sector || !$message) {
    json_out(false, 'Todos os campos são obrigatórios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(false, 'E-mail inválido.');
}

$stmt = $pdo->prepare("INSERT INTO contact_messages (name,email,sector,message) VALUES (?,?,?,?)");
$stmt->execute([$name, $email, $sector, $message]);

json_out(true, 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
