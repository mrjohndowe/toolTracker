<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function maintenance_priorities(): array
{
    return ['Low', 'Normal', 'High', 'Critical'];
}

function maintenance_statuses(): array
{
    return ['Open', 'In Progress', 'Waiting Parts', 'Completed', 'Cancelled'];
}

function calibration_results(): array
{
    return ['Passed', 'Failed', 'Limited Use'];
}

function maintenance_types_list(): array
{
    return db()->query(
        'SELECT * FROM maintenance_types WHERE active = 1 ORDER BY name'
    )->fetchAll();
}

function maintenance_tools_list(): array
{
    return db()->query(
        'SELECT id, internal_id, name
         FROM tools
         WHERE active = 1
         ORDER BY name'
    )->fetchAll();
}

function generate_work_order_number(): string
{
    return 'WO-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function find_work_order(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT
            wo.*,
            t.name AS tool_name,
            t.internal_id,
            t.status AS tool_status,
            mt.name AS maintenance_type_name
         FROM work_orders wo
         INNER JOIN tools t ON t.id = wo.tool_id
         LEFT JOIN maintenance_types mt ON mt.id = wo.maintenance_type_id
         WHERE wo.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function maintenance_upload_directory(): string
{
    return __DIR__ . '/../uploads/maintenance';
}

function ensure_maintenance_upload_directory(): void
{
    $dir = maintenance_upload_directory();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function record_work_order_history(
    int $workOrderId,
    ?string $oldStatus,
    string $newStatus,
    ?string $notes = null
): void {
    $user = current_user();

    db()->prepare(
        'INSERT INTO work_order_status_history
         (work_order_id, old_status, new_status, notes, changed_by)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([
        $workOrderId,
        $oldStatus,
        $newStatus,
        $notes,
        $user['id'] ?? null,
    ]);
}

function maintenance_total_cost(array $workOrder): float
{
    return (float)$workOrder['labor_cost']
        + (float)$workOrder['parts_cost']
        + (float)$workOrder['other_cost'];
}
