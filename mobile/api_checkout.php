<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../inspections/_common.php';
require_login();

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals(csrf_token(), $csrf)) {
    json_response(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(['success' => false, 'message' => 'Invalid request body.'], 400);
}

$action = (string)($input['action'] ?? '');

if ($action === 'employee') {
    $value = trim((string)($input['value'] ?? ''));
    $employee = mobile_find_employee($value);

    if ($employee === null || $employee['status'] !== 'Active') {
        json_response(['success' => false, 'message' => 'Employee badge not found or employee is inactive.'], 404);
    }

    json_response(['success' => true, 'employee' => $employee]);
}

if ($action === 'tool') {
    $value = trim((string)($input['value'] ?? ''));
    $tool = mobile_find_tool($value);

    if ($tool === null) {
        json_response(['success' => false, 'message' => 'Tool not found.'], 404);
    }

    if ($tool['status'] !== 'Available') {
        json_response([
            'success' => false,
            'message' => 'Tool is not available. Current status: ' . $tool['status'],
        ], 409);
    }

    json_response(['success' => true, 'tool' => $tool]);
}

if ($action === 'complete') {
    $employeeId = filter_var($input['employee_id'] ?? null, FILTER_VALIDATE_INT);
    $toolIds = $input['tool_ids'] ?? [];
    $dueDate = trim((string)($input['due_date'] ?? ''));
    $notes = trim((string)($input['notes'] ?? ''));

    if (!$employeeId || !is_array($toolIds) || !$toolIds) {
        json_response(['success' => false, 'message' => 'Employee and at least one tool are required.'], 422);
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
            throw new RuntimeException('Employee is no longer active.');
        }

        $user = current_user();
        $transactionNumber = 'TX-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

        $pdo->prepare(
            'INSERT INTO checkout_transactions
             (transaction_number, employee_id, due_date, notes, issued_by)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $transactionNumber,
            $employeeId,
            $dueDate !== '' ? $dueDate : null,
            $notes !== '' ? $notes : null,
            $user['id'] ?? null,
        ]);

        $transactionId = (int)$pdo->lastInsertId();
        $inspectionItems = [];

        $selectTool = $pdo->prepare(
            'SELECT id, barcode, name, status, tool_condition
             FROM tools
             WHERE id = ?
             FOR UPDATE'
        );

        $updateTool = $pdo->prepare(
            'UPDATE tools SET status = "Checked Out" WHERE id = ?'
        );

        $insertItem = $pdo->prepare(
            'INSERT INTO checkout_items
             (transaction_id, tool_id, checkout_condition)
             VALUES (?, ?, ?)'
        );

        $insertHistory = $pdo->prepare(
            'INSERT INTO tool_status_history
             (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
             VALUES (?, "Available", "Checked Out", ?, ?, ?, ?)'
        );

        foreach (array_unique(array_map('intval', $toolIds)) as $toolId) {
            $selectTool->execute([$toolId]);
            $tool = $selectTool->fetch();

            if (!is_array($tool) || $tool['status'] !== 'Available') {
                throw new RuntimeException('One or more tools are no longer available.');
            }

            $updateTool->execute([$toolId]);
            $insertItem->execute([$transactionId, $toolId, $tool['tool_condition']]);
            $checkoutItemId = (int)$pdo->lastInsertId();
            $inspectionItems[] = ['type'=>'Checkout','tool_id'=>$toolId,'transaction_id'=>$transactionId,'checkout_item_id'=>$checkoutItemId,'employee_id'=>$employeeId];
            $insertHistory->execute([
                $toolId,
                $tool['tool_condition'],
                $tool['tool_condition'],
                'Mobile checkout ' . $transactionNumber,
                $user['id'] ?? null,
            ]);
        }

        $pdo->commit();
        audit_log('mobile_checkout_completed', null, $transactionNumber);

        json_response([
            'success' => true,
            'transaction_id' => $transactionId,
            'transaction_number' => $transactionNumber,
            'inspection_url' => inspection_create_queue('Checkout', $inspectionItems, BASE_URL . '/checkout/view.php?id=' . $transactionId),
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        json_response(['success' => false, 'message' => $e->getMessage()], 409);
    }
}

json_response(['success' => false, 'message' => 'Unknown action.'], 400);
