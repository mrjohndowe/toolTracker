<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('POST');
$token = api_authenticate(['checkout:write']);
$body = api_body();

$toolId = filter_var($body['tool_id'] ?? null, FILTER_VALIDATE_INT);
$returnCondition = (string)($body['return_condition'] ?? 'Good');
$returnStatus = (string)($body['return_status'] ?? 'Returned');
$notes = trim((string)($body['notes'] ?? ''));

$allowedConditions = ['Excellent', 'Good', 'Fair', 'Poor'];
$allowedStatuses = ['Returned', 'Inspection', 'Repair', 'Lost'];

if (!$toolId) {
    api_error('A valid tool_id is required.', 422);
}

if (!in_array($returnCondition, $allowedConditions, true)) {
    api_error('Invalid return_condition.', 422);
}

if (!in_array($returnStatus, $allowedStatuses, true)) {
    api_error('Invalid return_status.', 422);
}

$toolStatus = match ($returnStatus) {
    'Returned' => 'Available',
    'Inspection' => 'Inspection',
    'Repair' => 'Repair',
    'Lost' => 'Retired',
};

$pdo = db();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT
            ci.id, ci.transaction_id, ci.tool_id,
            t.status AS tool_status, t.tool_condition
         FROM checkout_items ci
         INNER JOIN tools t ON t.id = ci.tool_id
         WHERE ci.tool_id = ?
           AND ci.return_status = "Pending"
         ORDER BY ci.id DESC
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute([$toolId]);
    $item = $stmt->fetch();

    if (!is_array($item)) {
        throw new RuntimeException('No open checkout item exists for this tool.');
    }

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
        $token['user_id'] ?? null,
        (int)$item['id'],
    ]);

    $pdo->prepare(
        'UPDATE tools SET status = ?, tool_condition = ? WHERE id = ?'
    )->execute([$toolStatus, $returnCondition, $toolId]);

    $pdo->prepare(
        'INSERT INTO tool_status_history
         (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $toolId,
        $item['tool_status'],
        $toolStatus,
        $item['tool_condition'],
        $returnCondition,
        $notes !== '' ? $notes : 'API return processed',
        $token['user_id'] ?? null,
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

    $total = (int)($counts['total_count'] ?? 0);
    $pending = (int)($counts['pending_count'] ?? 0);

    if ($total > 0 && $pending === 0) {
        $pdo->prepare(
            'UPDATE checkout_transactions
             SET status = "Closed", returned_date = NOW(), closed_by = ?
             WHERE id = ?'
        )->execute([$token['user_id'] ?? null, (int)$item['transaction_id']]);
    } elseif ($pending < $total) {
        $pdo->prepare(
            'UPDATE checkout_transactions
             SET status = "Partially Returned"
             WHERE id = ?'
        )->execute([(int)$item['transaction_id']]);
    }

    $pdo->commit();

    api_success([
        'tool_id' => $toolId,
        'tool_status' => $toolStatus,
        'transaction_id' => (int)$item['transaction_id'],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_error($e->getMessage(), 409);
}
