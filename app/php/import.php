<?php

declare(strict_types=1);

function runImport(string $filePath): array
{
    $dbHost = getenv('POSTGRES_HOST') ?: 'database'; 
    $dbName = getenv('POSTGRES_DB');
    $dbUser = getenv('POSTGRES_USER');
    $dbPass = getenv('POSTGRES_PASSWORD');

    $errorFile = dirname(__DIR__) . '/invalid_orders.txt';
    $csvSeparator = ';';

    if (!file_exists($filePath)) {
        throw new Exception("Входной файл не найден: " . basename($filePath));
    }

    $dsn = sprintf("pgsql:host=%s;dbname=%s", $dbHost, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $sql = "INSERT INTO orders (item_id, customer_id, comment, status, order_date) 
            VALUES (:item_id, :customer_id, :comment, :status, :order_date)";
    $stmt = $pdo->prepare($sql);

    $inputHandle = fopen($filePath, 'r');
    $errorHandle = fopen($errorFile, 'w');

    if ($inputHandle === false || $errorHandle === false) {
        throw new Exception("Не удалось открыть файлы для чтения/записи.");
    }

    $pdo->beginTransaction();

    $totalRows = 0;
    $successRows = 0;
    $errorRows = 0;

    // Допустимые статусы
    $allowedStatuses = ['new', 'complete'];

    while (($row = fgetcsv($inputHandle, 0, $csvSeparator, '"', '\\')) !== false) {
        $totalRows++;

        // 1. Валидация структуры: строго 5 колонок
        if (count($row) !== 5) {
            fputcsv($errorHandle, $row, $csvSeparator, '"', '\\');
            $errorRows++;
            continue;
        }

        [$itemId, $customerId, $comment, $status, $dateStr] = $row;

        // 2. Валидация числовых ID товара и клиента
        if (!filter_var($itemId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ||
            !filter_var($customerId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            fputcsv($errorHandle, $row, $csvSeparator, '"', '\\');
            $errorRows++;
            continue;
        }

        // 3. Валидация статуса
        $status = trim(strtolower((string)$status));
        if (!in_array($status, $allowedStatuses, true)) {
            fputcsv($errorHandle, $row, $csvSeparator, '"', '\\');
            $errorRows++;
            continue;
        }

        // 4. Проверка только корректности формата даты
        $dateStr = trim((string)$dateStr);
        $orderDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateStr);
        
        if ($orderDate === false) {
            $orderDate = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
        }

        if ($orderDate === false) {
            fputcsv($errorHandle, $row, $csvSeparator, '"', '\\');
            $errorRows++;
            continue;
        }

        $comment = trim((string)$comment);

        try {
            $stmt->execute([
                'item_id'     => (int)$itemId,
                'customer_id' => (int)$customerId,
                'comment'     => $comment !== '' ? $comment : null,
                'status'      => $status,
                'order_date'  => $orderDate->format('Y-m-d H:i:s'),
            ]);
            $successRows++;
        } catch (PDOException $e) {
            fputcsv($errorHandle, $row, $csvSeparator, '"', '\\');
            $errorRows++;
        }
    }

    $pdo->commit();

    fclose($inputHandle);
    fclose($errorHandle);

    return [
        'total'   => $totalRows,
        'success' => $successRows,
        'error'   => $errorRows,
        'file'    => basename($filePath)
    ];
}

// Запуск через CLI
if (php_sapi_name() === 'cli') {
    try {
        $defaultFile = dirname(__DIR__) . '/orders.txt';
        $result = runImport($defaultFile);
        echo "Обработка завершена через CLI.\n";
        echo "Файл: {$result['file']}\n";
        echo "Всего строк: {$result['total']}\n";
        echo "Успешно: {$result['success']}\n";
        echo "Ошибок: {$result['error']}\n";
    } catch (Exception $e) {
        echo "Критическая ошибка CLI: " . $e->getMessage() . "\n";
        exit(1);
    }
}