<?php
$categories = tool_categories();
$locations = tool_locations();
?>
<div class="grid">
    <div class="form-group">
        <label>Internal ID *</label>
        <input name="internal_id" value="<?= e((string)$values['internal_id']) ?>" required>
    </div>

    <div class="form-group">
        <label>Barcode *</label>
        <input name="barcode" value="<?= e((string)$values['barcode']) ?>" required>
    </div>

    <div class="form-group">
        <label>Serial Number</label>
        <input name="serial_number" value="<?= e((string)$values['serial_number']) ?>">
    </div>

    <div class="form-group">
        <label>Tool Name *</label>
        <input name="name" value="<?= e((string)$values['name']) ?>" required>
    </div>

    <div class="form-group">
        <label>Manufacturer</label>
        <input name="manufacturer" value="<?= e((string)$values['manufacturer']) ?>">
    </div>

    <div class="form-group">
        <label>Model</label>
        <input name="model" value="<?= e((string)$values['model']) ?>">
    </div>

    <div class="form-group">
        <label>Category</label>
        <select name="category_id">
            <option value="">No category</option>
            <?php foreach ($categories as $item): ?>
                <option value="<?= (int)$item['id'] ?>"
                    <?= (int)($values['category_id'] ?? 0) === (int)$item['id'] ? 'selected' : '' ?>>
                    <?= e((string)$item['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Location</label>
        <select name="location_id">
            <option value="">No location</option>
            <?php foreach ($locations as $item): ?>
                <option value="<?= (int)$item['id'] ?>"
                    <?= (int)($values['location_id'] ?? 0) === (int)$item['id'] ? 'selected' : '' ?>>
                    <?= e((string)$item['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Status</label>
        <select name="status">
            <?php foreach (tool_statuses() as $item): ?>
                <option value="<?= e($item) ?>" <?= $values['status'] === $item ? 'selected' : '' ?>>
                    <?= e($item) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Condition</label>
        <select name="tool_condition">
            <?php foreach (tool_conditions() as $item): ?>
                <option value="<?= e($item) ?>" <?= $values['tool_condition'] === $item ? 'selected' : '' ?>>
                    <?= e($item) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Purchase Date</label>
        <input type="date" name="purchase_date" value="<?= e((string)$values['purchase_date']) ?>">
    </div>

    <div class="form-group">
        <label>Replacement Value</label>
        <input type="number" step="0.01" min="0" name="replacement_value"
               value="<?= e(number_format((float)$values['replacement_value'], 2, '.', '')) ?>">
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
