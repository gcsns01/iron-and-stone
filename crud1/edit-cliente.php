<?php
include 'db.php';

$id = $_GET['id'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $stmt = $pdo->prepare('UPDATE cliente SET nome = :nome WHERE idcliente = :id');
    $stmt->execute(['nome' => $nome, 'id' => $id]);

    header("Location: index-cliente.php");
    exit();
} else {
    $stmt = $pdo->prepare('SELECT idcliente, nome FROM cliente WHERE idcliente = :id');
    $stmt->execute(['id' => $id]);
    $cliente = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente</title>
</head>
<body>
    <h1>Editar Cliente</h1>
    <form method="post">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($cliente['nome']) ?>">
        <button type="submit">Salvar Alterações</button>
    </form>
</body>
</html>