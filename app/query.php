<?php

declare(strict_types=1);

require_once __DIR__ . '/php/repository.php';

$queriesConfig = getQueriesConfig();
$selectedOption = $_POST['query_option'] ?? '';

$queryDescription = '';
$sqlQuery = '';
$results = [];
$columns = [];
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selectedOption)) {
    try {
        $data = getAnalyticsData($selectedOption);
        
        $queryDescription = $data['description'];
        $sqlQuery = $data['sql'];
        $results = $data['results'];
        $columns = $data['columns'];
        $errorMessage = $data['error'] ?? '';
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Выполнение SQL-запросов</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="assets/favicon.jpg">
</head>
<body>

<div class="card">

    <nav class="main-nav">
        <a href="index.php">Upload</a>
        <a href="query.php" class="active">Query</a>
        <a href="task.php">Task</a>
    </nav>

    <h2>Доступные аналитические запросы:</h2>
    <ul>
        <?php foreach ($queriesConfig as $key => $config): ?>
            <li><strong><?= $key ?>)</strong> <?= htmlspecialchars($config['description']) ?></li>
        <?php endforeach; ?>
    </ul>

    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

    <form action="query.php" method="POST">
        <div class="form-group">
            <label for="query_option">Выберите вариант запроса:</label><br><br>
            <select id="query_option" name="query_option" class="form-select">
                <option value="">-- Выберите из списка --</option>
                <?php foreach ($queriesConfig as $key => $config): ?>
                    <option value="<?= $key ?>" <?= $selectedOption === $key ? 'selected' : '' ?>><?= $key ?> - запрос</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn">Сформировать</button>
    </form>

    <?php if ($errorMessage): ?>
        <div class="alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($sqlQuery)): ?>
        <h3 style="margin-top: 30px;">Текст задания:</h3>
        <p class="task-description">
            <?= htmlspecialchars($queryDescription) ?>
        </p>

        <h3>Выполненный SQL-запрос:</h3>
        <div class="sql-code"><?= htmlspecialchars($sqlQuery) ?></div>

        <h3>Result запроса:</h3>
        <?php if (!empty($results)): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?= $value !== null ? htmlspecialchars((string)$value) : '<em>NULL</em>' ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">Запрос выполнен успешно, но совпадений в базе данных не найдено.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
