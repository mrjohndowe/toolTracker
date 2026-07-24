<?php
$rule = notification_rule('calibration_due');

if ($rule && (int)$rule['enabled'] === 1) {
    $leadDays = max(0, (int)$rule['lead_days']);

    $stmt = db()->prepare(
        'SELECT
            cr.id, cr.next_calibration_date, cr.certificate_number,
            t.id AS tool_id, t.name AS tool_name, t.internal_id
         FROM calibration_records cr
         INNER JOIN (
             SELECT tool_id, MAX(id) AS latest_id
             FROM calibration_records
             GROUP BY tool_id
         ) latest ON latest.latest_id = cr.id
         INNER JOIN tools t ON t.id = cr.tool_id
         WHERE cr.next_calibration_date IS NOT NULL
           AND cr.next_calibration_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)'
    );
    $stmt->execute([$leadDays]);
    $rows = $stmt->fetchAll();

    $template = notification_template('calibration_due');

    foreach ($rows as $row) {
        $variables = [
            'tool_name' => $row['tool_name'],
            'internal_id' => $row['internal_id'],
            'due_date' => $row['next_calibration_date'],
            'certificate_number' => $row['certificate_number'] ?: 'N/A',
        ];

        $title = render_notification_template(
            $template['subject_template'] ?: 'Calibration Due',
            $variables
        );
        $message = render_notification_template($template['body_template'], $variables);
        $actionUrl = BASE_URL . '/maintenance/calibration.php';
        $repeat = max(1, (int)($rule['repeat_days'] ?? 7));

        foreach (notification_admin_users() as $admin) {
            $exists = db()->prepare(
                'SELECT COUNT(*)
                 FROM notifications
                 WHERE user_id = ?
                   AND notification_type = "calibration_due"
                   AND title = ?
                   AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
            );
            $exists->execute([(int)$admin['id'], $title, $repeat]);

            if ((int)$exists->fetchColumn() > 0) {
                continue;
            }

            $notificationId = null;

            if ((int)$rule['in_app_enabled'] === 1) {
                $notificationId = create_in_app_notification(
                    (int)$admin['id'],
                    null,
                    'calibration_due',
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
                    'calibration_due',
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

    $messages[] = 'calibration_due=' . count($rows);
}
