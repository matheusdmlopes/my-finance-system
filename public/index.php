<?php
require_once __DIR__ . '/../database.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Financeiro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Meu Painel Financeiro</h1>

        <?php
        // Exibir mensagens de feedback do import
        if (isset($_GET['imported'])) {
            $imported = (int)$_GET['imported'];
            $errors = (int)($_GET['errors'] ?? 0);
            echo "<div class='import-feedback success'>";
            echo "✅ $imported transações importadas com sucesso!";
            if ($errors > 0) {
                echo " ($errors linhas com erro foram ignoradas)";
            }
            echo "</div>";
        } elseif (isset($_GET['error'])) {
            $error = $_GET['error'];
            echo "<div class='import-feedback error'>";
            switch ($error) {
                case 'upload':
                    echo "❌ Erro no upload do arquivo.";
                    break;
                case 'file':
                    echo "❌ Não foi possível ler o arquivo.";
                    break;
                case 'format':
                    echo "❌ Formato do CSV inválido.";
                    break;
                case 'unknown_format':
                    echo "❌ Formato do CSV não reconhecido. Verifique se é um arquivo de cartão ou extrato válido.";
                    break;
                default:
                    echo "❌ Erro desconhecido na importação.";
            }
            echo "</div>";
        }
        ?>

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

        <h2>Importar Transações via CSV</h2>
        <form action="import_csv.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="csv_file">Arquivo CSV (Cartão ou Extrato):</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            <button type="submit">Importar CSV</button>
        </form>

        <hr>

        <?php
        // --- Parâmetros de URL ---
        $currentMonth = $_GET['month'] ?? date('Y-m');
        $sortBy = $_GET['sort'] ?? 'date';
        $sortOrder = $_GET['order'] ?? 'DESC';
        $filterType = $_GET['filter'] ?? 'all';
        
        // --- Lógica de Seleção de Mês ---
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

        <div class="month-navigation">
            <a href="?month=<?= $prevMonth ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>&filter=<?= $filterType ?>">&lt; Mês Anterior</a>
            <h2><?= $monthNamePortuguese ?> de <?= $year ?></h2>
            <a href="?month=<?= $nextMonth ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>&filter=<?= $filterType ?>">Mês Seguinte &gt;</a>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <label for="type-filter">Filtrar por tipo:</label>
            <select id="type-filter" onchange="updateFilter()">
                <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="income" <?= $filterType === 'income' ? 'selected' : '' ?>>Apenas Entradas</option>
                <option value="expense" <?= $filterType === 'expense' ? 'selected' : '' ?>>Apenas Saídas</option>
            </select>
        </div>

        <?php
        // --- Busca e Cálculo das Transações do Mês ---
        
        // Construir query com filtros e ordenação
        $whereClause = "strftime('%Y-%m', date) = ?";
        $params = [$currentMonth];
        
        if ($filterType !== 'all') {
            $whereClause .= " AND type = ?";
            $params[] = $filterType;
        }
        
        // Mapear colunas de ordenação
        $validSortColumns = [
            'description' => 'description',
            'amount' => 'amount', 
            'type' => 'type',
            'date' => 'date'
        ];
        
        $sortColumn = isset($validSortColumns[$sortBy]) ? $validSortColumns[$sortBy] : 'date';
        $order = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';
        
        $query = "SELECT id, type, description, amount, date FROM transactions WHERE $whereClause ORDER BY $sortColumn $order";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
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

        <h3 class="balance">Saldo do Mês: <span class="<?= $balance >= 0 ? 'text-income' : 'text-expense' ?>">R$ <?= number_format($balance, 2, ',', '.') ?></span></h3>

        <table class="transactions-table">
            <thead>
                <tr>
                    <?php
                    // Função para gerar links de ordenação
                    function getSortLink($column, $currentSort, $currentOrder, $currentMonth, $filterType) {
                        $newOrder = ($currentSort === $column && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
                        $arrow = '';
                        if ($currentSort === $column) {
                            $arrow = $currentOrder === 'DESC' ? ' ↓' : ' ↑';
                        }
                        return "?month=$currentMonth&sort=$column&order=$newOrder&filter=$filterType";
                    }
                    ?>
                    <th><a href="<?= getSortLink('description', $sortBy, $sortOrder, $currentMonth, $filterType) ?>">Descrição<?= $sortBy === 'description' ? ($sortOrder === 'DESC' ? ' ↓' : ' ↑') : '' ?></a></th>
                    <th><a href="<?= getSortLink('amount', $sortBy, $sortOrder, $currentMonth, $filterType) ?>">Valor (R$)<?= $sortBy === 'amount' ? ($sortOrder === 'DESC' ? ' ↓' : ' ↑') : '' ?></a></th>
                    <th><a href="<?= getSortLink('type', $sortBy, $sortOrder, $currentMonth, $filterType) ?>">Tipo<?= $sortBy === 'type' ? ($sortOrder === 'DESC' ? ' ↓' : ' ↑') : '' ?></a></th>
                    <th><a href="<?= getSortLink('date', $sortBy, $sortOrder, $currentMonth, $filterType) ?>">Data<?= $sortBy === 'date' ? ($sortOrder === 'DESC' ? ' ↓' : ' ↑') : '' ?></a></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">Nenhuma transação encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                            <td class="<?= $transaction['type'] === 'income' ? 'text-income' : 'text-expense' ?>">
                                <?= number_format($transaction['amount'], 2, ',', '.') ?>
                            </td>
                            <td><?= $transaction['type'] === 'income' ? 'Entrada' : 'Saída' ?></td>
                            <td><?= date('d/m/Y', strtotime($transaction['date'])) ?></td>
                            <td class="actions">
                                <a href="edit_transaction.php?id=<?= $transaction['id'] ?>">Editar</a>
                                <a href="delete_transaction.php?id=<?= $transaction['id'] ?>" class="delete" onclick="return confirm('Tem certeza que deseja excluir esta transação?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function updateFilter() {
        const filter = document.getElementById('type-filter').value;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('filter', filter);
        urlParams.delete('sort');
        urlParams.delete('order');
        window.location.search = urlParams.toString();
    }
    </script>
</body>
</html>
