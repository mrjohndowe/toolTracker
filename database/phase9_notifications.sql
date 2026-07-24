USE tooltrack;

CREATE TABLE IF NOT EXISTS notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    channel ENUM('In App','Email','Both') NOT NULL DEFAULT 'Both',
    subject_template VARCHAR(255) NULL,
    body_template TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    employee_id INT UNSIGNED NULL,
    notification_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(500) NULL,
    severity ENUM('Info','Success','Warning','Critical') NOT NULL DEFAULT 'Info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_read (user_id, is_read),
    INDEX idx_notifications_employee (employee_id),
    INDEX idx_notifications_created (created_at),
    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NULL,
    template_key VARCHAR(100) NULL,
    channel ENUM('In App','Email') NOT NULL,
    recipient VARCHAR(255) NULL,
    subject VARCHAR(255) NULL,
    status ENUM('Queued','Sent','Failed','Skipped') NOT NULL DEFAULT 'Queued',
    response_message VARCHAR(500) NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_delivery_status (status),
    INDEX idx_delivery_created (created_at),
    CONSTRAINT fk_delivery_notification
        FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    lead_days INT UNSIGNED NOT NULL DEFAULT 0,
    repeat_days INT UNSIGNED NULL,
    email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    in_app_enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_run_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS scheduled_task_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(150) NOT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    status ENUM('Running','Completed','Failed') NOT NULL DEFAULT 'Running',
    processed_count INT UNSIGNED NOT NULL DEFAULT 0,
    message VARCHAR(500) NULL,
    INDEX idx_task_run_name_date (task_name, started_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO notification_templates
(template_key, name, channel, subject_template, body_template)
VALUES
(
    'overdue_checkout',
    'Overdue Checkout',
    'Both',
    'Overdue Tool Checkout - {{transaction_number}}',
    'Hello {{employee_name}},

Your ToolTrack checkout {{transaction_number}} was due on {{due_date}}.

Outstanding tools: {{tool_count}}

Please return the tools or contact your supervisor.

ToolTrack Pro'
),
(
    'maintenance_due',
    'Maintenance Due',
    'Both',
    'Maintenance Due - {{tool_name}}',
    'Maintenance is due for {{tool_name}} ({{internal_id}}) on {{due_date}}.

Maintenance type: {{maintenance_type}}

Open ToolTrack Pro to review the maintenance schedule.'
),
(
    'work_order_overdue',
    'Overdue Work Order',
    'Both',
    'Overdue Work Order - {{work_order_number}}',
    'Work order {{work_order_number}} is overdue.

Tool: {{tool_name}}
Title: {{title}}
Due date: {{due_date}}
Priority: {{priority}}'
),
(
    'calibration_due',
    'Calibration Due',
    'Both',
    'Calibration Due - {{tool_name}}',
    'Calibration is due for {{tool_name}} ({{internal_id}}) on {{due_date}}.

Certificate: {{certificate_number}}'
);

INSERT IGNORE INTO notification_rules
(rule_key, name, lead_days, repeat_days, email_enabled, in_app_enabled)
VALUES
('overdue_checkout', 'Overdue Checkout Alerts', 0, 1, 1, 1),
('maintenance_due', 'Maintenance Due Alerts', 14, 7, 1, 1),
('work_order_overdue', 'Overdue Work Order Alerts', 0, 1, 1, 1),
('calibration_due', 'Calibration Due Alerts', 30, 7, 1, 1);
