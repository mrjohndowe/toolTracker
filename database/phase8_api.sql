USE tooltrack;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    token_prefix VARCHAR(20) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    scopes JSON NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    INDEX idx_api_token_active (active),
    INDEX idx_api_token_prefix (token_prefix),
    CONSTRAINT fk_api_token_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS api_request_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_token_id BIGINT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    request_id CHAR(36) NOT NULL,
    duration_ms INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_log_date (created_at),
    INDEX idx_api_log_endpoint (endpoint),
    INDEX idx_api_log_request_id (request_id),
    CONSTRAINT fk_api_log_token
        FOREIGN KEY (api_token_id) REFERENCES api_tokens(id) ON DELETE SET NULL,
    CONSTRAINT fk_api_log_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
