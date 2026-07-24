<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/../notifications/_common.php';

$runStmt = db()->prepare(
    'INSERT INTO scheduled_task_runs (task_name, status)
     VALUES ("notifications", "Running")'
);
$runStmt->execute();
$runId = (int)db()->lastInsertId();

$processed = 0;
$messages = [];

try {
    require __DIR__ . '/tasks/overdue_checkouts.php';
    require __DIR__ . '/tasks/maintenance_due.php';
    require __DIR__ . '/tasks/work_orders_overdue.php';
    require __DIR__ . '/tasks/calibration_due.php';
    require __DIR__ . '/tasks/send_email_queue.php';

    db()->prepare(
        'UPDATE scheduled_task_runs
         SET status = "Completed", completed_at = NOW(),
             processed_count = ?, message = ?
         WHERE id = ?'
    )->execute([
        $processed,
        implode(' | ', $messages),
        $runId,
    ]);

    echo "Notification run completed. Processed: {$processed}\n";
} catch (Throwable $e) {
    db()->prepare(
        'UPDATE scheduled_task_runs
         SET status = "Failed", completed_at = NOW(),
             processed_count = ?, message = ?
         WHERE id = ?'
    )->execute([
        $processed,
        substr($e->getMessage(), 0, 500),
        $runId,
    ]);

    fwrite(STDERR, "Notification run failed: {$e->getMessage()}\n");
    exit(1);
}
