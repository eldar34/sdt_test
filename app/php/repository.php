<?php

declare(strict_types=1);

/**
 * Возвращает конфигурационный массив всех доступных аналитических запросов.
 */
function getQueriesConfig(): array
{
    return [
        '1' => [
            'description' => 'Выбрать имена (name) всех клиентов, которые не делали заказы в последние 7 дней.',
            'sql' => "SELECT c.id, c.name 
                      FROM clients c 
                      LEFT JOIN orders o ON o.customer_id = c.id 
                                        AND o.order_date >= NOW() - INTERVAL '7 days'
                      WHERE o.id IS NULL
                      ORDER BY c.id ASC"
        ],
        '2' => [
            'description' => 'Выбрать имена (name) 5 клиентов, которые сделали больше всего заказов в магазине.',
            'sql' => "SELECT c.id, c.name, COUNT(o.id) as total_orders
                      FROM clients c
                      JOIN orders o ON o.customer_id = c.id
                      GROUP BY c.id, c.name
                      ORDER BY total_orders DESC, c.name ASC
                      LIMIT 5"
        ],
        '3' => [
            'description' => 'Выбрать имена (name) 10 клиентов, которые сделали заказы на наибольшую сумму.',
            'sql' => "SELECT c.id, c.name, SUM(m.price) as total_spent
                      FROM clients c
                      JOIN orders o ON o.customer_id = c.id
                      JOIN merchandise m ON o.item_id = m.id
                      GROUP BY c.id, c.name
                      ORDER BY total_spent DESC, c.name ASC
                      LIMIT 10"
        ],
        '4' => [
            'description' => 'Выбрать имена (name) всех товаров, по которым не было доставленных заказов (со статусом “complete”).',
            'sql' => "SELECT m.id, m.name, m.price
                      FROM merchandise m
                      LEFT JOIN orders o ON o.item_id = m.id 
                                        AND o.status != 'complete'
                      WHERE o.id IS NULL
                      ORDER BY m.id ASC"
        ]
    ];
}

/**
 * Возвращает массив с результатами, колонками и SQL-кодом, либо генерирует ошибку.
 */
function getAnalyticsData(string $option): array
{
    $config = getQueriesConfig();
    
    if (!isset($config[$option])) {
        throw new InvalidArgumentException("Указан некорректный вариант запроса.");
    }

    $dbHost = getenv('POSTGRES_HOST') ?: 'database'; 
    $dbName = getenv('POSTGRES_DB');
    $dbUser = getenv('POSTGRES_USER');
    $dbPass = getenv('POSTGRES_PASSWORD');

    $sqlQuery = $config[$option]['sql'];

    try {
        $dsn = sprintf("pgsql:host=%s;dbname=%s", $dbHost, $dbName);
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $stmt = $pdo->query($sqlQuery);
        $results = $stmt->fetchAll();
        $columns = !empty($results) ? array_keys($results[0]) : [];

        return [
            'description'=> $config[$option]['description'],
            'sql'     => $sqlQuery,
            'results' => $results,
            'columns' => $columns,
            'error'   => null
        ];
    } catch (PDOException $e) {
        return [
            'description'=> $config[$option]['description'],
            'sql'     => $sqlQuery,
            'results' => [],
            'columns' => [],
            'error'   => "Ошибка базы данных: " . $e->getMessage()
        ];
    }
}
