<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$department = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: null;

$sql = '
    SELECT
        e.id, e.employee_number, e.badge_code, e.first_name, e.last_name,
        e.email, e.phone, e.job_title, e.status, e.active,
        d.name AS department_name
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    WHERE 1=1
';
$params = [];

if ($q !== '') {
    $sql .= ' AND (
        e.employee_number LIKE ? OR e.badge_code LIKE ? OR
        e.first_name LIKE ? OR e.last_name LIKE ? OR
        e.email LIKE ? OR e.job_title LIKE ?
    )';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($status !== '' && in_array($status, employee_statuses(), true)) {
    $sql .= ' AND e.status = ?';
    $params[] = $status;
}

if ($department !== null) {
    $sql .= ' AND e.department_id = ?';
    $params[] = $department;
}

$sql .= ' ORDER BY e.active DESC, e.last_name, e.first_name';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$pageTitle = 'Employees';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Employees</h1>
    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/employees/departments.php">Departments</a>
        <a class="btn" href="<?= BASE_URL ?>/employees/add.php">Add Employee</a>
    </div>
</div>

<div class="card">
    <form method="get">
        <div class="grid">
            <div class="form-group">
                <label>Search</label>
                <input name="q" value="<?= e($q) ?>" placeholder="Name, badge, employee number...">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (employee_statuses() as $item): ?>
                        <option value="<?= e($item) ?>" <?= $status === $item ? 'selected' : '' ?>>
                            <?= e($item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Department</label>
                <select name="department_id">
                    <option value="">All departments</option>
                    <?php foreach (employee_departments() as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= $department === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= e((string)$item['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="actions">
            <button class="btn">Filter</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/employees/index.php">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Employee #</th>
            <th>Badge</th>
            <th>Department</th>
            <th>Job Title</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$employees): ?>
            <tr><td colspan="7">No employees found.</td></tr>
        <?php endif; ?>

        <?php foreach ($employees as $employee): ?>
            <tr>
                <td>
                    <strong><?= e((string)$employee['first_name'] . ' ' . (string)$employee['last_name']) ?></strong><br>
                    <span class="muted"><?= e((string)($employee['email'] ?? '')) ?></span>
                </td>
                <td><?= e((string)$employee['employee_number']) ?></td>
                <td><?= e((string)$employee['badge_code']) ?></td>
                <td><?= e((string)($employee['department_name'] ?? '')) ?></td>
                <td><?= e((string)($employee['job_title'] ?? '')) ?></td>
                <td>
                    <span class="badge"><?= e((string)$employee['status']) ?></span>
                    <?php if ((int)$employee['active'] !== 1): ?>
                        <span class="badge">Inactive Record</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a class="btn secondary" href="<?= BASE_URL ?>/employees/view.php?id=<?= (int)$employee['id'] ?>">View</a>
                    <a class="btn secondary" href="<?= BASE_URL ?>/employees/edit.php?id=<?= (int)$employee['id'] ?>">Edit</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
