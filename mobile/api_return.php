<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals(csrf_token(), $csrf)) {
    json_response(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(['success' => false, 'message' => 'Invalid request.'], 400);
}

$value = trim((string)($input['value'] ?? ''));
$returnCondition = (string)($input['return_condition'] ?? 'Good');
$returnStatus = (string)($input['return_status'] ?? 'Returned');
$notes = trim((string)($input['notes'] ?? ''));

$allowedConditions = ['Excellent', 'Good', 'Fair', 'Poor'];
$allowedStatuses = ['Returned', 'Inspection', 'Repair', 'Lost'];

if (
    $value === '' ||
    !in_array($returnCondition, $allowedConditions, true) ||
    !in_array($returnStatus, $allowedStatuses, true)
) {
    json_response(['success' => false, 'message' => 'Invalid return information.'], 422);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT
            ci.id AS checkout_item_id,
            ci.transaction_id,
            ci.tool_id,
            t.name,
            t.status AS tool_status,
            t.tool_condition
         FROM checkout_items ci
         INNER JOIN tools t ON t.id = ci.tool_id
         WHERE ci.return_status = "Pending"
           AND (t.barcode = ? OR t.internal_id = ? OR t.serial_number = ?)
         ORDER BY ci.id DESC
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute([$value, $value, $value]);
    $item = $stmt->fetch();

    if (!is_array($item)) {
        throw new RuntimeException('No open checkout was found for that tool.');
    }

    $toolStatus = match ($returnStatus) {
        'Returned' => 'Available',
        'Inspection' => 'Inspection',
        'Repair' => 'Repair',
        'Lost' => 'Retired',
        default => 'Inspection',
    };

    $user = current_user();

    $pdo->prepare(
        'UPDATE checkout_items
         SET returned_at = NOW(),
             return_condition = ?,
             return_status = ?,
             inspection_notes = ?,
             returned_by = ?
         WHERE id = ?'
    )->execute([
        $returnCondition,
        $returnStatus,
        $notes !== '' ? $notes : null,
        $user['id'] ?? null,
        (int)$item['checkout_item_id'],
    ]);

    $pdo->prepare(
        'UPDATE tools SET status = ?, tool_condition = ? WHERE id = ?'
    )->execute([
        $toolStatus,
        $returnCondition,
        (int)$item['tool_id'],
    ]);

    $pdo->prepare(
        'INSERT INTO tool_status_history
         (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        (int)$item['tool_id'],
        (string)$item['tool_status'],
        $toolStatus,
        (string)$item['tool_condition'],
        $returnCondition,
        $notes !== '' ? $notes : 'Mobile return processed',
        $user['id'] ?? null,
    ]);

    $countStmt = $pdo->prepare(
        'SELECT
            COUNT(*) AS total_count,
            SUM(return_status = "Pending") AS pending_count
         FROM checkout_items
         WHERE transaction_id = ?'
    );
    $countStmt->execute([(int)$item['transaction_id']]);
    $counts = $countStmt->fetch();

    $pending = (int)($counts['pending_count'] ?? 0);
    $total = (int)($counts['total_count'] ?? 0);

    if ($total > 0 && $pending === 0) {
        $pdo->prepare(
            'UPDATE checkout_transactions
             SET status = "Closed", returned_date = NOW(), closed_by = ?
             WHERE id = ?'
        )->execute([$user['id'] ?? null, (int)$item['transaction_id']]);
    } elseif ($pending < $total) {
        $pdo->prepare(
            'UPDATE checkout_transactions
             SET status = "Partially Returned"
             WHERE id = ?'
        )->execute([(int)$item['transaction_id']]);
    }

    $pdo->commit();

    audit_log('mobile_tool_returned', null, (string)$item['name']);

    json_response([
        'success' => true,
        'tool_name' => $item['name'],
        'tool_status' => $toolStatus,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(['success' => false, 'message' => $e->getMessage()], 409);
}
