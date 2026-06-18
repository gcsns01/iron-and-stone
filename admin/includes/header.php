<?php
// expects $page_title and $active_page to be defined by the calling page
$page_title  = $page_title  ?? 'Admin';
$active_page = $active_page ?? '';
$unread_msgs = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status='unread'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | Admin Iron &amp; Stone</title>
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sb-logo">
        <span>Painel Admin</span>
    </div>

    <nav class="sb-nav">
        <a href="/admin/index.php" class="<?= $active_page==='dashboard'?'active':'' ?>">Dashboard</a>
        <div class="sb-sep"></div>
        <a href="/admin/produtos.php" class="<?= $active_page==='produtos'?'active':'' ?>">Produtos</a>
        <a href="/admin/categorias.php" class="<?= $active_page==='categorias'?'active':'' ?>">Categorias</a>
        <div class="sb-sep"></div>
        <a href="/admin/mensagens.php" class="<?= $active_page==='mensagens'?'active':'' ?>">
            Mensagens
            <?php if($unread_msgs>0): ?>
                <span class="badge-sm"><?= $unread_msgs ?></span>
            <?php endif; ?>
        </a>
        <a href="/admin/usuarios.php" class="<?= $active_page==='usuarios'?'active':'' ?>">Usuários</a>
        <div class="sb-sep"></div>
        <a href="/produtos.php" target="_blank">Ver Site</a>
    </nav>

    <div class="sb-foot">
        <div class="sb-user">
            <div class="sb-avatar"><?= strtoupper(mb_substr($_SESSION['user_name']??'A',0,1)) ?></div>
            <div>
                <div class="sb-uname"><?= e($_SESSION['user_name']??'') ?></div>
                <div class="sb-urole">Administrador</div>
            </div>
        </div>
        <a href="/auth/logout.php" class="sb-logout">Sair</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <h1><?= e($page_title) ?></h1>
        <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
    </div>
    <div class="content">
        <?php show_flash('success'); show_flash('error'); ?>
