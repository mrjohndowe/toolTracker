<?php
declare(strict_types=1);

require_once __DIR__ . '/../notifications/_common.php';
require_role('Administrator');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $templateId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim((string)($_POST['name'] ?? ''));
    $channel = (string)($_POST['channel'] ?? 'Both');
    $subject = trim((string)($_POST['subject_template'] ?? ''));
    $body = trim((string)($_POST['body_template'] ?? ''));
    $active = isset($_POST['active']) ? 1 : 0;

    if (!$templateId || $name === '' || $body === '') {
        flash('danger', 'Template name and body are required.');
    } elseif (!in_array($channel, ['In App', 'Email', 'Both'], true)) {
        flash('danger', 'Invalid notification channel.');
    } else {
        db()->prepare(
            'UPDATE notification_templates
             SET name = ?, channel = ?, subject_template = ?,
                 body_template = ?, active = ?
             WHERE id = ?'
        )->execute([
            $name,
            $channel,
            $subject !== '' ? $subject : null,
            $body,
            $active,
            $templateId,
        ]);

        audit_log('notification_template_updated', null, $name);
        flash('success', 'Notification template updated.');
    }

    redirect('/admin/notification_templates.php?id=' . $templateId);
}

$templates = db()->query(
    'SELECT * FROM notification_templates ORDER BY name'
)->fetchAll();

$current = null;

if ($id) {
    $stmt = db()->prepare(
        'SELECT * FROM notification_templates WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $current = $stmt->fetch();
}

$pageTitle = 'Notification Templates';
require __DIR__ . '/../includes/header.php';
?>
<h1>Notification Templates</h1>

<div class="grid">
    <div class="card">
        <h2>Templates</h2>

        <ul>
            <?php foreach ($templates as $template): ?>
                <li>
                    <a href="?id=<?= (int)$template['id'] ?>">
                        <?= e((string)$template['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <?php if ($current): ?>
            <h2>Edit <?= e((string)$current['name']) ?></h2>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$current['id'] ?>">

                <div class="form-group">
                    <label>Name</label>
                    <input name="name" value="<?= e((string)$current['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Channel</label>
                    <select name="channel">
                        <?php foreach (['In App', 'Email', 'Both'] as $channel): ?>
                            <option value="<?= e($channel) ?>" <?= $current['channel'] === $channel ? 'selected' : '' ?>>
                                <?= e($channel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Email Subject</label>
                    <input name="subject_template" value="<?= e((string)($current['subject_template'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label>Body</label>
                    <textarea name="body_template" rows="12" style="width:100%;padding:11px" required><?= e((string)$current['body_template']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" style="width:auto" <?= (int)$current['active'] === 1 ? 'checked' : '' ?>>
                        Active
                    </label>
                </div>

                <button class="btn">Save Template</button>
            </form>
        <?php else: ?>
            <p>Select a template to edit.</p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
