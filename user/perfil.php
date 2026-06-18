<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login('/user/perfil.php');

$user_id = $_SESSION['user_id'];
$errors  = [];
$success = false;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $name         = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!$name)  $errors[] = 'Nome é obrigatório.';
    if (!$email) $errors[] = 'E-mail é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';

    if ($new_pass) {
        if (!password_verify($current_pass, $user['password'])) $errors[] = 'Senha atual incorreta.';
        if (strlen($new_pass) < 6) $errors[] = 'Nova senha deve ter ao menos 6 caracteres.';
        if ($new_pass !== $confirm_pass) $errors[] = 'Confirmação de senha não confere.';
    }

    if (empty($errors)) {
        try {
            if ($new_pass) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET name=?,email=?,password=? WHERE id=?")->execute([$name,$email,$hash,$user_id]);
            } else {
                $pdo->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email,$user_id]);
            }
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            $user['name']  = $name;
            $user['email'] = $email;
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Este e-mail já está em uso.';
        }
    }
}

$order_count = (int)$pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?")->execute([$user_id]) ?
    $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id=$user_id")->fetchColumn() : 0;
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$stmt2->execute([$user_id]);
$order_count = (int)$stmt2->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil | Iron &amp; Stone</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
    <style>
        .profile-grid{display:grid;grid-template-columns:280px 1fr;gap:28px;align-items:start}
        .profile-sidebar{background:#fff;border-radius:12px;padding:28px;border:1px solid var(--borda);text-align:center}
        .profile-avatar{width:80px;height:80px;background:var(--primaria);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:#fff;margin:0 auto 16px}
        .profile-name{font-size:18px;font-weight:700;margin-bottom:4px}
        .profile-email{color:var(--texto-claro);font-size:13px;margin-bottom:16px}
        .profile-stat{background:var(--fundo-secao);border-radius:8px;padding:14px;margin-bottom:10px}
        .profile-stat strong{display:block;font-size:22px;font-weight:900;color:var(--primaria)}
        .profile-stat span{font-size:12px;color:var(--texto-claro)}
        .profile-links{margin-top:16px}
        .profile-links a{display:block;padding:10px;color:var(--texto);text-decoration:none;border-radius:8px;font-size:14px;font-weight:500;transition:background .2s}
        .profile-links a:hover{background:var(--fundo-secao)}
        .form-card{background:#fff;border-radius:12px;padding:28px;border:1px solid var(--borda)}
        .form-card h2{font-size:18px;margin-bottom:24px;padding-bottom:14px;border-bottom:1px solid var(--borda)}
        .frow{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .fg{margin-bottom:18px}
        .fl{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--texto);margin-bottom:6px}
        .fc{width:100%;padding:12px 14px;border:2px solid var(--borda);border-radius:8px;font-size:14px;font-family:inherit;box-sizing:border-box;transition:border-color .2s}
        .fc:focus{outline:none;border-color:var(--destaque)}
        .btn-save{padding:12px 28px;background:var(--destaque);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;transition:background .2s}
        .btn-save:hover{background:var(--destaque-hover)}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:13px}
        .alert-success{background:#D5F5E3;border:1px solid #A9DFBF;color:#1E8449}
        .alert-danger{background:#FDECEA;border:1px solid #F5B7B1;color:#C0392B}
        .sep{margin:28px 0;border:none;border-top:1px solid var(--borda)}
        @media(max-width:768px){.profile-grid{grid-template-columns:1fr}.frow{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="/index.html">
                <img src="/logo_iron_stone.png" alt="Logo" class="logo-img">
                <span class="logo-text">Iron & Stone</span>
            </a>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="/produtos.php">Produtos</a></li>
                <li><a href="/user/carrinho.php">🛒 Carrinho</a></li>
                <li><a href="/user/pedidos.php">Pedidos</a></li>
                <li><a href="/auth/logout.php" class="btn-nav">Sair</a></li>
            </ul>
        </nav>
    </div>
</header>

<section class="section-padding page-content">
    <div class="container">
        <h2 class="section-title">Meu Perfil</h2>

        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="profile-avatar"><?= strtoupper(mb_substr($user['name'],0,1)) ?></div>
                <div class="profile-name"><?= e($user['name']) ?></div>
                <div class="profile-email"><?= e($user['email']) ?></div>
                <div class="profile-stat">
                    <strong><?= $order_count ?></strong>
                    <span>Pedido(s) realizado(s)</span>
                </div>
                <div class="profile-links">
                    <a href="/user/pedidos.php">Meus Pedidos</a>
                    <a href="/user/carrinho.php">Carrinho</a>
                    <a href="/produtos.php">Produtos</a>
                    <a href="/auth/logout.php">Sair</a>
                </div>
            </div>

            <div>
                <div class="form-card">
                    <h2>Dados Pessoais</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success">Dados atualizados com sucesso!</div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul style="margin:0 0 0 16px"><?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <?php csrf_field() ?>
                        <div class="fg">
                            <label class="fl">Nome completo</label>
                            <input type="text" name="name" class="fc" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="fg">
                            <label class="fl">E-mail</label>
                            <input type="email" name="email" class="fc" value="<?= e($user['email']) ?>" required>
                        </div>

                        <hr class="sep">
                        <h3 style="font-size:15px;margin-bottom:16px">Alterar Senha <small style="font-weight:400;font-size:13px">(opcional)</small></h3>

                        <div class="fg">
                            <label class="fl">Senha atual</label>
                            <input type="password" name="current_password" class="fc" placeholder="Digite a senha atual">
                        </div>
                        <div class="frow">
                            <div class="fg">
                                <label class="fl">Nova senha</label>
                                <input type="password" name="new_password" class="fc" placeholder="Mín. 6 caracteres">
                            </div>
                            <div class="fg">
                                <label class="fl">Confirmar nova senha</label>
                                <input type="password" name="confirm_password" class="fc" placeholder="Repita a senha">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">Salvar Alterações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="footer"><p>&copy; 2026 Iron &amp; Stone. Todos os direitos reservados.</p></footer>
</body>
</html>
