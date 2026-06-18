<?php

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool
{
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_login(string $redirect_after = ''): void
{
    if (!is_logged_in()) {
        $back = $redirect_after ?: $_SERVER['REQUEST_URI'];
        redirect('/auth/login.php?redirect=' . urlencode($back));
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        redirect('/produtos.php');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
