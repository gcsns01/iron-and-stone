<?php
include 'db.php';

$stmt = $pdo->query('SELECT idcliente, nome FROM cliente');
$clientes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Lista de Clientes</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($clientes as $cliente): ?>
        <tr>
            <td><?= htmlspecialchars($cliente['idcliente']) ?></td>
            <td><?= htmlspecialchars($cliente['nome']) ?></td>
            <td>
                <a href="edit-cliente.php?id=<?= $cliente['idcliente'] ?>">Editar</a>
                <a href="delete-cliente.php?id=<?= $cliente['idcliente'] ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="create-cliente.php">Adicionar novo cliente</a>
</body>
</html>