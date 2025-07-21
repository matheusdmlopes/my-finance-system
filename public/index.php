<?php
require_once __DIR__ . '/../database.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Financeiro</title>
</head>
<body>
    <h1>Meu Painel Financeiro</h1>

    <hr>

    <h2>Adicionar Nova Transação</h2>
    <form action="add_transaction.php" method="POST">
        <div>
            <label for="description">Descrição:</label>
            <input type="text" id="description" name="description" required>
        </div>
        <div>
            <label for="amount">Valor:</label>
            <input type="number" step="0.01" id="amount" name="amount" required>
        </div>
        <div>
            <label for="type">Tipo:</label>
            <select id="type" name="type" required>
                <option value="income">Entrada</option>
                <option value="expense">Saída</option>
            </select>
        </div>
        <div>
            <label for="date">Data (opcional):</label>
            <input type="date" id="date" name="date">
        </div>
        <button type="submit">Adicionar</button>
    </form>

    <hr>

    <?php
    // --- Lógica de Seleção de Mês ---
    $currentMonth = $_GET['month'] ?? date('Y-m');
    $currentDate = new DateTime($currentMonth . '-01');
    $monthName = $currentDate->format('F'); // Nome do mês em inglês
    $year = $currentDate->format('Y');

    // Tradução manual simples para o nome do mês
    $monthTranslations = [
        'January' => 'Janeiro', 'February' => 'Fevereiro', 'March' => 'Março',
        'April' => 'Abril', 'May' => 'Maio', 'June' => 'Junho',
        'July' => 'Julho', 'August' => 'Agosto', 'September' => 'Setembro',
        'October' => 'Outubro', 'November' => 'Novembro', 'December' => 'Dezembro'
    ];
    $monthNamePortuguese = $monthTranslations[$monthName];

    // --- Navegação entre meses ---
    $prevMonth = (clone $currentDate)->modify('-1 month')->format('Y-m');
    $nextMonth = (clone $currentDate)->modify('+1 month')->format('Y-m');
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
        <a href="?month=<?= $prevMonth ?>">&lt; Mês Anterior</a>
        <h2>Transações de <?= $monthNamePortuguese ?> de <?= $year ?></h2>
        <a href="?month=<?= $nextMonth ?>">Mês Seguinte &gt;</a>
    </div>

    <?php
    // --- Busca e Cálculo das Transações do Mês ---
    $stmt = $pdo->prepare(
        "SELECT id, type, description, amount, date FROM transactions WHERE strftime('%Y-%m', date) = ? ORDER BY date DESC"
    );
    $stmt->execute([$currentMonth]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcula o saldo do mês
    $balance = 0;
    foreach ($transactions as $transaction) {
        if ($transaction['type'] === 'income') {
            $balance += $transaction['amount'];
        } else {
            $balance -= $transaction['amount'];
        }
    }
    ?>

    <h3>Saldo do Mês: <span style="color: <?= $balance >= 0 ? 'green' : 'red' ?>;">R$ <?= number_format($balance, 2, ',', '.') ?></span></h3>

    <table border="1" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="padding: 8px;">Descrição</th>
                <th style="padding: 8px;">Valor (R$)</th>
                <th style="padding: 8px;">Tipo</th>
                <th style="padding: 8px;">Data</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding: 8px;">Nenhuma transação encontrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td style="padding: 8px;"><?= htmlspecialchars($transaction['description']) ?></td>
                        <td style="padding: 8px; color: <?= $transaction['type'] === 'income' ? 'green' : 'red' ?>;">
                            <?= number_format($transaction['amount'], 2, ',', '.') ?>
                        </td>
                        <td style="padding: 8px;"><?= $transaction['type'] === 'income' ? 'Entrada' : 'Saída' ?></td>
                        <td style="padding: 8px;"><?= date('d/m/Y', strtotime($transaction['date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>