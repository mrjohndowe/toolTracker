<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$key = (string)($_GET['queue'] ?? $_POST['queue'] ?? '');
$queue = $_SESSION['inspection_queues'][$key] ?? null;

if (!is_array($queue)) {
    flash('danger', 'Inspection queue expired or was not found.');
    redirect('/');
}

$index = (int)$queue['index'];
$item = $queue['items'][$index] ?? null;

if (!is_array($item)) {
    $returnPath = (string)($queue['return_path'] ?? '/');
    unset($_SESSION['inspection_queues'][$key]);
    flash('success', 'All required inspections are complete.');
    redirect($returnPath);
}

$template = inspection_template_for_tool((string)$item['type'], (int)$item['tool_id']);
if ($template === null) {
    http_response_code(500);
    exit('No inspection template configured.');
}

$questions = inspection_questions((int)$template['id']);
$stmt = db()->prepare(
    'SELECT t.id, t.name, t.internal_id, t.tool_condition, t.category_id,
            tc.name AS category_name
     FROM tools t
     LEFT JOIN tool_categories tc ON tc.id = t.category_id
     WHERE t.id = ?'
);
$stmt->execute([(int)$item['tool_id']]);
$tool = $stmt->fetch();

if (!is_array($tool)) {
    http_response_code(404);
    exit('Tool not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $user = current_user();

        save_inspection(
            $item,
            is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [],
            $user['id'] ?? null,
            trim((string)($_POST['notes'] ?? '')) ?: null
        );

        $pdo->commit();
        $_SESSION['inspection_queues'][$key]['index'] = $index + 1;
        redirect('/inspections/queue.php?queue=' . rawurlencode($key));
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('danger', $e->getMessage());
    }
}

$pageTitle = $item['type'] . ' Inspection';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= e((string)$item['type']) ?> Inspection</h1>

<div class="card">
    <h2><?= e((string)$tool['name']) ?></h2>
    <p>
        <strong>ID:</strong> <?= e((string)$tool['internal_id']) ?> |
        <strong>Category:</strong> <?= e((string)($tool['category_name'] ?? 'Uncategorized')) ?> |
        <strong>Question set:</strong> <?= e((string)$template['name']) ?> |
        <strong>Questionnaire:</strong> <?= $index + 1 ?> of <?= count($queue['items']) ?>
    </p>
</div>

<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="queue" value="<?= e($key) ?>">

        <?php foreach ($questions as $questionIndex => $question): ?>
            <div class="form-group">
                <label>
                    <?= $questionIndex + 1 ?>.
                    <?= e((string)$question['question_text']) ?>
                    <?= (int)$question['required'] === 1 ? ' *' : '' ?>
                </label>
                <?= inspection_render_field($question) ?>
            </div>
        <?php endforeach; ?>

        <div class="form-group">
            <label>Additional notes</label>
            <textarea name="notes" rows="4"></textarea>
        </div>

        <button class="btn">Save and Continue</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
