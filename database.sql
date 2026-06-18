-- Estrutura do Banco de Dados - Iron & Stone

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','user') DEFAULT 'user',
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `is_main` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  UNIQUE KEY `uq_cart_product` (`cart_id`, `product_id`),
  FOREIGN KEY (`cart_id`) REFERENCES `cart`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `status` ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `sector` VARCHAR(100),
  `message` TEXT NOT NULL,
  `status` ENUM('unread','read','replied') DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `produto` (
  `idproduto` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `categoria` VARCHAR(100),
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `imagem` VARCHAR(500)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carga de dados inicial

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'Ferramentas', 'ferramentas'),
(2, 'Material de Construção', 'material-de-construcao'),
(3, 'Aço Estrutural', 'aco-estrutural'),
(4, 'Revestimento', 'revestimento'),
(5, 'Equipamento de Proteção', 'equipamento-de-protecao')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Administrador', 'admin@ironstone.com', '$2y$10$YCo27y9vW8xXk5g0x2wW6uvJc.H5K5qZ00yFm.oTq4O0yB1oE4Fwq', 'admin')
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`) VALUES
(1, 1, 'Martelo Unha Stanley - Cabo de Madeira', 'Martelo de alta resistência ideal para fixações e reformas em madeira.', 189.90, 10),
(2, 1, 'Parafusos Auto-Brocantes (pacote 100)', 'Fixadores de excelente qualidade para estruturas metálicas e coberturas.', 49.90, 25),
(3, 2, 'Cimento Portland CP-II (50 kg)', 'Cimento ideal para obras de alvenaria e concreto em geral.', 55.39, 50),
(4, 3, 'Vergalhão de aço CA-50 (12.5mm, 12m)', 'Aço estrutural de excelente qualidade para pilares, vigas e lajes.', 115.00, 15),
(5, 4, 'Porcelanato Retificado - Cimento Queimado (80x80)', 'Revestimento cerâmico premium com acabamento moderno e duradouro.', 98.00, 30),
(6, 5, 'Capacete de Segurança - Aba frontal', 'Equipamento de proteção individual com regulagem confortável.', 65.00, 20)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `product_images` (`product_id`, `image_url`, `is_main`) VALUES
(1, 'assets/img/martelo.png', 1),
(2, 'assets/img/parafusos.png', 1),
(3, 'assets/img/cimento.png', 1),
(4, 'assets/img/aco.png', 1),
(5, 'assets/img/revestimento.png', 1),
(6, 'assets/img/capacete.png', 1)
ON DUPLICATE KEY UPDATE `image_url`=VALUES(`image_url`);
