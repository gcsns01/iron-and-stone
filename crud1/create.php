<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $descricao = $_POST['descricao'];
    $stmt = $pdo->prepare('INSERT INTO produto (descricao) VALUES (:descricao)');
    $stmt->execute(['descricao' => $descricao]);
    
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Produto</title>
</head>
<body>
    <h1>Adicionar Produto</h1>
    <form method="post">
        <label for="descricao">Descrição:</label>
        <input type="text" id="descricao" name="descricao" required>
        <button type="submit">Salvar</button>
    </form>
</body>
</html>