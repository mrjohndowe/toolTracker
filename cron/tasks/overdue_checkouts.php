<?php
$rule = notification_rule('overdue_checkout');

if ($rule && (int)$rule['enabled'] === 1) {
    $rows = db()->query(
        'SELECT
            ct.id, ct.transaction_number, ct.due_date,
            e.id AS employee_id,
            e.first_name, e.last_name, e.email,
            COUNT(ci.id) AS tool_count
         FROM checkout_transactions ct
         INNER JOIN employees e ON e.id = ct.employee_id
         INNER JOIN checkout_items ci ON ci.transaction_id = ct.id
         WHERE ct.status IN ("Open","Partially Returned")
           AND ct.due_date < NOW()
           AND ci.return_status = "Pending"
         GROUP BY ct.id'
    )->fetchAll();

    $template = notification_template('overdue_checkout');

    foreach ($rows as $row) {
        $variables = [
            'employee_name' => $row['first_name'] . ' ' . $row['last_name'],
            'transaction_number' => $row['transaction_number'],
            'due_date' => $row['due_date'],
            'tool_count' => $row['tool_count'],
        ];

        $title = render_notification_template(
            $template['subject_template'] ?: 'Overdue Checkout',
            $variables
        );

        $message = render_notification_template(
            $template['body_template'],
            $variables
        );

        foreach (notification_admin_users() as $admin) {
            $exists = db()->prepare(
                'SELECT COUNT(*)
                 FROM notifications
                 WHERE user_id = ?
                   AND notification_type = "overdue_checkout"
                   AND action_url = ?
                   AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
            );
            $actionUrl = BASE_URL . '/checkout/view.php?id=' . (int)$row['id'];
            $repeat = max(1, (int)($rule['repeat_days'] ?? 1));

            $exists->execute([
                (int)$admin['id'],
                $actionUrl,
                $repeat,
            ]);

            if ((int)$exists->fetchColumn() > 0) {
                continue;
            }

            $notificationId = null;

            if ((int)$rule['in_app_enabled'] === 1) {
                $notificationId = create_in_app_notification(
                    (int)$admin['id'],
                    (int)$row['employee_id'],
                    'overdue_checkout',
                    $title,
                    $message,
                    $actionUrl,
                    'Warning'
                );
            }

            if (
                (int)$rule['email_enabled'] === 1 &&
                !empty($row['email'])
            ) {
                queue_email_delivery(
                    $notificationId,
                    'overdue_checkout',
                    (string)$row['email'],
                    $title
                );
            }

            $processed++;
        }
    }

    db()->prepare(
        'UPDATE notification_rules SET last_run_at = NOW() WHERE id = ?'
    )->execute([(int)$rule['id']]);

    $messages[] = 'overdue_checkouts=' . count($rows);
}
