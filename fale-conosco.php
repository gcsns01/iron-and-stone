<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$success = false;
$error   = '';
$data    = ['name'=>'','email'=>'','sector'=>'','message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Requisição inválida. Tente novamente.';
    } else {
        $data = [
            'name'    => trim($_POST['name']    ?? ''),
            'email'   => trim($_POST['email']   ?? ''),
            'sector'  => trim($_POST['sector']  ?? ''),
            'message' => trim($_POST['message'] ?? ''),
        ];

        if (!$data['name'] || !$data['email'] || !$data['sector'] || !$data['message']) {
            $error = 'Todos os campos são obrigatórios.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, insira um e-mail válido.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name,email,sector,message) VALUES (?,?,?,?)");
            $stmt->execute([$data['name'],$data['email'],$data['sector'],$data['message']]);
            $success = true;
            $data = ['name'=>'','email'=>'','sector'=>'','message'=>''];
        }
    }
}

$cart_count = cart_count();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iron &amp; Stone | Fale Conosco</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="/index.html">
                <img src="/logo_iron_stone.png" alt="Logo Iron &amp; Stone" class="logo-img">
                <span class="logo-text">Iron & Stone</span>
            </a>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="/index.html">Início</a></li>
                <li><a href="/quem-somos.php">Quem Somos</a></li>
                <li><a href="/produtos.php">Produtos</a></li>
                <li><a href="/servicos.html">Serviços</a></li>
                <li><a href="/fale-conosco.php" class="btn-nav">Fale Conosco</a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="/user/carrinho.php">🛒<?= $cart_count>0?" ($cart_count)":'' ?></a></li>
                    <li><a href="/user/perfil.php"><?= e($_SESSION['user_name']) ?></a></li>
                    <?php if(is_admin()): ?><li><a href="/admin/index.php">Admin</a></li><?php endif; ?>
                    <li><a href="/auth/logout.php">Sair</a></li>
                <?php else: ?>
                    <li><a href="/auth/login.php">Login</a></li>
                    <li><a href="/auth/register.php">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<section class="section-padding page-content">
    <div class="container form-container">
        <div class="form-header">
            <h2>Fale Conosco</h2>
            <p>Nossa comunicação é tão forte quanto nossos produtos. Envie sua solicitação.</p>
        </div>

        <?php if ($success): ?>
            <div class="sucesso" style="margin-bottom:24px;border-radius:8px">
                Mensagem enviada com sucesso! Entraremos em contato em breve.
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="erro" style="margin-bottom:24px;border-radius:8px"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <?php csrf_field() ?>
            <div class="input-group">
                <label>Nome Completo</label>
                <input type="text" name="name" value="<?= e($data['name']) ?>" placeholder="Digite seu nome completo" required>
            </div>
            <div class="input-group">
                <label>E-mail Profissional</label>
                <input type="email" name="email" value="<?= e($data['email']) ?>" placeholder="email@empresa.com.br" required>
            </div>
            <div class="input-group">
                <label>Setor de Atendimento</label>
                <select name="sector" required>
                    <option value="">Selecione o departamento...</option>
                    <option value="Suporte Técnico" <?= $data['sector']==='Suporte Técnico'?'selected':'' ?>>Suporte Técnico (Engenharia)</option>
                    <option value="Vendas"          <?= $data['sector']==='Vendas'?'selected':'' ?>>Vendas e Orçamentos</option>
                    <option value="SAC"             <?= $data['sector']==='SAC'?'selected':'' ?>>Pós-Venda e SAC</option>
                </select>
            </div>
            <div class="input-group">
                <label>Detalhes do Projeto/Dúvida</label>
                <textarea name="message" rows="4" placeholder="Descreva sua necessidade..." required><?= e($data['message']) ?></textarea>
            </div>
            <button type="submit" class="btn-submit">Enviar Solicitação</button>
        </form>
    </div>
</section>

<footer class="footer"><p>&copy; 2026 Iron &amp; Stone. Todos os direitos reservados.</p></footer>
</body>
</html>
