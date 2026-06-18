<?php
include 'db.php';

$id = $_GET['id'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $descricao = $_POST['descricao'];
    $stmt = $pdo->prepare('UPDATE produto SET descricao = :descricao WHERE idproduto = :id');
    $stmt->execute(['descricao' => $descricao, 'id' => $id]);

    header("Location: index.html");
    exit();
} else {
    $stmt = $pdo->prepare('SELECT idproduto, descricao FROM produto WHERE idproduto = :id');
    $stmt->execute(['id' => $id]);
    $produto = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
</head>
<body>
    <h1>Editar Produto</h1>
    <form method="post">
        <label for="descricao">Descrição:</label>
        <input type="text" id="descricao" name="descricao" required value="<?= htmlspecialchars($produto['descricao']) ?>">
        <button type="submit">Salvar Alterações</button>
    </form>
</body>
</html>