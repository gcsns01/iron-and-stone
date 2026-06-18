<?php
include 'db.php';

$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$categoria = $_POST['categoria'];
$preco = $_POST['preco'];
$imagem = $_POST['imagem'];
$id = $_POST['idproduto'];

if ($id) {
    $stmt = $pdo->prepare('UPDATE produto SET nome=:nome, descricao=:descricao, categoria=:categoria, preco=:preco, imagem=:imagem WHERE idproduto=:id');
    $stmt->execute(['nome'=>$nome, 'descricao'=>$descricao, 'categoria'=>$categoria, 'preco'=>$preco, 'imagem'=>$imagem, 'id'=>$id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO produto (nome, descricao, categoria, preco, imagem) VALUES (:nome, :descricao, :categoria, :preco, :imagem)');
    $stmt->execute(['nome'=>$nome, 'descricao'=>$descricao, 'categoria'=>$categoria, 'preco'=>$preco, 'imagem'=>$imagem]);
}

header("Location: ../produtos.php");
exit();
?>