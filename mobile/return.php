<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$pageTitle = 'Mobile Return';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Mobile Return</h1>
    <a class="btn secondary" href="<?= BASE_URL ?>/mobile/index.php">Back</a>
</div>

<div class="card">
    <?php require __DIR__ . '/_scanner.php'; ?>

    <div class="form-group">
        <label>Tool Barcode, Internal ID, or Serial</label>
        <input id="returnCode" autocomplete="off">
    </div>

    <div class="grid">
        <div class="form-group">
            <label>Return Condition</label>
            <select id="returnCondition">
                <option>Excellent</option>
                <option selected>Good</option>
                <option>Fair</option>
                <option>Poor</option>
            </select>
        </div>

        <div class="form-group">
            <label>Return Result</label>
            <select id="returnStatus">
                <option value="Returned">Available</option>
                <option value="Inspection">Inspection</option>
                <option value="Repair">Repair</option>
                <option value="Lost">Lost</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label>Notes</label>
        <textarea id="returnNotes" rows="4" style="width:100%;padding:11px"></textarea>
    </div>

    <button class="btn" id="processReturn">Process Return</button>
</div>

<div id="returnResult"></div>

<script>
async function processReturn(value) {
    const response = await fetch('<?= BASE_URL ?>/mobile/api_return.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': '<?= e(csrf_token()) ?>'
        },
        body: JSON.stringify({
            value,
            return_condition: document.getElementById('returnCondition').value,
            return_status: document.getElementById('returnStatus').value,
            notes: document.getElementById('returnNotes').value
        })
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Return failed.');
    }

    document.getElementById('returnResult').innerHTML = `
        <div class="alert success">
            ${data.tool_name} returned successfully.
        </div>
    `;

    document.getElementById('returnCode').value = '';
    document.getElementById('returnNotes').value = '';
}

document.getElementById('startScanner').addEventListener('click', () => {
    startScanner(decodedText => {
        document.getElementById('returnCode').value = decodedText;
        processReturn(decodedText).catch(error => alert(error.message));
    });
});

document.getElementById('processReturn').addEventListener('click', () => {
    processReturn(document.getElementById('returnCode').value.trim())
        .catch(error => alert(error.message));
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
