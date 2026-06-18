<?php
include 'db.php';

$sql = "
CREATE TABLE IF NOT EXISTS produto (
    idproduto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagem VARCHAR(500)
)";

try {
    $pdo->exec($sql);
    echo "<h2>Banco de dados criado com sucesso!</h2>";
    echo "<p>Tabela 'produto' criada.</p>";
    echo "<a href='../produtos.php'>Ir para Produtos</a>";
} catch (PDOException $e) {
    echo "<h2>Erro:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>