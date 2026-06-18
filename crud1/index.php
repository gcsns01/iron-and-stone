<?php
include 'db.php';

$stmt = $pdo->query('SELECT idproduto, descricao FROM produto');
$produtos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Lista de Produtos</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Descrição</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($produtos as $produto): ?>
        <tr>
            <td><?= htmlspecialchars($produto['idproduto']) ?></td>
            <td><?= htmlspecialchars($produto['descricao']) ?></td>
            <td>
                <a href="edit.php?id=<?= $produto['idproduto'] ?>">Editar</a>
                <a href="delete.php?id=<?= $produto['idproduto'] ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="create.php">Adicionar novo produto</a>
</body>
</html>