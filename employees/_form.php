<?php $departments = employee_departments(); ?>
<div class="grid">
    <div class="form-group">
        <label>Employee Number *</label>
        <input name="employee_number" value="<?= e((string)$values['employee_number']) ?>" required>
    </div>

    <div class="form-group">
        <label>Badge Code *</label>
        <input name="badge_code" value="<?= e((string)$values['badge_code']) ?>" required>
    </div>

    <div class="form-group">
        <label>First Name *</label>
        <input name="first_name" value="<?= e((string)$values['first_name']) ?>" required>
    </div>

    <div class="form-group">
        <label>Last Name *</label>
        <input name="last_name" value="<?= e((string)$values['last_name']) ?>" required>
    </div>

    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e((string)$values['email']) ?>">
    </div>

    <div class="form-group">
        <label>Phone</label>
        <input name="phone" value="<?= e((string)$values['phone']) ?>">
    </div>

    <div class="form-group">
        <label>Department</label>
        <select name="department_id">
            <option value="">No department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= (int)$department['id'] ?>"
                    <?= (int)($values['department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>>
                    <?= e((string)$department['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Job Title</label>
        <input name="job_title" value="<?= e((string)$values['job_title']) ?>">
    </div>

    <div class="form-group">
        <label>Supervisor</label>
        <input name="supervisor_name" value="<?= e((string)$values['supervisor_name']) ?>">
    </div>

    <div class="form-group">
        <label>Hire Date</label>
        <input type="date" name="hire_date" value="<?= e((string)$values['hire_date']) ?>">
    </div>

    <div class="form-group">
        <label>Status</label>
        <select name="status">
            <?php foreach (employee_statuses() as $status): ?>
                <option value="<?= e($status) ?>" <?= $values['status'] === $status ? 'selected' : '' ?>>
                    <?= e($status) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="form-group">
    <label>Notes</label>
    <textarea name="notes" rows="5" style="width:100%;padding:11px"><?= e((string)$values['notes']) ?></textarea>
</div>

<div class="form-group">
    <label>
        <input type="checkbox" name="active" style="width:auto" <?= (int)$values['active'] === 1 ? 'checked' : '' ?>>
        Active
    </label>
</div>
