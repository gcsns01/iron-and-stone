CREATE DATABASE IF NOT EXISTS iron_stone;
USE iron_stone;

CREATE TABLE IF NOT EXISTS produto (
    idproduto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagem VARCHAR(500)
);

INSERT INTO produto (nome, descricao, categoria, preco, imagem) VALUES
('Martelo Unha Stanley', 'Ferramenta Profissional', 'Ferramentas', 189.90, 'assets/img/martelo.png'),
('Parafusos Auto-Brocantes', 'Pacote 100 unidades', 'Ferramentas de Fixação', 49.90, 'assets/img/parafusos.png'),
('Cimento Portland CP-II', 'Saco 50 kg', 'Material de Construção', 55.39, 'assets/img/cimento.png'),
('Vergalhão de aço CA-50', 'Bitola 12.5mm, 12m', 'Aço Estrutural', 115.00, 'assets/img/aco.png'),
('Porcelanato Retificado', 'Cimento Queimado 80x80', 'Revestimento', 98.00, 'assets/img/revestimento.png'),
('Capacete de Segurança', 'Aba frontal', 'Equipamento de Proteção', 65.00, 'assets/img/capacete.png');