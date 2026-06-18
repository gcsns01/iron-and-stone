<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $stmt = $pdo->prepare('INSERT INTO cliente (nome) VALUES (:nome)');
    $stmt->execute(['nome' => $nome]);
    
    header("Location: index-cliente.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Cliente</title>
</head>
<body>
    <h1>Adicionar Cliente</h1>
    <form method="post">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        <button type="submit">Salvar</button>
    </form>
</body>
</html>