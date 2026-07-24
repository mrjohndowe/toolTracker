<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function generate_transaction_number(): string
{
    return 'TX-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function find_employee_by_badge(string $badge): ?array
{
    $stmt = db()->prepare(
        'SELECT e.*, d.name AS department_name
         FROM employees e
         LEFT JOIN departments d ON d.id = e.department_id
         WHERE e.badge_code = ? AND e.active = 1 AND e.status = "Active"
         LIMIT 1'
    );
    $stmt->execute([$badge]);
    $employee = $stmt->fetch();

    return is_array($employee) ? $employee : null;
}

function find_tool_by_scan(string $value): ?array
{
    $stmt = db()->prepare(
        'SELECT *
         FROM tools
         WHERE (barcode = ? OR internal_id = ? OR serial_number = ?)
           AND active = 1
         LIMIT 1'
    );
    $stmt->execute([$value, $value, $value]);
    $tool = $stmt->fetch();

    return is_array($tool) ? $tool : null;
}

function record_scan(
    string $type,
    string $value,
    bool $success,
    ?string $message = null,
    ?int $employeeId = null,
    ?int $toolId = null,
    ?int $transactionId = null
): void {
    $user = current_user();

    db()->prepare(
        'INSERT INTO scan_history
         (scan_type, scanned_value, employee_id, tool_id, transaction_id,
          success, message, scanned_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $type,
        $value,
        $employeeId,
        $toolId,
        $transactionId,
        $success ? 1 : 0,
        $message,
        $user['id'] ?? null,
    ]);
}

function checkout_cart(): array
{
    $cart = $_SESSION['checkout_cart'] ?? [];
    return is_array($cart) ? $cart : [];
}

function save_checkout_cart(array $cart): void
{
    $_SESSION['checkout_cart'] = $cart;
}

function clear_checkout_cart(): void
{
    unset($_SESSION['checkout_cart'], $_SESSION['checkout_employee']);
}

function current_checkout_employee(): ?array
{
    $employee = $_SESSION['checkout_employee'] ?? null;
    return is_array($employee) ? $employee : null;
}

function update_transaction_status(int $transactionId): void
{
    $stmt = db()->prepare(
        'SELECT
            COUNT(*) AS total_count,
            SUM(return_status = "Pending") AS pending_count
         FROM checkout_items
         WHERE transaction_id = ?'
    );
    $stmt->execute([$transactionId]);
    $counts = $stmt->fetch();

    $total = (int)($counts['total_count'] ?? 0);
    $pending = (int)($counts['pending_count'] ?? 0);

    if ($total === 0) {
        return;
    }

    if ($pending === 0) {
        $user = current_user();

        db()->prepare(
            'UPDATE checkout_transactions
             SET status = "Closed", returned_date = NOW(), closed_by = ?
             WHERE id = ?'
        )->execute([$user['id'] ?? null, $transactionId]);
    } elseif ($pending < $total) {
        db()->prepare(
            'UPDATE checkout_transactions
             SET status = "Partially Returned"
             WHERE id = ?'
        )->execute([$transactionId]);
    }
}
