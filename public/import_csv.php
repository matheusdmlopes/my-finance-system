<?php
require_once __DIR__ . '/../database.php';

function detectCsvType($headers) {
    // Cartão: date, title, amount
    if (count($headers) == 3 && 
        (in_array('date', $headers) || in_array('Date', $headers)) &&
        (in_array('title', $headers) || in_array('Title', $headers)) &&
        (in_array('amount', $headers) || in_array('Amount', $headers))) {
        return 'cartao';
    }
    
    // Extrato: Data, Valor, Identificador, Descrição (ignoramos identificador)
    if (count($headers) >= 3 && 
        (in_array('Data', $headers) || in_array('data', $headers)) &&
        (in_array('Valor', $headers) || in_array('valor', $headers)) &&
        (in_array('Descrição', $headers) || in_array('descrição', $headers) || in_array('Descricao', $headers))) {
        return 'extrato';
    }
    
    return 'unknown';
}

function parseDate($dateStr, $type) {
    if ($type === 'cartao') {
        // Já vem no formato YYYY-MM-DD
        return $dateStr;
    } else {
        // Converter DD/MM/YYYY para YYYY-MM-DD
        $date = DateTime::createFromFormat('d/m/Y', $dateStr);
        return $date ? $date->format('Y-m-d') : null;
    }
}

function cleanAmount($amountStr) {
    // Remove espaços e converte vírgula para ponto
    $cleaned = str_replace([' ', ','], ['', '.'], trim($amountStr));
    return floatval($cleaned);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: index.php?error=upload');
        exit;
    }
    
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        header('Location: index.php?error=file');
        exit;
    }
    
    // Tentar diferentes separadores
    $separators = [",", "\t", ";"];
    $headers = null;
    $separator = ",";
    
    foreach ($separators as $sep) {
        rewind($handle);
        $testHeaders = fgetcsv($handle, 1000, $sep);
        if ($testHeaders && count($testHeaders) > 1) {
            $headers = $testHeaders;
            $separator = $sep;
            break;
        }
    }
    
    if (!$headers) {
        fclose($handle);
        header('Location: index.php?error=format');
        exit;
    }
    
    // Detectar tipo do CSV
    $csvType = detectCsvType($headers);
    if ($csvType === 'unknown') {
        fclose($handle);
        header('Location: index.php?error=unknown_format');
        exit;
    }
    
    $imported = 0;
    $errors = 0;
    
    // Processar linhas
    while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
        // Pular linhas vazias
        if (empty($row) || (count($row) == 1 && empty(trim($row[0])))) {
            continue;
        }
        
        try {
            if ($csvType === 'cartao') {
                // Cartão: date, title, amount
                $date = parseDate($row[0], 'cartao');
                $description = trim($row[1]);
                $amount = cleanAmount($row[2]);
                
                // Se valor é negativo, é pagamento recebido (income)
                // Se positivo, é despesa (expense)
                $type = $amount < 0 ? 'income' : 'expense';
                $amount = abs($amount); // Sempre positivo no banco
                
            } else { // extrato
                // Extrato: Data, Valor, Identificador, Descrição
                $date = parseDate($row[0], 'extrato');
                $amount = cleanAmount($row[1]);
                $description = trim($row[3]); // Pula o identificador
                
                // Determinar tipo baseado no sinal
                $type = $amount >= 0 ? 'income' : 'expense';
                $amount = abs($amount); // Sempre positivo no banco
            }
            
            // Validar dados
            if (!$date || empty($description) || $amount == 0) {
                $errors++;
                continue;
            }
            
            // Inserir no banco
            $stmt = $pdo->prepare(
                "INSERT INTO transactions (type, description, amount, date) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$type, $description, $amount, $date . ' 12:00:00']);
            $imported++;
            
        } catch (Exception $e) {
            $errors++;
        }
    }
    
    fclose($handle);
    header("Location: index.php?imported=$imported&errors=$errors");
    exit;
}

// Se não for POST, redireciona
header('Location: index.php');
exit;