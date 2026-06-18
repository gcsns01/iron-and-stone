CREATE DATABASE IF NOT EXISTS exemploCRUD;
USE exemploCRUD;

CREATE TABLE produto (
    idproduto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    preco DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(500)
);
