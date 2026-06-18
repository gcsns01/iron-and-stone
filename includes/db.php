<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'iron_stone');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    _run_migrations($pdo);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

function _run_migrations(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(255) NOT NULL,
        email      VARCHAR(255) UNIQUE NOT NULL,
        password   VARCHAR(255) NOT NULL,
        role       ENUM('admin','user') DEFAULT 'user',
        active     TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100) NOT NULL,
        slug       VARCHAR(100) UNIQUE NOT NULL,
        active     TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        name        VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        price       DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock       INT NOT NULL DEFAULT 0,
        active      TINYINT(1) DEFAULT 1,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS product_images (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_url  VARCHAR(500) NOT NULL,
        is_main    TINYINT(1) DEFAULT 0,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS cart (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        cart_id    INT NOT NULL,
        product_id INT NOT NULL,
        quantity   INT NOT NULL DEFAULT 1,
        UNIQUE KEY uq_cart_product (cart_id, product_id),
        FOREIGN KEY (cart_id)    REFERENCES cart(id)     ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        status     ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        total      DECIMAL(10,2) NOT NULL DEFAULT 0,
        notes      TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        order_id     INT NOT NULL,
        product_id   INT,
        product_name VARCHAR(255) NOT NULL,
        quantity     INT NOT NULL,
        price        DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(255) NOT NULL,
        email      VARCHAR(255) NOT NULL,
        sector     VARCHAR(100),
        message    TEXT NOT NULL,
        status     ENUM('unread','read','replied') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // legacy table kept for crud1/ files
    $db->exec("CREATE TABLE IF NOT EXISTS produto (
        idproduto INT AUTO_INCREMENT PRIMARY KEY,
        nome      VARCHAR(255) NOT NULL,
        descricao VARCHAR(255) NOT NULL,
        categoria VARCHAR(100),
        preco     DECIMAL(10,2) NOT NULL DEFAULT 0,
        imagem    VARCHAR(500)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Default admin
    $count = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'admin')");
        $stmt->execute(['Administrador', 'admin@ironstone.com', $hash]);
    }

    // Default categories
    $count = (int) $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($count === 0) {
        $cats = [
            ['Ferramentas',             'ferramentas'],
            ['Material de Construção',  'material-de-construcao'],
            ['Aço Estrutural',          'aco-estrutural'],
            ['Revestimento',            'revestimento'],
            ['Equipamento de Proteção', 'equipamento-de-protecao'],
        ];
        $stmt = $db->prepare("INSERT INTO categories (name,slug) VALUES (?,?)");
        foreach ($cats as [$name, $slug]) {
            $stmt->execute([$name, $slug]);
        }
    }

    // Migrate existing produto records to products once
    $hasProducts  = (int) $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $hasProduto   = (int) $db->query("SELECT COUNT(*) FROM produto")->fetchColumn();
    if ($hasProducts === 0 && $hasProduto > 0) {
        $rows = $db->query("SELECT * FROM produto")->fetchAll();
        $ins  = $db->prepare("INSERT INTO products (name,description,price,stock,active) VALUES (?,?,?,10,1)");
        $img  = $db->prepare("INSERT INTO product_images (product_id,image_url,is_main) VALUES (?,?,1)");
        foreach ($rows as $r) {
            $ins->execute([$r['nome'], $r['descricao'], $r['preco']]);
            $pid = $db->lastInsertId();
            if (!empty($r['imagem'])) {
                $img->execute([$pid, $r['imagem']]);
            }
        }
    }
}
