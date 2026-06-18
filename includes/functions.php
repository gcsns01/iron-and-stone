<?php

function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, string $message, string $type = 'success'): void
{
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

function get_flash(string $key): ?array
{
    if (isset($_SESSION['flash'][$key])) {
        $f = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $f;
    }
    return null;
}

function show_flash(string $key): void
{
    $f = get_flash($key);
    if (!$f) return;
    $cls = $f['type'] === 'error' ? 'danger' : $f['type'];
    echo "<div class='alert alert-{$cls}'>" . e($f['message']) . "</div>";
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool
{
    return isset($_POST['csrf_token'])
        && hash_equals(csrf_token(), $_POST['csrf_token']);
}

function format_price(float|int|string $price): string
{
    return 'R$ ' . number_format((float)$price, 2, ',', '.');
}

function slugify(string $text): string
{
    $map = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'];
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function cart_count(): int
{
    global $pdo;
    if (!is_logged_in() || !isset($pdo)) return 0;
    try {
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(ci.quantity),0)
             FROM cart c
             JOIN cart_items ci ON ci.cart_id = c.id
             WHERE c.user_id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function get_or_create_cart(PDO $db, int $user_id): int
{
    $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchColumn();
    if ($cart) return (int)$cart;
    $db->prepare("INSERT INTO cart (user_id) VALUES (?)")->execute([$user_id]);
    return (int)$db->lastInsertId();
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'agora mesmo';
    if ($diff < 3600)  return floor($diff/60) . ' min atrás';
    if ($diff < 86400) return floor($diff/3600) . 'h atrás';
    return floor($diff/86400) . ' dias atrás';
}
