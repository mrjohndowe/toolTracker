<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$employee = $id ? find_employee($id) : null;

if ($employee === null) {
    http_response_code(404);
    exit('Employee not found.');
}

$photos = db()->prepare(
    'SELECT * FROM employee_photos
     WHERE employee_id = ?
     ORDER BY is_primary DESC, id DESC'
);
$photos->execute([$id]);
$photos = $photos->fetchAll();

$history = db()->prepare(
    'SELECT h.*, u.username
     FROM employee_history h
     LEFT JOIN users u ON u.id = h.changed_by
     WHERE h.employee_id = ?
     ORDER BY h.id DESC'
);
$history->execute([$id]);
$history = $history->fetchAll();

$pageTitle = $employee['first_name'] . ' ' . $employee['last_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <div>
        <h1><?= e((string)$employee['first_name'] . ' ' . (string)$employee['last_name']) ?></h1>
        <div class="muted">Employee #<?= e((string)$employee['employee_number']) ?></div>
    </div>

    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/employees/badge.php?id=<?= $id ?>" target="_blank">Print Badge</a>
        <a class="btn" href="<?= BASE_URL ?>/employees/edit.php?id=<?= $id ?>">Edit Employee</a>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2>Employment</h2>
        <p><strong>Badge:</strong> <?= e((string)$employee['badge_code']) ?></p>
        <p><strong>Department:</strong> <?= e((string)($employee['department_name'] ?? '')) ?></p>
        <p><strong>Job Title:</strong> <?= e((string)($employee['job_title'] ?? '')) ?></p>
        <p><strong>Supervisor:</strong> <?= e((string)($employee['supervisor_name'] ?? '')) ?></p>
        <p><strong>Hire Date:</strong> <?= e((string)($employee['hire_date'] ?? '')) ?></p>
    </div>

    <div class="card">
        <h2>Contact and Status</h2>
        <p><strong>Email:</strong> <?= e((string)($employee['email'] ?? '')) ?></p>
        <p><strong>Phone:</strong> <?= e((string)($employee['phone'] ?? '')) ?></p>
        <p><strong>Status:</strong> <span class="badge"><?= e((string)$employee['status']) ?></span></p>
        <p><strong>Active Record:</strong> <?= (int)$employee['active'] === 1 ? 'Yes' : 'No' ?></p>
    </div>
</div>

<div class="card">
    <h2>Notes</h2>
    <p><?= nl2br(e((string)($employee['notes'] ?? 'No notes.'))) ?></p>
</div>

<div class="card">
    <div class="actions" style="justify-content:space-between">
        <h2>Photos</h2>
        <a class="btn" href="<?= BASE_URL ?>/employees/upload_photo.php?id=<?= $id ?>">Upload Photo</a>
    </div>

    <?php if (!$photos): ?>
        <p>No photos uploaded.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($photos as $photo): ?>
                <div>
                    <img src="<?= BASE_URL ?>/uploads/employees/<?= e((string)$photo['filename']) ?>"
                         alt="Employee photo"
                         style="width:100%;max-height:280px;object-fit:cover;border-radius:8px">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Employee History</h2>

    <table class="table">
        <thead>
        <tr><th>Date</th><th>Action</th><th>User</th><th>Details</th></tr>
        </thead>
        <tbody>
        <?php foreach ($history as $row): ?>
            <tr>
                <td><?= e((string)$row['created_at']) ?></td>
                <td><?= e((string)$row['action']) ?></td>
                <td><?= e((string)($row['username'] ?? 'System')) ?></td>
                <td><?= e((string)($row['details'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
