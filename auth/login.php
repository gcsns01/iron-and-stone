<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? '/admin/index.php' : '/produtos.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Requisição inválida. Tente novamente.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                login_user($user);
                $dest = $_GET['redirect'] ?? ($user['role'] === 'admin' ? '/admin/index.php' : '/produtos.php');
                redirect($dest);
            } else {
                $error = 'E-mail ou senha incorretos.';
            }
        } else {
            $error = 'Preencha todos os campos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Iron &amp; Stone</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        body { background: var(--primaria); display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .auth-card { background:#fff; border-radius:12px; padding:48px 40px; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,.3); }
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
        <h1 class="auth-title">Entrar na sua conta</h1>

        <?php if ($error): ?>
            <div class="alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?php csrf_field() ?>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus placeholder="seu@email.com">
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-auth">Entrar</button>
        </form>

        <div class="auth-links">
            Não tem conta? <a href="/auth/register.php">Criar conta gratuita</a>
        </div>
    </div>
    <a class="back-link" href="/index.html">← Voltar ao site</a>
</div>
</body>
</html>
