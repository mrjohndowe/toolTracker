<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('POST');
$token = api_authenticate(['checkout:write']);
$body = api_body();

$employeeId = filter_var($body['employee_id'] ?? null, FILTER_VALIDATE_INT);
$toolIds = $body['tool_ids'] ?? [];
$dueDate = trim((string)($body['due_date'] ?? ''));
$notes = trim((string)($body['notes'] ?? ''));

if (!$employeeId || !is_array($toolIds) || !$toolIds) {
    api_error('employee_id and at least one tool_id are required.', 422);
}

$toolIds = array_values(array_unique(array_filter(array_map('intval', $toolIds))));

if (!$toolIds) {
    api_error('No valid tool ids were provided.', 422);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $employeeStmt = $pdo->prepare(
        'SELECT id FROM employees
         WHERE id = ? AND active = 1 AND status = "Active"
         FOR UPDATE'
    );
    $employeeStmt->execute([$employeeId]);

    if (!$employeeStmt->fetchColumn()) {
        throw new RuntimeException('Employee is not active or was not found.');
    }

    $number = 'TX-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $pdo->prepare(
        'INSERT INTO checkout_transactions
         (transaction_number, employee_id, due_date, notes, issued_by)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([
        $number,
        $employeeId,
        $dueDate !== '' ? $dueDate : null,
        $notes !== '' ? $notes : null,
        $token['user_id'] ?? null,
    ]);

    $transactionId = (int)$pdo->lastInsertId();

    $selectTool = $pdo->prepare(
        'SELECT id, status, tool_condition
         FROM tools
         WHERE id = ? AND active = 1
         FOR UPDATE'
    );

    $insertItem = $pdo->prepare(
        'INSERT INTO checkout_items
         (transaction_id, tool_id, checkout_condition)
         VALUES (?, ?, ?)'
    );

    $updateTool = $pdo->prepare(
        'UPDATE tools SET status = "Checked Out" WHERE id = ?'
    );

    $history = $pdo->prepare(
        'INSERT INTO tool_status_history
         (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
         VALUES (?, "Available", "Checked Out", ?, ?, ?, ?)'
    );

    foreach ($toolIds as $toolId) {
        $selectTool->execute([$toolId]);
        $tool = $selectTool->fetch();

        if (!is_array($tool)) {
            throw new RuntimeException("Tool {$toolId} was not found.");
        }

        if ($tool['status'] !== 'Available') {
            throw new RuntimeException("Tool {$toolId} is not available.");
        }

        $updateTool->execute([$toolId]);
        $insertItem->execute([$transactionId, $toolId, $tool['tool_condition']]);
        $history->execute([
            $toolId,
            $tool['tool_condition'],
            $tool['tool_condition'],
            'API checkout ' . $number,
            $token['user_id'] ?? null,
        ]);
    }

    $pdo->commit();

    api_success([
        'transaction_id' => $transactionId,
        'transaction_number' => $number,
        'employee_id' => $employeeId,
        'tool_ids' => $toolIds,
    ], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_error($e->getMessage(), 409);
}
