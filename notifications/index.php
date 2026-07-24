<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$user = current_user();
$userId = (int)($user['id'] ?? 0);

if (isset($_GET['read'])) {
    $id = filter_input(INPUT_GET, 'read', FILTER_VALIDATE_INT);

    if ($id) {
        db()->prepare(
            'UPDATE notifications
             SET is_read = 1, read_at = NOW()
             WHERE id = ? AND user_id = ?'
        )->execute([$id, $userId]);
    }

    redirect('/notifications/index.php');
}

if (isset($_GET['read_all'])) {
    db()->prepare(
        'UPDATE notifications
         SET is_read = 1, read_at = NOW()
         WHERE user_id = ? AND is_read = 0'
    )->execute([$userId]);

    flash('success', 'All notifications marked as read.');
    redirect('/notifications/index.php');
}

$stmt = db()->prepare(
    'SELECT *
     FROM notifications
     WHERE user_id = ?
     ORDER BY is_read ASC, created_at DESC
     LIMIT 250'
);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Notifications</h1>
    <a class="btn secondary" href="?read_all=1">Mark All Read</a>
</div>

<div class="card">
    <?php if (!$rows): ?>
        <p>No notifications.</p>
    <?php endif; ?>

    <?php foreach ($rows as $row): ?>
        <div style="padding:14px 0;border-bottom:1px solid #ddd;<?= (int)$row['is_read'] === 0 ? 'font-weight:600' : '' ?>">
            <div class="actions" style="justify-content:space-between">
                <div>
                    <span class="badge"><?= e((string)$row['severity']) ?></span>
                    <strong><?= e((string)$row['title']) ?></strong>
                </div>
                <span class="muted"><?= e((string)$row['created_at']) ?></span>
            </div>

            <p><?= nl2br(e((string)$row['message'])) ?></p>

            <div class="actions">
                <?php if (!empty($row['action_url'])): ?>
                    <a class="btn secondary" href="<?= e((string)$row['action_url']) ?>">Open</a>
                <?php endif; ?>

                <?php if ((int)$row['is_read'] === 0): ?>
                    <a class="btn secondary" href="?read=<?= (int)$row['id'] ?>">Mark Read</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
