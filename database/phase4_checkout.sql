USE tooltrack;

CREATE TABLE IF NOT EXISTS checkout_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    checkout_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date DATETIME NULL,
    returned_date DATETIME NULL,
    status ENUM('Open','Partially Returned','Closed','Cancelled') NOT NULL DEFAULT 'Open',
    notes TEXT NULL,
    issued_by INT UNSIGNED NULL,
    closed_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_checkout_employee (employee_id),
    INDEX idx_checkout_status (status),
    INDEX idx_checkout_date (checkout_date),
    CONSTRAINT fk_checkout_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_checkout_issuer
        FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_checkout_closer
        FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS checkout_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    tool_id INT UNSIGNED NOT NULL,
    checkout_condition VARCHAR(40) NOT NULL,
    returned_at DATETIME NULL,
    return_condition VARCHAR(40) NULL,
    return_status ENUM('Pending','Returned','Inspection','Repair','Lost') NOT NULL DEFAULT 'Pending',
    inspection_notes TEXT NULL,
    returned_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_open_transaction_tool (transaction_id, tool_id),
    INDEX idx_checkout_item_tool (tool_id),
    INDEX idx_checkout_item_return (return_status),
    CONSTRAINT fk_checkout_items_transaction
        FOREIGN KEY (transaction_id) REFERENCES checkout_transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_checkout_items_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id),
    CONSTRAINT fk_checkout_items_returned_by
        FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS scan_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scan_type ENUM('Employee Badge','Tool Checkout','Tool Return','Lookup') NOT NULL,
    scanned_value VARCHAR(150) NOT NULL,
    employee_id INT UNSIGNED NULL,
    tool_id INT UNSIGNED NULL,
    transaction_id BIGINT UNSIGNED NULL,
    success TINYINT(1) NOT NULL DEFAULT 1,
    message VARCHAR(255) NULL,
    scanned_by INT UNSIGNED NULL,
    scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scan_date (scanned_at),
    INDEX idx_scan_value (scanned_value),
    CONSTRAINT fk_scan_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_scan_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE SET NULL,
    CONSTRAINT fk_scan_transaction
        FOREIGN KEY (transaction_id) REFERENCES checkout_transactions(id) ON DELETE SET NULL,
    CONSTRAINT fk_scan_user
        FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
