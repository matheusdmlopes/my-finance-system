<?php
require_once __DIR__ . '/../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? '';

    if (!empty($description) && is_numeric($amount) && $amount > 0 && !empty($type)) {
        $stmt = $pdo->prepare(
            "INSERT INTO transactions (type, description, amount, date) VALUES (?, ?, ?, ?)"
        );
        // Usando a data atual
        $stmt->execute([$type, $description, $amount, date('Y-m-d H:i:s')]);
    }
}

// Redireciona de volta para a página inicial
header('Location: index.php');
exit;
