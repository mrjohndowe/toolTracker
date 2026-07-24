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
