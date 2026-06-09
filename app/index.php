<?php

declare(strict_types=1);

// Подключаем файл с логикой импорта
require_once __DIR__ . '/php/import.php';

$message = '';
$resultData = null;
$isError = false;
$invalidRows = []; // Массив для хранения ошибочных строк

// Проверяем, была ли отправлена форма
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Проверяем, загрузил ли пользователь файл И что загрузка прошла без ошибок
        if (isset($_FILES['orders_file']) && $_FILES['orders_file']['error'] === UPLOAD_ERR_OK) {
            $targetFile = $_FILES['orders_file']['tmp_name'];
        } else {
            $targetFile = __DIR__ . '/orders.txt';
        }

        // Запускаем импорт
        $resultData = runImport($targetFile);
        
        // Переопределяем имя файла для красивого вывода в интерфейсе
        if (isset($_FILES['orders_file']) && $_FILES['orders_file']['error'] === UPLOAD_ERR_OK) {
            $resultData['file'] = htmlspecialchars($_FILES['orders_file']['name']);
        } else {
            $resultData['file'] = 'orders.txt (по умолчанию)';
        }

        // Читаем файл с ошибками, если они есть
        $errorFile = __DIR__ . '/invalid_orders.txt';
        if (file_exists($errorFile) && filesize($errorFile) > 0) {
            $errorHandle = fopen($errorFile, 'r');
            if ($errorHandle !== false) {
                while (($row = fgetcsv($errorHandle, 0, ';', '"', '\\')) !== false) {
                    $invalidRows[] = implode(' ; ', $row);
                }
                fclose($errorHandle);
            }
        }

        $message = "Импорт успешно завершен!";
    } catch (Exception $e) {
        $isError = true;
        $message = "Ошибка при импорте: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Импорт заказов PostgreSQL</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="assets/favicon.jpg">
</head>
<body>

<div class="card">

    <nav class="main-nav">
        <a href="index.php" class="active">Upload</a>
        <a href="query.php">Query</a>
        <a href="task.php">Task</a>
    </nav>

    <h2>Загрузка заказов в БД</h2>
    
    <?php if ($message): ?>
        <div class="alert <?= $isError ? 'alert-danger' : 'alert-success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($resultData): ?>
        <div class="stats">
            <strong>Результаты обработки:</strong>
            <ul>
                <li><strong>Использован файл:</strong> <?= htmlspecialchars($resultData['file']) ?></li>
                <li><strong>Всего строк в файле:</strong> <?= $resultData['total'] ?></li>
                <li><strong>Успешно импортировано:</strong> <?= $resultData['success'] ?></li>
                <li><strong>Записано в невалидные:</strong> <?= $resultData['error'] ?></li>
            </ul>
        </div>

        <!-- Секция со списком невалидных строк -->
        <?php if (!empty($invalidRows)): ?>
            <div class="errors-list">
                <strong>Список невалидных строк из файла ошибок:</strong>
                <ul>
                    <?php foreach ($invalidRows as $badRow): ?>
                        <li><?= htmlspecialchars($badRow) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">
    <?php endif; ?>

    <form action="index.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="orders_file">Выберите файл заказа (CSV/TXT с разделителем ";"):</label><br><br>
            <input type="file" id="orders_file" name="orders_file" accept=".txt,.csv">
            <br><small style="color: #6c757d;">Если файл не выбран, автоматически импортируется локальный orders.txt</small>
        </div>
        
        <button type="submit" class="btn btn-upload">Запустить Import</button>
    </form>
</div>

</body>
</html>
