<?php
$rows = db()->query(
    'SELECT
        nd.*,
        nt.body_template
     FROM notification_deliveries nd
     LEFT JOIN notification_templates nt
        ON nt.template_key = nd.template_key
     WHERE nd.channel = "Email"
       AND nd.status = "Queued"
     ORDER BY nd.id
     LIMIT 100'
)->fetchAll();

foreach ($rows as $row) {
    $body = (string)($row['body_template'] ?? '');

    if ($row['notification_id']) {
        $stmt = db()->prepare(
            'SELECT message FROM notifications WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int)$row['notification_id']]);
        $notificationMessage = $stmt->fetchColumn();

        if ($notificationMessage !== false) {
            $body = (string)$notificationMessage;
        }
    }

    [$sent, $response] = delivery_mail(
        (string)$row['recipient'],
        (string)($row['subject'] ?? 'ToolTrack Notification'),
        $body
    );

    db()->prepare(
        'UPDATE notification_deliveries
         SET status = ?, response_message = ?, sent_at = ?
         WHERE id = ?'
    )->execute([
        $sent ? 'Sent' : 'Failed',
        substr($response, 0, 500),
        $sent ? date('Y-m-d H:i:s') : null,
        (int)$row['id'],
    ]);

    $processed++;
}

$messages[] = 'email_queue=' . count($rows);
