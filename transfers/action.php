<?php
declare(strict_types=1);

require_once __DIR__ . '/../locations/_common.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/transfers/index.php');
}

verify_csrf();

$transferId = filter_input(INPUT_POST, 'transfer_id', FILTER_VALIDATE_INT);
$action = (string)($_POST['action'] ?? '');

if (!$transferId) {
    redirect('/transfers/index.php');
}

$transfer = find_transfer($transferId);

if (!$transfer) {
    flash('danger', 'Transfer not found.');
    redirect('/transfers/index.php');
}

$items = transfer_items($transferId);
$user = current_user();
$userId = $user['id'] ?? null;
$pdo = db();

try {
    $pdo->beginTransaction();

    if ($action === 'approve' && $transfer['status'] === 'Pending Approval') {
        $pdo->prepare(
            'UPDATE transfer_requests
             SET status = "Approved", approved_by = ?, approved_at = NOW()
             WHERE id = ?'
        )->execute([$userId, $transferId]);

        foreach ($items as $item) {
            record_custody(
                (int)$item['tool_id'],
                'Transfer Approved',
                (int)$transfer['from_location_id'],
                (int)$transfer['to_location_id'],
                $item['from_storage_bin_id'] ? (int)$item['from_storage_bin_id'] : null,
                null,
                $transferId,
                $userId,
                'Transfer approved'
            );
        }

        flash('success', 'Transfer approved.');
    } elseif ($action === 'reject' && $transfer['status'] === 'Pending Approval') {
        $pdo->prepare(
            'UPDATE transfer_requests
             SET status = "Rejected", approved_by = ?, rejected_at = NOW()
             WHERE id = ?'
        )->execute([$userId, $transferId]);

        $pdo->prepare(
            'UPDATE transfer_items SET item_status = "Rejected" WHERE transfer_id = ?'
        )->execute([$transferId]);

        foreach ($items as $item) {
            record_custody(
                (int)$item['tool_id'],
                'Transfer Rejected',
                (int)$transfer['from_location_id'],
                (int)$transfer['to_location_id'],
                $item['from_storage_bin_id'] ? (int)$item['from_storage_bin_id'] : null,
                null,
                $transferId,
                $userId,
                'Transfer rejected'
            );
        }

        flash('success', 'Transfer rejected.');
    } elseif ($action === 'ship' && $transfer['status'] === 'Approved') {
        $pdo->prepare(
            'UPDATE transfer_requests
             SET status = "In Transit", shipped_at = NOW()
             WHERE id = ?'
        )->execute([$transferId]);

        $pdo->prepare(
            'UPDATE transfer_items SET item_status = "Shipped" WHERE transfer_id = ?'
        )->execute([$transferId]);

        foreach ($items as $item) {
            $pdo->prepare(
                'UPDATE tools SET status = "In Transit" WHERE id = ?'
            )->execute([(int)$item['tool_id']]);

            record_custody(
                (int)$item['tool_id'],
                'Transfer Shipped',
                (int)$transfer['from_location_id'],
                (int)$transfer['to_location_id'],
                $item['from_storage_bin_id'] ? (int)$item['from_storage_bin_id'] : null,
                null,
                $transferId,
                $userId,
                'Transfer marked in transit'
            );
        }

        flash('success', 'Transfer marked in transit.');
    } elseif ($action === 'receive' && $transfer['status'] === 'In Transit') {
        $toBinId = filter_input(INPUT_POST, 'to_storage_bin_id', FILTER_VALIDATE_INT) ?: null;
        $conditionIn = (string)($_POST['condition_in'] ?? 'Good');

        if (!in_array($conditionIn, ['Excellent', 'Good', 'Fair', 'Poor'], true)) {
            throw new RuntimeException('Invalid received condition.');
        }

        if ($toBinId) {
            $binStmt = $pdo->prepare(
                'SELECT id FROM storage_bins
                 WHERE id = ? AND location_id = ? AND active = 1'
            );
            $binStmt->execute([$toBinId, (int)$transfer['to_location_id']]);

            if (!$binStmt->fetchColumn()) {
                throw new RuntimeException('Selected bin does not belong to the destination location.');
            }
        }

        $pdo->prepare(
            'UPDATE transfer_requests
             SET status = "Received", received_by = ?, received_at = NOW()
             WHERE id = ?'
        )->execute([$userId, $transferId]);

        foreach ($items as $item) {
            $pdo->prepare(
                'UPDATE transfer_items
                 SET item_status = "Received",
                     to_storage_bin_id = ?,
                     condition_in = ?,
                     received_at = NOW()
                 WHERE id = ?'
            )->execute([
                $toBinId,
                $conditionIn,
                (int)$item['id'],
            ]);

            $pdo->prepare(
                'UPDATE tools
                 SET location_id = ?, storage_bin_id = ?,
                     status = "Available", tool_condition = ?
                 WHERE id = ?'
            )->execute([
                (int)$transfer['to_location_id'],
                $toBinId,
                $conditionIn,
                (int)$item['tool_id'],
            ]);

            record_custody(
                (int)$item['tool_id'],
                'Transfer Received',
                (int)$transfer['from_location_id'],
                (int)$transfer['to_location_id'],
                $item['from_storage_bin_id'] ? (int)$item['from_storage_bin_id'] : null,
                $toBinId,
                $transferId,
                $userId,
                'Transfer received'
            );
        }

        flash('success', 'Transfer received.');
    } else {
        throw new RuntimeException('That transfer action is not allowed.');
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('danger', $e->getMessage());
}

redirect('/transfers/view.php?id=' . $transferId);
