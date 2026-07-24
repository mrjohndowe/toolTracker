<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

$user = current_user();
$userId = (int)($user['id'] ?? 0);

$count = 0;

if ($userId > 0) {
    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM notifications
         WHERE user_id = ? AND is_read = 0'
    );
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetchColumn();
}
?>
<a href="<?= BASE_URL ?>/notifications/index.php">
    Notifications<?= $count > 0 ? ' (' . $count . ')' : '' ?>
</a>
