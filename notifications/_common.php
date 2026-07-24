<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function notification_template(string $key): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM notification_templates
         WHERE template_key = ? AND active = 1
         LIMIT 1'
    );
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function render_notification_template(string $template, array $variables): string
{
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', (string)$value, $template);
    }

    return $template;
}

function create_in_app_notification(
    ?int $userId,
    ?int $employeeId,
    string $type,
    string $title,
    string $message,
    ?string $actionUrl = null,
    string $severity = 'Info'
): int {
    db()->prepare(
        'INSERT INTO notifications
         (user_id, employee_id, notification_type, title, message, action_url, severity)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $userId,
        $employeeId,
        $type,
        $title,
        $message,
        $actionUrl,
        $severity,
    ]);

    return (int)db()->lastInsertId();
}

function queue_email_delivery(
    ?int $notificationId,
    string $templateKey,
    string $recipient,
    string $subject
): int {
    db()->prepare(
        'INSERT INTO notification_deliveries
         (notification_id, template_key, channel, recipient, subject, status)
         VALUES (?, ?, "Email", ?, ?, "Queued")'
    )->execute([
        $notificationId,
        $templateKey,
        $recipient,
        $subject,
    ]);

    return (int)db()->lastInsertId();
}

function notification_admin_users(): array
{
    return db()->query(
        'SELECT id, username, email
         FROM users
         WHERE active = 1
           AND role IN ("Administrator","Manager")
         ORDER BY username'
    )->fetchAll();
}

function delivery_mail(
    string $recipient,
    string $subject,
    string $body
): array {
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Invalid recipient email address.'];
    }

    $fromAddress = defined('MAIL_FROM_ADDRESS')
        ? MAIL_FROM_ADDRESS
        : 'tooltrack@localhost';

    $fromName = defined('MAIL_FROM_NAME')
        ? MAIL_FROM_NAME
        : 'ToolTrack Pro';

    $headers = [
        'From: ' . $fromName . ' <' . $fromAddress . '>',
        'Reply-To: ' . $fromAddress,
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
    ];

    $sent = mail(
        $recipient,
        $subject,
        $body,
        implode("\r\n", $headers)
    );

    return [$sent, $sent ? 'Sent using PHP mail().' : 'PHP mail() returned false.'];
}

function notification_rule(string $key): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM notification_rules WHERE rule_key = ? LIMIT 1'
    );
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}
