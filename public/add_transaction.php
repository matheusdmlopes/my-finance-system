<?php
require_once __DIR__ . '/../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? '';
    $date = $_POST['date'] ?? '';

    // Se a data não for fornecida, usa a data/hora atual.
    // Se for fornecida, usa a data com a hora definida como meio-dia para consistência.
    $transactionDate = !empty($date) ? $date . ' 12:00:00' : date('Y-m-d H:i:s');

    if (!empty($description) && is_numeric($amount) && $amount > 0 && !empty($type)) {
        $stmt = $pdo->prepare(
            "INSERT INTO transactions (type, description, amount, date) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$type, $description, $amount, $transactionDate]);
    }
}

// Redireciona de volta para a página inicial
header('Location: index.php');
exit;
