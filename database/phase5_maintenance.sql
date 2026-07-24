USE tooltrack;

CREATE TABLE IF NOT EXISTS maintenance_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    default_interval_days INT UNSIGNED NULL,
    requires_calibration TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tool_id INT UNSIGNED NOT NULL,
    maintenance_type_id INT UNSIGNED NOT NULL,
    interval_days INT UNSIGNED NULL,
    last_service_date DATE NULL,
    next_service_date DATE NULL,
    reminder_days INT UNSIGNED NOT NULL DEFAULT 14,
    active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tool_maintenance_type (tool_id, maintenance_type_id),
    INDEX idx_schedule_next_date (next_service_date),
    CONSTRAINT fk_schedule_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_type
        FOREIGN KEY (maintenance_type_id) REFERENCES maintenance_types(id),
    CONSTRAINT fk_schedule_user
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_order_number VARCHAR(50) NOT NULL UNIQUE,
    tool_id INT UNSIGNED NOT NULL,
    maintenance_type_id INT UNSIGNED NULL,
    schedule_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    priority ENUM('Low','Normal','High','Critical') NOT NULL DEFAULT 'Normal',
    status ENUM('Open','In Progress','Waiting Parts','Completed','Cancelled') NOT NULL DEFAULT 'Open',
    assigned_to VARCHAR(150) NULL,
    vendor_name VARCHAR(150) NULL,
    opened_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NULL,
    completed_date DATETIME NULL,
    labor_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    parts_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    other_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    completion_notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    completed_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_work_order_status (status),
    INDEX idx_work_order_due_date (due_date),
    INDEX idx_work_order_tool (tool_id),
    CONSTRAINT fk_work_order_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id),
    CONSTRAINT fk_work_order_type
        FOREIGN KEY (maintenance_type_id) REFERENCES maintenance_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_order_schedule
        FOREIGN KEY (schedule_id) REFERENCES maintenance_schedules(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_order_creator
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_order_completer
        FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_order_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_order_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NOT NULL,
    notes VARCHAR(255) NULL,
    changed_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_work_order_history (work_order_id, created_at),
    CONSTRAINT fk_work_order_history_order
        FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_work_order_history_user
        FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_order_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_order_id BIGINT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(100) NULL,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_work_order_attachment_order
        FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_work_order_attachment_user
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS calibration_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tool_id INT UNSIGNED NOT NULL,
    work_order_id BIGINT UNSIGNED NULL,
    certificate_number VARCHAR(100) NULL,
    calibration_date DATE NOT NULL,
    next_calibration_date DATE NULL,
    result ENUM('Passed','Failed','Limited Use') NOT NULL,
    performed_by VARCHAR(150) NULL,
    standards_used VARCHAR(255) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_calibration_tool (tool_id),
    INDEX idx_calibration_next_date (next_calibration_date),
    CONSTRAINT fk_calibration_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    CONSTRAINT fk_calibration_work_order
        FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_calibration_user
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO maintenance_types
(name, description, default_interval_days, requires_calibration)
VALUES
('Preventive Maintenance', 'Routine inspection, cleaning, and servicing', 90, 0),
('Safety Inspection', 'Scheduled safety and operational inspection', 30, 0),
('Calibration', 'Accuracy verification and calibration', 365, 1),
('Repair', 'Corrective repair for damaged or failed equipment', NULL, 0);
