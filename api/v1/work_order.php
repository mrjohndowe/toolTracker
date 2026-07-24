<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('POST');
$token = api_authenticate(['maintenance:write']);
$body = api_body();

$toolId = filter_var($body['tool_id'] ?? null, FILTER_VALIDATE_INT);
$typeId = filter_var($body['maintenance_type_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$title = trim((string)($body['title'] ?? ''));
$description = trim((string)($body['description'] ?? ''));
$priority = (string)($body['priority'] ?? 'Normal');
$assignedTo = trim((string)($body['assigned_to'] ?? ''));
$vendor = trim((string)($body['vendor_name'] ?? ''));
$dueDate = trim((string)($body['due_date'] ?? ''));

if (!$toolId || $title === '') {
    api_error('tool_id and title are required.', 422);
}

if (!in_array($priority, ['Low', 'Normal', 'High', 'Critical'], true)) {
    api_error('Invalid priority.', 422);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $toolStmt = $pdo->prepare(
        'SELECT id, status, tool_condition
         FROM tools
         WHERE id = ? AND active = 1
         FOR UPDATE'
    );
    $toolStmt->execute([$toolId]);
    $tool = $toolStmt->fetch();

    if (!is_array($tool)) {
        throw new RuntimeException('Tool not found.');
    }

    $number = 'WO-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $pdo->prepare(
        'INSERT INTO work_orders
         (work_order_number, tool_id, maintenance_type_id,
          title, description, priority, assigned_to, vendor_name,
          due_date, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $number,
        $toolId,
        $typeId,
        $title,
        $description !== '' ? $description : null,
        $priority,
        $assignedTo !== '' ? $assignedTo : null,
        $vendor !== '' ? $vendor : null,
        $dueDate !== '' ? $dueDate : null,
        $token['user_id'] ?? null,
    ]);

    $workOrderId = (int)$pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO work_order_status_history
         (work_order_id, old_status, new_status, notes, changed_by)
         VALUES (?, NULL, "Open", "Created through API", ?)'
    )->execute([$workOrderId, $token['user_id'] ?? null]);

    if ($tool['status'] !== 'Repair') {
        $pdo->prepare(
            'UPDATE tools SET status = "Repair" WHERE id = ?'
        )->execute([$toolId]);

        $pdo->prepare(
            'INSERT INTO tool_status_history
             (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
             VALUES (?, ?, "Repair", ?, ?, ?, ?)'
        )->execute([
            $toolId,
            $tool['status'],
            $tool['tool_condition'],
            $tool['tool_condition'],
            'API work order ' . $number,
            $token['user_id'] ?? null,
        ]);
    }

    $pdo->commit();

    api_success([
        'work_order_id' => $workOrderId,
        'work_order_number' => $number,
    ], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_error($e->getMessage(), 409);
}
