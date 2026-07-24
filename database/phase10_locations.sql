USE tooltrack;

CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    address_line1 VARCHAR(180) NULL,
    address_line2 VARCHAR(180) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(30) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS storage_bins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_storage_bin_code (location_id, code),
    CONSTRAINT fk_storage_bin_location
        FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS location_managers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_location_manager (location_id, user_id),
    CONSTRAINT fk_location_manager_location
        FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    CONSTRAINT fk_location_manager_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE tools
    ADD COLUMN location_id INT UNSIGNED NULL AFTER category_id,
    ADD COLUMN storage_bin_id INT UNSIGNED NULL AFTER location_id,
    ADD INDEX idx_tools_location (location_id),
    ADD INDEX idx_tools_storage_bin (storage_bin_id);

ALTER TABLE tools
    ADD CONSTRAINT fk_tools_location
        FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_tools_storage_bin
        FOREIGN KEY (storage_bin_id) REFERENCES storage_bins(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS transfer_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_number VARCHAR(60) NOT NULL UNIQUE,
    from_location_id INT UNSIGNED NOT NULL,
    to_location_id INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NULL,
    approved_by INT UNSIGNED NULL,
    received_by INT UNSIGNED NULL,
    status ENUM(
        'Draft',
        'Pending Approval',
        'Approved',
        'In Transit',
        'Received',
        'Rejected',
        'Cancelled'
    ) NOT NULL DEFAULT 'Draft',
    reason VARCHAR(255) NULL,
    notes TEXT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    shipped_at DATETIME NULL,
    received_at DATETIME NULL,
    rejected_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transfer_status (status),
    INDEX idx_transfer_from (from_location_id),
    INDEX idx_transfer_to (to_location_id),
    CONSTRAINT fk_transfer_from_location
        FOREIGN KEY (from_location_id) REFERENCES locations(id),
    CONSTRAINT fk_transfer_to_location
        FOREIGN KEY (to_location_id) REFERENCES locations(id),
    CONSTRAINT fk_transfer_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_received_by
        FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transfer_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_id BIGINT UNSIGNED NOT NULL,
    tool_id INT UNSIGNED NOT NULL,
    from_storage_bin_id INT UNSIGNED NULL,
    to_storage_bin_id INT UNSIGNED NULL,
    item_status ENUM('Pending','Shipped','Received','Rejected') NOT NULL DEFAULT 'Pending',
    condition_out VARCHAR(50) NULL,
    condition_in VARCHAR(50) NULL,
    notes VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_at DATETIME NULL,
    UNIQUE KEY uq_transfer_tool (transfer_id, tool_id),
    CONSTRAINT fk_transfer_item_transfer
        FOREIGN KEY (transfer_id) REFERENCES transfer_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_item_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id),
    CONSTRAINT fk_transfer_item_from_bin
        FOREIGN KEY (from_storage_bin_id) REFERENCES storage_bins(id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_item_to_bin
        FOREIGN KEY (to_storage_bin_id) REFERENCES storage_bins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tool_custody_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tool_id INT UNSIGNED NOT NULL,
    event_type ENUM(
        'Created',
        'Checkout',
        'Return',
        'Transfer Requested',
        'Transfer Approved',
        'Transfer Shipped',
        'Transfer Received',
        'Transfer Rejected',
        'Location Changed',
        'Bin Changed',
        'Maintenance'
    ) NOT NULL,
    from_location_id INT UNSIGNED NULL,
    to_location_id INT UNSIGNED NULL,
    from_storage_bin_id INT UNSIGNED NULL,
    to_storage_bin_id INT UNSIGNED NULL,
    transfer_id BIGINT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_custody_tool_date (tool_id, created_at),
    CONSTRAINT fk_custody_tool
        FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    CONSTRAINT fk_custody_from_location
        FOREIGN KEY (from_location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_custody_to_location
        FOREIGN KEY (to_location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_custody_from_bin
        FOREIGN KEY (from_storage_bin_id) REFERENCES storage_bins(id) ON DELETE SET NULL,
    CONSTRAINT fk_custody_to_bin
        FOREIGN KEY (to_storage_bin_id) REFERENCES storage_bins(id) ON DELETE SET NULL,
    CONSTRAINT fk_custody_transfer
        FOREIGN KEY (transfer_id) REFERENCES transfer_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_custody_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO locations (name, code, active)
VALUES ('Main Warehouse', 'MAIN', 1);
