<?php
declare(strict_types=1);

require_once __DIR__ . '/../inspections/_common.php';
require_role('Administrator');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'save_template') {
            $templateId = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT) ?: null;
            $name = trim((string)($_POST['name'] ?? ''));
            $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
            $inspectionType = (string)($_POST['inspection_type'] ?? 'Both');
            $active = isset($_POST['active']) ? 1 : 0;

            if ($name === '' || !in_array($inspectionType, ['Checkout', 'Checkin', 'Both'], true)) {
                throw new RuntimeException('Template name and a valid inspection type are required.');
            }

            if ($templateId) {
                $pdo->prepare(
                    'UPDATE inspection_templates
                     SET name=?, category_id=?, inspection_type=?, active=?
                     WHERE id=?'
                )->execute([$name, $categoryId, $inspectionType, $active, $templateId]);
                flash('success', 'Question set updated.');
            } else {
                $pdo->prepare(
                    'INSERT INTO inspection_templates
                     (name, category_id, inspection_type, active)
                     VALUES (?, ?, ?, ?)'
                )->execute([$name, $categoryId, $inspectionType, $active]);
                $templateId = (int)$pdo->lastInsertId();
                flash('success', 'Question set created.');
            }

            redirect('/admin/inspection_questions.php?template_id=' . $templateId);
        }

        if ($action === 'add_question') {
            $templateId = (int)($_POST['template_id'] ?? 0);
            $text = trim((string)($_POST['question_text'] ?? ''));
            $type = (string)($_POST['question_type'] ?? 'YesNo');
            $required = isset($_POST['required']) ? 1 : 0;
            $sortOrder = (int)($_POST['sort_order'] ?? 100);
            $options = trim((string)($_POST['options'] ?? ''));
            $validTypes = ['YesNo','Text','Textarea','Select','Number','Condition'];

            if (!$templateId || $text === '' || !in_array($type, $validTypes, true)) {
                throw new RuntimeException('Question text and a valid question type are required.');
            }

            $optionsJson = null;
            if (in_array($type, ['Select', 'Condition'], true) && $options !== '') {
                $optionsJson = json_encode(array_values(array_filter(array_map('trim', explode(',', $options)))));
            }

            $pdo->prepare(
                'INSERT INTO inspection_questions
                 (template_id, question_text, question_type, options_json, required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$templateId, $text, $type, $optionsJson, $required, $sortOrder]);
            flash('success', 'Question added.');
            redirect('/admin/inspection_questions.php?template_id=' . $templateId);
        }

        if ($action === 'toggle_question') {
            $questionId = (int)($_POST['question_id'] ?? 0);
            $templateId = (int)($_POST['template_id'] ?? 0);
            $pdo->prepare('UPDATE inspection_questions SET active=IF(active=1,0,1) WHERE id=?')->execute([$questionId]);
            flash('success', 'Question status changed.');
            redirect('/admin/inspection_questions.php?template_id=' . $templateId);
        }

        if ($action === 'delete_question') {
            $questionId = (int)($_POST['question_id'] ?? 0);
            $templateId = (int)($_POST['template_id'] ?? 0);
            $pdo->prepare('DELETE FROM inspection_questions WHERE id=?')->execute([$questionId]);
            flash('success', 'Question deleted.');
            redirect('/admin/inspection_questions.php?template_id=' . $templateId);
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect('/admin/inspection_questions.php');
    }
}

$categories = $pdo->query('SELECT id, name FROM tool_categories WHERE active=1 ORDER BY name')->fetchAll();
$templates = $pdo->query(
    'SELECT it.*, tc.name AS category_name,
            (SELECT COUNT(*) FROM inspection_questions iq WHERE iq.template_id=it.id) AS question_count
     FROM inspection_templates it
     LEFT JOIN tool_categories tc ON tc.id=it.category_id
     ORDER BY tc.name IS NULL DESC, tc.name, it.inspection_type, it.name'
)->fetchAll();

$selectedId = filter_input(INPUT_GET, 'template_id', FILTER_VALIDATE_INT)
    ?: (int)($templates[0]['id'] ?? 0);
