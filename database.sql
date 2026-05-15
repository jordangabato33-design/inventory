-- ============================================================
-- INVENTORY MANAGEMENT SYSTEM - Computer Hardware Store
-- NEUST Case Study | AY 2025-2026 | Second Semester
-- Import this file in phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventory_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE inventory_db;

-- ============================================================
-- TABLE: users
-- Stores admin and staff accounts with Bcrypt passwords
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)             NOT NULL,
    username    VARCHAR(100)             NOT NULL UNIQUE,
    password    VARCHAR(255)             NOT NULL,  -- Bcrypt hashed
    role        ENUM('admin','staff')    NOT NULL DEFAULT 'staff',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: products
-- Stores all hardware items with brand, price, and stock
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    product_code        VARCHAR(20)     NOT NULL UNIQUE,
    name                VARCHAR(150)    NOT NULL,
    brand               VARCHAR(100)    DEFAULT NULL,
    category            VARCHAR(100)    DEFAULT NULL,
    unit                VARCHAR(50)     DEFAULT 'Piece',
    price               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    quantity            INT             NOT NULL DEFAULT 0,
    low_stock_threshold INT             NOT NULL DEFAULT 3,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: stock_in
-- Records all incoming stock transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_in (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    product_id    INT           NOT NULL,
    quantity      INT           NOT NULL,
    supplier      VARCHAR(150)  DEFAULT NULL,
    remarks       TEXT          DEFAULT NULL,
    date_received DATE          NOT NULL,
    created_by    INT           NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: stock_out
-- Records all outgoing stock transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_out (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    product_id    INT           NOT NULL,
    quantity      INT           NOT NULL,
    reason        VARCHAR(255)  DEFAULT NULL,
    remarks       TEXT          DEFAULT NULL,
    date_released DATE          NOT NULL,
    created_by    INT           NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- TRIGGER 1: Auto-INCREASE product stock after Stock-In
-- Fires automatically after every INSERT on stock_in
-- ============================================================
CREATE TRIGGER after_stock_in_insert
AFTER INSERT ON stock_in
FOR EACH ROW
UPDATE products
SET quantity   = quantity + NEW.quantity,
    updated_at = CURRENT_TIMESTAMP
WHERE id = NEW.product_id;

-- ============================================================
-- TRIGGER 2: Auto-DECREASE product stock after Stock-Out
-- Fires automatically after every INSERT on stock_out
-- ============================================================
CREATE TRIGGER after_stock_out_insert
AFTER INSERT ON stock_out
FOR EACH ROW
UPDATE products
SET quantity   = quantity - NEW.quantity,
    updated_at = CURRENT_TIMESTAMP
WHERE id = NEW.product_id;

-- ============================================================
-- STORED PROCEDURE 1: Get all low stock products
-- Call: CALL GetLowStockProducts();
-- ============================================================
CREATE PROCEDURE GetLowStockProducts()
BEGIN
    SELECT
        id,
        product_code,
        name,
        brand,
        category,
        quantity,
        low_stock_threshold
    FROM products
    WHERE quantity <= low_stock_threshold
    ORDER BY quantity ASC;
END;

-- ============================================================
-- STORED PROCEDURE 2: Get full transaction history of a product
-- Call: CALL GetProductHistory(1);
-- ============================================================
CREATE PROCEDURE GetProductHistory(IN prod_id INT)
BEGIN
    SELECT
        'Stock-In'         AS transaction_type,
        si.quantity,
        si.supplier        AS reference,
        si.remarks,
        si.date_received   AS transaction_date,
        u.name             AS handled_by
    FROM stock_in si
    JOIN users u ON si.created_by = u.id
    WHERE si.product_id = prod_id

    UNION ALL

    SELECT
        'Stock-Out',
        so.quantity,
        so.reason,
        so.remarks,
        so.date_released,
        u.name
    FROM stock_out so
    JOIN users u ON so.created_by = u.id
    WHERE so.product_id = prod_id

    ORDER BY transaction_date DESC;
END;

-- ============================================================
-- STORED PROCEDURE 3: Dashboard summary counts
-- Call: CALL GetDashboardSummary();
-- ============================================================
CREATE PROCEDURE GetDashboardSummary()
BEGIN
    SELECT
        (SELECT COUNT(*)
            FROM products)                                                     AS total_products,
        (SELECT COALESCE(SUM(quantity),0)
            FROM stock_in  WHERE DATE(date_received) = CURDATE())              AS stock_in_today,
        (SELECT COALESCE(SUM(quantity),0)
            FROM stock_out WHERE DATE(date_released) = CURDATE())              AS stock_out_today,
        (SELECT COUNT(*)
            FROM products WHERE quantity <= low_stock_threshold)               AS low_stock_count,
        (SELECT COUNT(*)
            FROM products WHERE quantity = 0)                                  AS out_of_stock_count;
END;

-- ============================================================
-- DEFAULT USERS
-- Password for both accounts: password
-- (Hashed with Bcrypt via password_hash($pass, PASSWORD_BCRYPT))
-- ============================================================
INSERT INTO users (name, username, password, role) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Staff User',    'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- ============================================================
-- SAMPLE PRODUCTS — Computer Hardware Store
-- ============================================================
INSERT INTO products (product_code,name,brand,category,unit,price,quantity,low_stock_threshold) VALUES
('EC-001','RTX 4060 8GB GPU',           'ASUS',          'GPU',         'Piece', 18500.00,  5, 2),
('EC-002','RTX 4070 Super 12GB',        'MSI',           'GPU',         'Piece', 32000.00,  3, 2),
('EC-003','RX 7600 8GB GPU',            'Sapphire',      'GPU',         'Piece', 14800.00,  0, 2),
('EC-004','Intel Core i5-13400F',       'Intel',         'CPU',         'Piece',  9200.00,  1, 2),
('EC-005','Intel Core i7-13700K',       'Intel',         'CPU',         'Piece', 18900.00,  4, 2),
('EC-006','AMD Ryzen 5 7600X',          'AMD',           'CPU',         'Piece', 12500.00,  2, 3),
('EC-007','AMD Ryzen 9 7900X',          'AMD',           'CPU',         'Piece', 24000.00,  1, 2),
('EC-008','DDR4 16GB 3200MHz',          'Kingston',      'RAM',         'Piece',  2100.00,  0, 3),
('EC-009','DDR5 32GB 5600MHz',          'Corsair',       'RAM',         'Piece',  5800.00,  6, 2),
('EC-010','DDR4 8GB 2666MHz',           'TeamGroup',     'RAM',         'Piece',  1100.00, 10, 3),
('EC-011','1TB NVMe SSD',               'Samsung',       'Storage',     'Piece',  3800.00,  8, 3),
('EC-012','2TB NVMe SSD',               'Western Digital','Storage',    'Piece',  6200.00,  4, 2),
('EC-013','4TB HDD 7200RPM',            'Seagate',       'Storage',     'Piece',  3500.00,  7, 2),
('EC-014','B660M Motherboard',          'MSI',           'Motherboard', 'Piece',  6500.00,  3, 2),
('EC-015','Z790 Gaming Motherboard',    'ASUS ROG',      'Motherboard', 'Piece', 14500.00,  2, 2),
('EC-016','650W 80+ Bronze PSU',        'Corsair',       'PSU',         'Piece',  3200.00,  2, 2),
('EC-017','850W 80+ Gold PSU',          'Seasonic',      'PSU',         'Piece',  5800.00,  4, 2),
('EC-018','240mm AIO Cooler',           'Deepcool',      'Cooling',     'Piece',  2800.00,  4, 2),
('EC-019','360mm AIO Cooler',           'Corsair',       'Cooling',     'Piece',  5500.00,  2, 2),
('EC-020','Mechanical Keyboard TKL',    'Keychron',      'Keyboard',    'Piece',  1950.00,  6, 3),
('EC-021','Gaming Mouse 12K DPI',       'Logitech',      'Mouse',       'Piece',  1600.00,  0, 3),
('EC-022','27" IPS FHD Monitor',        'LG',            'Monitor',     'Piece',  8500.00,  5, 2),
('EC-023','24" 144Hz Gaming Monitor',   'ASUS',          'Monitor',     'Piece', 11200.00,  3, 2),
('EC-024','USB-C Hub 7-in-1',           'Anker',         'Accessories', 'Piece',   850.00, 12, 5),
('EC-025','Cat6 Ethernet Cable 5m',     'TP-Link',       'Networking',  'Piece',   280.00, 20, 5),
('EC-026','Gaming Headset 7.1',         'HyperX',        'Headphone',   'Piece',  2900.00,  4, 2),
('EC-027','Bluetooth Speaker 20W',      'JBL',           'Speaker',     'Piece',  3200.00,  3, 2),
('EC-028','Wireless Laser Printer',     'HP',            'Printer',     'Unit',  12500.00,  2, 1),
('EC-029','Gaming Laptop RTX 4060',     'ASUS ROG',      'Laptop',      'Unit',  75000.00,  2, 1),
('EC-030','Business Laptop i7',         'Lenovo',        'Laptop',      'Unit',  55000.00,  3, 1);
