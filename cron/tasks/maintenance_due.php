<?php
$rule = notification_rule('maintenance_due');

if ($rule && (int)$rule['enabled'] === 1) {
    $leadDays = max(0, (int)$rule['lead_days']);

    $stmt = db()->prepare(
        'SELECT
            ms.id, ms.next_service_date,
            t.id AS tool_id, t.name AS tool_name, t.internal_id,
            mt.name AS maintenance_type
         FROM maintenance_schedules ms
         INNER JOIN tools t ON t.id = ms.tool_id
         INNER JOIN maintenance_types mt ON mt.id = ms.maintenance_type_id
         WHERE ms.active = 1
           AND ms.next_service_date IS NOT NULL
           AND ms.next_service_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)'
    );
    $stmt->execute([$leadDays]);
    $rows = $stmt->fetchAll();

    $template = notification_template('maintenance_due');

    foreach ($rows as $row) {
        $variables = [
            'tool_name' => $row['tool_name'],
            'internal_id' => $row['internal_id'],
            'due_date' => $row['next_service_date'],
            'maintenance_type' => $row['maintenance_type'],
        ];

        $title = render_notification_template(
            $template['subject_template'] ?: 'Maintenance Due',
            $variables
        );
        $message = render_notification_template($template['body_template'], $variables);
        $actionUrl = BASE_URL . '/maintenance/schedules.php';
        $repeat = max(1, (int)($rule['repeat_days'] ?? 7));

        foreach (notification_admin_users() as $admin) {
            $exists = db()->prepare(
                'SELECT COUNT(*)
                 FROM notifications
                 WHERE user_id = ?
                   AND notification_type = "maintenance_due"
                   AND title = ?
                   AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
            );
            $exists->execute([
                (int)$admin['id'],
                $title,
                $repeat,
            ]);

            if ((int)$exists->fetchColumn() > 0) {
                continue;
            }

            $notificationId = null;

            if ((int)$rule['in_app_enabled'] === 1) {
                $notificationId = create_in_app_notification(
                    (int)$admin['id'],
                    null,
                    'maintenance_due',
                    $title,
                    $message,
                    $actionUrl,
                    'Warning'
                );
            }

            if (
                (int)$rule['email_enabled'] === 1 &&
                !empty($admin['email'])
            ) {
                queue_email_delivery(
                    $notificationId,
                    'maintenance_due',
                    (string)$admin['email'],
                    $title
                );
            }

            $processed++;
        }
    }

    db()->prepare(
        'UPDATE notification_rules SET last_run_at = NOW() WHERE id = ?'
    )->execute([(int)$rule['id']]);

    $messages[] = 'maintenance_due=' . count($rows);
}
