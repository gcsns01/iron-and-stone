<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) redirect('/produtos.php');

$errors = [];
$data   = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Requisição inválida.';
    } else {
        $data['name']  = trim($_POST['name'] ?? '');
        $data['email'] = trim($_POST['email'] ?? '');
        $password      = $_POST['password'] ?? '';
        $confirm       = $_POST['confirm'] ?? '';

        if (!$data['name'])          $errors[] = 'Nome é obrigatório.';
        if (!$data['email'])         $errors[] = 'E-mail é obrigatório.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
        if (strlen($password) < 6)   $errors[] = 'Senha deve ter ao menos 6 caracteres.';
        if ($password !== $confirm)  $errors[] = 'As senhas não coincidem.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'Este e-mail já está cadastrado.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'user')");
                $stmt->execute([$data['name'], $data['email'], $hash]);
                $user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $user->execute([$pdo->lastInsertId()]);
                login_user($user->fetch());
                flash('success', 'Bem-vindo(a), ' . $data['name'] . '! Conta criada com sucesso.');
                redirect('/produtos.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta | Iron &amp; Stone</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        body { background:var(--primaria); display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:20px 0; }
        .auth-card { background:#fff; border-radius:12px; padding:48px 40px; width:100%; max-width:440px; box-shadow:0 20px 60px rgba(0,0,0,.3); }
        .auth-logo { text-align:center; margin-bottom:28px; }
        .auth-logo img { height:55px; }
        .auth-title { font-size:22px; font-weight:700; color:var(--primaria); text-align:center; margin-bottom:28px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--texto); margin-bottom:6px; }
        .form-group input { width:100%; padding:12px 14px; border:2px solid var(--borda); border-radius:8px; font-size:14px; font-family:inherit; box-sizing:border-box; transition:border-color .2s; }
        .form-group input:focus { outline:none; border-color:var(--destaque); }
        .btn-auth { width:100%; padding:14px; background:var(--destaque); color:#fff; border:none; border-radius:8px; font-size:15px; font-weight:700; font-family:inherit; cursor:pointer; transition:background .2s; margin-top:6px; }
        .btn-auth:hover { background:var(--destaque-hover); }
        .auth-links { text-align:center; margin-top:20px; font-size:14px; color:var(--texto-claro); }
        .auth-links a { color:var(--destaque); font-weight:700; text-decoration:none; }
        .alert-error { background:#FDECEA; border:1px solid #E74C3C; color:#C0392B; padding:12px 14px; border-radius:8px; margin-bottom:18px; font-size:13px; }
        .alert-error ul { margin:4px 0 0 16px; }
        .back-link { display:block; text-align:center; margin-top:16px; font-size:13px; color:rgba(255,255,255,.6); text-decoration:none; }
        .back-link:hover { color:#fff; }
    </style>
</head>
<body>
<div>
    <div class="auth-card">
        <div class="auth-logo">
            <a href="/index.html"><img src="/logo_iron_stone.png" alt="Iron &amp; Stone"></a>
        </div>
        <h1 class="auth-title">Criar conta</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?php csrf_field() ?>
            <div class="form-group">
                <label>Nome completo</label>
                <input type="text" name="name" value="<?= e($data['name']) ?>" required autofocus placeholder="Seu nome">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" value="<?= e($data['email']) ?>" required placeholder="seu@email.com">
            </div>
            <div class="form-group">
                <label>Senha <small style="font-weight:400;text-transform:none">(mín. 6 caracteres)</small></label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Confirmar senha</label>
                <input type="password" name="confirm" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-auth">Criar conta</button>
        </form>

        <div class="auth-links">
            Já tem conta? <a href="/auth/login.php">Entrar</a>
        </div>
    </div>
    <a class="back-link" href="/index.html">← Voltar ao site</a>
</div>
</body>
</html>
