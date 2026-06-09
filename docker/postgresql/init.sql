-- Создание таблицы клиентов
CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Создание таблицы товаров
CREATE TABLE IF NOT EXISTS merchandise (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price NUMERIC(10, 2) NOT NULL DEFAULT 0.00 
);

-- Создание таблицы заказов
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    item_id INT NOT NULL,
    customer_id INT NOT NULL,
    comment TEXT,
    status VARCHAR(20) DEFAULT 'new',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Внешние ключи для обеспечения целостности данных
    CONSTRAINT fk_merchandise FOREIGN KEY (item_id) REFERENCES merchandise(id) ON DELETE RESTRICT,
    CONSTRAINT fk_clients FOREIGN KEY (customer_id) REFERENCES clients(id) ON DELETE RESTRICT
);

-- --- ДОБАВЛЕНИЕ ИНДЕКСОВ ДЛЯ ОПТИМИЗАЦИИ JOIN ---

-- Индекс для ускорения выборок заказов по конкретному товару и JOIN с таблицей merchandise
CREATE INDEX IF NOT EXISTS idx_orders_item_id ON orders(item_id);

-- Индекс для ускорения выборок заказов конкретного клиента и JOIN с таблицей clients
CREATE INDEX IF NOT EXISTS idx_orders_customer_id ON orders(customer_id);

-- Заполнение тестовыми данными 
INSERT INTO clients (id, name) VALUES 
(1, 'Иван'), (2, 'Мария'), (3, 'Алексей'), (4, 'Ольга'), (5, 'Дмитрий'),
(6, 'Елена'), (7, 'Павел'), (8, 'Татьяна'), (9, 'Сергей'), (10, 'Наталья'),
(11, 'Антон'), (12, 'Ирина'), (13, 'Максим'), (14, 'Светлана'), (15, 'Артем'),
(16, 'Юлия')
ON CONFLICT (id) DO NOTHING;

INSERT INTO merchandise (id, name, price) VALUES 
(1, 'Ноутбук', 75000.00), (2, 'Телефон', 35000.00), (3, 'Наушники', 5000.00), 
(4, 'Клавиатура', 3500.00), (5, 'Мышь', 1500.00), (6, 'Монитор', 18000.00), 
(7, 'Принтер', 12000.00), (8, 'Роутер', 4500.00), (9, 'Кабель', 500.00), 
(10, 'Колонки', 7000.00), (11, 'Веб-камера', 3000.00), (12, 'Микрофон', 6000.00), 
(13, 'Коврик', 800.00), (14, 'Жесткий диск', 5500.00), (15, 'Флешка', 1200.00), 
(16, 'Переходник', 600.00), (17, 'Чехол', 1000.00), (18, 'Зарядка', 1500.00), 
(19, 'Батарейки', 300.00), (20, 'Смарт-часы', 14000.00)
ON CONFLICT (id) DO NOTHING;

-- Сброс счетчиков автоинкремента
SELECT setval('clients_id_seq', COALESCE((SELECT MAX(id) FROM clients), 1));
SELECT setval('merchandise_id_seq', COALESCE((SELECT MAX(id) FROM merchandise), 1));
