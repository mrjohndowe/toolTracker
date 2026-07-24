<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$pageTitle = 'Mobile Lookup';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Quick Lookup</h1>
    <a class="btn secondary" href="<?= BASE_URL ?>/mobile/index.php">Back</a>
</div>

<div class="card">
    <?php require __DIR__ . '/_scanner.php'; ?>

    <div class="form-group">
        <label>Scanned Value</label>
        <input id="lookupValue" placeholder="Barcode, serial, internal ID, or badge">
    </div>

    <button class="btn" id="lookupButton">Lookup</button>
</div>

<div id="result"></div>

<script>
const valueInput = document.getElementById('lookupValue');
const result = document.getElementById('result');

async function lookup(value) {
    if (!value) return;

    result.innerHTML = '<div class="card">Looking up...</div>';

    const response = await fetch(
        '<?= BASE_URL ?>/mobile/api_lookup.php?value=' + encodeURIComponent(value),
        { headers: { 'Accept': 'application/json' } }
    );

    const data = await response.json();

    if (!data.success) {
        result.innerHTML = '<div class="alert danger">' + escapeHtml(data.message) + '</div>';
        return;
    }

    const item = data.item;

    if (data.type === 'tool') {
        result.innerHTML = `
            <div class="card">
                <h2>${escapeHtml(item.name)}</h2>
                <p><strong>Internal ID:</strong> ${escapeHtml(item.internal_id || '')}</p>
                <p><strong>Barcode:</strong> ${escapeHtml(item.barcode || '')}</p>
                <p><strong>Serial:</strong> ${escapeHtml(item.serial_number || '')}</p>
                <p><strong>Status:</strong> ${escapeHtml(item.status || '')}</p>
                <p><strong>Condition:</strong> ${escapeHtml(item.tool_condition || '')}</p>
                <a class="btn" href="<?= BASE_URL ?>/tools/view.php?id=${item.id}">Open Tool</a>
            </div>
        `;
    } else {
        result.innerHTML = `
            <div class="card">
                <h2>${escapeHtml(item.first_name + ' ' + item.last_name)}</h2>
                <p><strong>Employee Number:</strong> ${escapeHtml(item.employee_number || '')}</p>
                <p><strong>Badge:</strong> ${escapeHtml(item.badge_code || '')}</p>
                <p><strong>Status:</strong> ${escapeHtml(item.status || '')}</p>
                <a class="btn" href="<?= BASE_URL ?>/employees/view.php?id=${item.id}">Open Employee</a>
            </div>
        `;
    }
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

document.getElementById('startScanner').addEventListener('click', () => {
    startScanner(decodedText => {
        valueInput.value = decodedText;
        lookup(decodedText);
    });
});

document.getElementById('lookupButton').addEventListener('click', () => lookup(valueInput.value.trim()));

valueInput.addEventListener('keydown', event => {
    if (event.key === 'Enter') lookup(valueInput.value.trim());
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
