<?php
$rule = notification_rule('work_order_overdue');

if ($rule && (int)$rule['enabled'] === 1) {
    $rows = db()->query(
        'SELECT
            wo.id, wo.work_order_number, wo.title,
            wo.due_date, wo.priority,
            t.name AS tool_name
         FROM work_orders wo
         INNER JOIN tools t ON t.id = wo.tool_id
         WHERE wo.due_date < CURDATE()
           AND wo.status NOT IN ("Completed","Cancelled")'
    )->fetchAll();

    $template = notification_template('work_order_overdue');

    foreach ($rows as $row) {
        $variables = [
            'work_order_number' => $row['work_order_number'],
            'tool_name' => $row['tool_name'],
            'title' => $row['title'],
            'due_date' => $row['due_date'],
            'priority' => $row['priority'],
        ];

        $title = render_notification_template(
            $template['subject_template'] ?: 'Overdue Work Order',
            $variables
        );
        $message = render_notification_template($template['body_template'], $variables);
        $actionUrl = BASE_URL . '/maintenance/work_order_view.php?id=' . (int)$row['id'];
        $repeat = max(1, (int)($rule['repeat_days'] ?? 1));

        foreach (notification_admin_users() as $admin) {
            $exists = db()->prepare(
                'SELECT COUNT(*)
                 FROM notifications
                 WHERE user_id = ?
                   AND notification_type = "work_order_overdue"
                   AND action_url = ?
                   AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
            );
            $exists->execute([(int)$admin['id'], $actionUrl, $repeat]);

            if ((int)$exists->fetchColumn() > 0) {
                continue;
            }

            $notificationId = null;

            if ((int)$rule['in_app_enabled'] === 1) {
                $notificationId = create_in_app_notification(
                    (int)$admin['id'],
                    null,
                    'work_order_overdue',
                    $title,
                    $message,
                    $actionUrl,
                    $row['priority'] === 'Critical' ? 'Critical' : 'Warning'
                );
            }

            if (
                (int)$rule['email_enabled'] === 1 &&
                !empty($admin['email'])
            ) {
                queue_email_delivery(
                    $notificationId,
                    'work_order_overdue',
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

    $messages[] = 'work_orders_overdue=' . count($rows);
}
