<?php
declare(strict_types=1);

require_once __DIR__ . '/../notifications/_common.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    $leadDays = max(0, (int)($_POST['lead_days'] ?? 0));
    $repeatDays = trim((string)($_POST['repeat_days'] ?? ''));
    $emailEnabled = isset($_POST['email_enabled']) ? 1 : 0;
    $inAppEnabled = isset($_POST['in_app_enabled']) ? 1 : 0;

    if ($id) {
        db()->prepare(
            'UPDATE notification_rules
             SET enabled = ?, lead_days = ?, repeat_days = ?,
                 email_enabled = ?, in_app_enabled = ?
             WHERE id = ?'
        )->execute([
            $enabled,
            $leadDays,
            $repeatDays !== '' ? max(1, (int)$repeatDays) : null,
            $emailEnabled,
            $inAppEnabled,
            $id,
        ]);

        flash('success', 'Notification rule updated.');
    }

    redirect('/admin/notification_rules.php');
}

$rows = db()->query(
    'SELECT * FROM notification_rules ORDER BY name'
)->fetchAll();

$pageTitle = 'Notification Rules';
require __DIR__ . '/../includes/header.php';
?>
<h1>Notification Rules</h1>

<?php foreach ($rows as $row): ?>
    <div class="card">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

            <h2><?= e((string)$row['name']) ?></h2>

            <div class="grid">
                <div class="form-group">
                    <label>Lead Days</label>
                    <input type="number" min="0" name="lead_days" value="<?= (int)$row['lead_days'] ?>">
                </div>

                <div class="form-group">
                    <label>Repeat Every (Days)</label>
                    <input type="number" min="1" name="repeat_days" value="<?= e((string)($row['repeat_days'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="enabled" style="width:auto" <?= (int)$row['enabled'] === 1 ? 'checked' : '' ?>> Enabled</label>
                <label><input type="checkbox" name="email_enabled" style="width:auto" <?= (int)$row['email_enabled'] === 1 ? 'checked' : '' ?>> Email</label>
                <label><input type="checkbox" name="in_app_enabled" style="width:auto" <?= (int)$row['in_app_enabled'] === 1 ? 'checked' : '' ?>> In App</label>
            </div>

            <button class="btn">Save Rule</button>
        </form>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
