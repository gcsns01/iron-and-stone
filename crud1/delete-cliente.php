<?php
include 'db.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('DELETE FROM cliente WHERE idcliente = :id');
    $stmt->execute(['id' => $_GET['id']]);
}

header("Location: index-cliente.php");
exit();
?>