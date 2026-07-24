CREATE DATABASE IF NOT EXISTS tooltrack
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE tooltrack;

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NULL UNIQUE,
    role_id INT UNSIGNED NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    target_user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_created (created_at),
    INDEX idx_activity_user (user_id),
    CONSTRAINT fk_activity_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_activity_target
        FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_username_time (username, attempted_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'Administrator', 'Full system access'),
(2, 'Tool Room Attendant', 'Checkout, return, and inspection access'),
(3, 'Supervisor', 'Reporting and oversight access');

REATE TABLE IF NOT EXISTS tool_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tool_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tools (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    internal_id VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    serial_number VARCHAR(100) NULL,
    name VARCHAR(150) NOT NULL,
    manufacturer VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    category_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    status ENUM('Available','Checked Out','Inspection','Repair','Retired') NOT NULL DEFAULT 'Available',
    tool_condition ENUM('Excellent','Good','Fair','Poor') NOT NULL DEFAULT 'Good',
    purchase_date DATE NULL,
    replacement_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tools_name (name),
    INDEX idx_tools_serial (serial_number),
    INDEX idx_tools_status (status),
    INDEX idx_tools_category (category_id),
    INDEX idx_tools_location (location_id),
    CONSTRAINT fk_tools_category FOREIGN KEY (category_id) REFERENCES tool_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_tools_location FOREIGN KEY (location_id) REFERENCES tool_locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_tools_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tool_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tool_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tool_photos_tool FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    CONSTRAINT fk_tool_photos_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tool_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tool_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NOT NULL,
    old_condition VARCHAR(40) NULL,
    new_condition VARCHAR(40) NOT NULL,
    notes VARCHAR(255) NULL,
    changed_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tool_status_history_tool (tool_id, created_at),
    CONSTRAINT fk_tool_status_history_tool FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    CONSTRAINT fk_tool_status_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO tool_categories (name, description) VALUES
('Power Tools', 'Electric, battery, and pneumatic tools'),
('Hand Tools', 'Manual tools and small equipment'),
('Safety Equipment', 'Protective and safety equipment');

INSERT IGNORE INTO tool_locations (name, description) VALUES
('Main Tool Room', 'Primary tool storage area'),
('Repair Area', 'Tools awaiting or undergoing repair'),
('Warehouse', 'General storage area');

USE tooltrack;

CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(50) NOT NULL UNIQUE,
    badge_code VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    department_id INT UNSIGNED NULL,
    job_title VARCHAR(120) NULL,
    supervisor_name VARCHAR(150) NULL,
    hire_date DATE NULL,
    status ENUM('Active','Inactive','Suspended','Terminated') NOT NULL DEFAULT 'Active',
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_name (last_name, first_name),
    INDEX idx_employee_number (employee_number),
    INDEX idx_employee_badge (badge_code),
    INDEX idx_employee_status (status),
    CONSTRAINT fk_employees_department
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_employees_creator
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employee_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_photos_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_photos_user
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employee_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    changed_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_history (employee_id, created_at),
    CONSTRAINT fk_employee_history_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_history_user
        FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO departments (name, description) VALUES
('Operations', 'General operations personnel'),
('Maintenance', 'Maintenance and repair staff'),
('Warehouse', 'Warehouse and inventory staff'),
('Administration', 'Office and administrative personnel');

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

USE tooltrack;

CREATE TABLE IF NOT EXISTS mobile_scan_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_token CHAR(64) NOT NULL UNIQUE,
    user_id INT UNSIGNED NULL,
    employee_id INT UNSIGNED NULL,
    mode ENUM('Checkout','Return','Lookup') NOT NULL DEFAULT 'Lookup',
    status ENUM('Open','Completed','Cancelled','Expired') NOT NULL DEFAULT 'Open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    expires_at DATETIME NULL,
    INDEX idx_mobile_scan_status (status),
    INDEX idx_mobile_scan_expires (expires_at),
    CONSTRAINT fk_mobile_scan_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_mobile_scan_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mobile_scan_session_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    scanned_value VARCHAR(150) NOT NULL,
    tool_id INT UNSIGNED NULL,
    scan_result ENUM('Accepted','Rejected','Duplicate','Unknown') NOT NULL,
    message VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mobile_scan_item_session (session_id),
    CONSTRAINT fk_mobile_scan_item_session
        FOREIGN KEY (session_id) REFERENCES mobile_scan_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_mobile_scan_item_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE SET NULL
) ENGINE=InnoDB;