$selected = null;
foreach ($templates as $template) {
    if ((int)$template['id'] === $selectedId) { $selected = $template; break; }
}
$questions = $selected ? inspection_questions((int)$selected['id']) : [];

$pageTitle = 'Category Inspection Questions';
require __DIR__ . '/../includes/header.php';
?>
<h1>Category Inspection Questions</h1>

<div class="card">
    <h2>Question Sets</h2>
    <p>Create a separate set for each tool category. A set with no category is the fallback.</p>
    <table class="table">
        <thead><tr><th>Name</th><th>Category</th><th>Used For</th><th>Questions</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($templates as $template): ?>
            <tr>
                <td><?= e((string)$template['name']) ?></td>
                <td><?= e((string)($template['category_name'] ?? 'Default / All Categories')) ?></td>
                <td><?= e((string)$template['inspection_type']) ?></td>
                <td><?= (int)$template['question_count'] ?></td>
                <td><?= (int)$template['active'] === 1 ? 'Active' : 'Inactive' ?></td>
                <td><a class="btn secondary" href="?template_id=<?= (int)$template['id'] ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2><?= $selected ? 'Edit Question Set' : 'Create Question Set' ?></h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_template">
        <input type="hidden" name="template_id" value="<?= (int)($selected['id'] ?? 0) ?>">
        <div class="grid">
            <div class="form-group"><label>Name</label><input name="name" required value="<?= e((string)($selected['name'] ?? '')) ?>"></div>
            <div class="form-group"><label>Tool Category</label><select name="category_id"><option value="">Default / All Categories</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)($selected['category_id'] ?? 0)===(int)$category['id']?'selected':'' ?>><?= e((string)$category['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Inspection Type</label><select name="inspection_type"><?php foreach (['Both','Checkout','Checkin'] as $type): ?><option <?= ($selected['inspection_type'] ?? 'Both')===$type?'selected':'' ?>><?= $type ?></option><?php endforeach; ?></select></div>
        </div>
        <label><input type="checkbox" name="active" style="width:auto" <?= !$selected || (int)$selected['active']===1?'checked':'' ?>> Active</label><br><br>
        <button class="btn"><?= $selected ? 'Save Question Set' : 'Create Question Set' ?></button>
        <?php if ($selected): ?><a class="btn secondary" href="?template_id=0">New Question Set</a><?php endif; ?>
    </form>
</div>

<?php if ($selected): ?>
<div class="card">
    <h2>Add Question to <?= e((string)$selected['name']) ?></h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_question">
        <input type="hidden" name="template_id" value="<?= (int)$selected['id'] ?>">
        <div class="form-group"><label>Question</label><input name="question_text" required></div>
        <div class="grid">
            <div class="form-group"><label>Answer Type</label><select name="question_type"><option value="YesNo">Yes / No</option><option value="Text">Short Text</option><option value="Textarea">Long Text</option><option value="Select">Dropdown</option><option value="Number">Number</option><option value="Condition">Condition</option></select></div>
            <div class="form-group"><label>Dropdown Options</label><input name="options" placeholder="Excellent, Good, Fair, Poor, Not Working"></div>
            <div class="form-group"><label>Order</label><input type="number" name="sort_order" value="100"></div>
        </div>
        <label><input type="checkbox" name="required" style="width:auto" checked> Required</label><br><br>
        <button class="btn">Add Question</button>
    </form>
</div>

<div class="card">
    <h2>Questions</h2>
    <table class="table"><thead><tr><th>Order</th><th>Question</th><th>Type</th><th>Required</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($questions as $question): ?><tr>
        <td><?= (int)$question['sort_order'] ?></td><td><?= e((string)$question['question_text']) ?></td><td><?= e((string)$question['question_type']) ?></td><td><?= (int)$question['required']===1?'Yes':'No' ?></td><td><?= (int)$question['active']===1?'Active':'Inactive' ?></td>
        <td><form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="template_id" value="<?= (int)$selected['id'] ?>"><input type="hidden" name="question_id" value="<?= (int)$question['id'] ?>"><button class="btn secondary" name="action" value="toggle_question">Toggle</button> <button class="btn danger" name="action" value="delete_question" onclick="return confirm('Delete this question?')">Delete</button></form></td>
    </tr><?php endforeach; ?>
    </tbody></table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
