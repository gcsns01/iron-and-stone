<?php
include 'db.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('DELETE FROM produto WHERE idproduto = :id');
    $stmt->execute(['id' => $_GET['id']]);
}

header("Location: ../produtos.php");
exit();
?>