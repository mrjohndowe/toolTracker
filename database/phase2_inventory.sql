USE tooltrack;

CREATE TABLE IF NOT EXISTS tool_categories (
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
