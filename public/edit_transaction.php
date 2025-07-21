<?php
require_once __DIR__ . '/../database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Se o formulário for enviado, atualiza os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? '';
    $date = $_POST['date'] ?? '';

    $transactionDate = !empty($date) ? $date . ' 12:00:00' : date('Y-m-d H:i:s');

    if (!empty($description) && is_numeric($amount) && $amount > 0 && !empty($type)) {
        $stmt = $pdo->prepare(
            "UPDATE transactions SET type = ?, description = ?, amount = ?, date = ? WHERE id = ?"
        );
        $stmt->execute([$type, $description, $amount, $transactionDate, $id]);

        // Extrai o mês da data para redirecionar corretamente
        $month = date('Y-m', strtotime($transactionDate));
        header('Location: index.php?month=' . $month);
        exit;
    }
}

// Busca os dados atuais da transação para preencher o formulário
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Transação</title>
</head>
<body>
    <h1>Editar Transação</h1>

    <form method="POST">
        <div>
            <label for="description">Descrição:</label>
            <input type="text" id="description" name="description" value="<?= htmlspecialchars($transaction['description']) ?>" required>
        </div>
        <div>
            <label for="amount">Valor:</label>
            <input type="number" step="0.01" id="amount" name="amount" value="<?= $transaction['amount'] ?>" required>
        </div>
        <div>
            <label for="type">Tipo:</label>
            <select id="type" name="type" required>
                <option value="income" <?= $transaction['type'] === 'income' ? 'selected' : '' ?>>Entrada</option>
                <option value="expense" <?= $transaction['type'] === 'expense' ? 'selected' : '' ?>>Saída</option>
            </select>
        </div>
        <div>
            <label for="date">Data:</label>
            <input type="date" id="date" name="date" value="<?= date('Y-m-d', strtotime($transaction['date'])) ?>" required>
        </div>
        <button type="submit">Salvar Alterações</button>
        <a href="index.php?month=<?= date('Y-m', strtotime($transaction['date'])) ?>">Cancelar</a>
    </form>

</body>
</html>
