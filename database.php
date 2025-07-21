<?php

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL, -- 'income' or 'expense'
        description TEXT NOT NULL,
        amount REAL NOT NULL,
        date TEXT NOT NULL
    )");

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}