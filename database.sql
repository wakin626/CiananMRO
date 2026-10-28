-- CIANAN MRO Database Schema
-- Run this inside phpMyAdmin after creating the database `cianan_mro`

CREATE DATABASE IF NOT EXISTS cianan_mro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cianan_mro;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
    quantity_on_hand INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE stock_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('STOCK_IN','STOCK_OUT') NOT NULL,
    quantity INT NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tx_item
        FOREIGN KEY (item_id) REFERENCES items(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_tx_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE tool_loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    borrower_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    loaned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE DEFAULT NULL,
    returned_at DATETIME DEFAULT NULL,
    status ENUM('LOANED','RETURNED','OVERDUE') NOT NULL DEFAULT 'LOANED',
    notes VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_loan_item
        FOREIGN KEY (item_id) REFERENCES items(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_loan_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE consumables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(150) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    supplier VARCHAR(150) DEFAULT NULL,
    recommended_qty INT NOT NULL DEFAULT 0,
    low_level_qty INT NOT NULL DEFAULT 0,
    actual_stock_qty INT NOT NULL DEFAULT 0,
    request_qty INT NOT NULL DEFAULT 0,
    uom VARCHAR(30) NOT NULL DEFAULT 'pcs',
    machine_equipment VARCHAR(150) DEFAULT NULL,
    price_per_piece DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('IN STOCK','LOW STOCK','OUT OF STOCK') NOT NULL DEFAULT 'IN STOCK',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE stock_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consumable_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    status ENUM('PENDING','RECEIVED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    received_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_request_item
        FOREIGN KEY (consumable_id) REFERENCES consumables(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_request_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE withdrawals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Withdraw number',
    consumable_id INT UNSIGNED NOT NULL,
    machine_equipment VARCHAR(150) DEFAULT NULL,
    quantity INT NOT NULL,
    withdrawer VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_withdrawal_item
        FOREIGN KEY (consumable_id) REFERENCES consumables(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_withdrawal_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

-- Users (registration: role is chosen at signup; 'admin' is provisioned manually)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(150) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('admin','user','production_operator','maintenance_technician','cianan_user') NOT NULL DEFAULT 'cianan_user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Service requests (SR Dashboard kanban: REQUEST -> ON GOING -> COMPLETED)
CREATE TABLE service_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description TEXT NOT NULL,
    requested_by VARCHAR(150) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    location VARCHAR(150) DEFAULT NULL,
    plant ENUM('SPRING BOOK','WILLYDRIVE') DEFAULT NULL,
    equipment_name VARCHAR(150) DEFAULT NULL,
    machine_tag VARCHAR(100) DEFAULT NULL,
    picture_path VARCHAR(255) DEFAULT NULL,
    technician VARCHAR(150) DEFAULT NULL,
    status ENUM('REQUEST','ON GOING','COMPLETED') NOT NULL DEFAULT 'REQUEST',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_status (status)
) ENGINE=InnoDB;

-- Sample data
INSERT INTO categories (name, description) VALUES
    ('Tools', 'Hand and power tools'),
    ('Fasteners', 'Bolts, nuts, screws'),
    ('Consumables', 'Oils, filters, seals');

INSERT INTO items (category_id, name, unit, quantity_on_hand, reorder_level) VALUES
    (1, 'Torque Wrench', 'pcs', 5, 2),
    (1, 'Socket Set 3/8"', 'set', 2, 3),
    (2, 'Hex Bolt M8x30', 'pcs', 500, 100),
    (3, 'Engine Oil 15W-40 (1L)', 'btl', 8, 10);
