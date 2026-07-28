<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$pageTitle = 'Mobile Checkout';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Mobile Checkout</h1>
    <a class="btn secondary" href="<?= BASE_URL ?>/mobile/index.php">Back</a>
</div>

<div class="card">
    <h2>1. Scan Employee Badge</h2>
    <?php require __DIR__ . '/_scanner.php'; ?>

    <div class="form-group">
        <label>Employee Badge</label>
        <input id="employeeBadge" autocomplete="off">
    </div>

    <button class="btn" id="selectEmployee">Select Employee</button>
    <div id="employeeResult" style="margin-top:12px"></div>
</div>

<div class="card">
    <h2>2. Scan Tools</h2>

    <div class="form-group">
        <label>Tool Barcode, Internal ID, or Serial</label>
        <input id="toolCode" autocomplete="off">
    </div>

    <button class="btn" id="addTool">Add Tool</button>

    <table class="table" style="margin-top:14px">
        <thead><tr><th>Tool</th><th>ID</th><th>Status</th><th></th></tr></thead>
        <tbody id="cartBody">
            <tr id="emptyCart"><td colspan="4">No tools scanned.</td></tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>3. Complete Checkout</h2>

    <div class="grid">
        <div class="form-group">
            <label>Due Date</label>
            <input type="datetime-local" id="dueDate">
        </div>

        <div class="form-group">
            <label>Notes</label>
            <input id="checkoutNotes">
        </div>
    </div>

    <button class="btn" id="completeCheckout">Complete Checkout</button>
</div>

<script>
let selectedEmployee = null;
const cart = new Map();

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

async function apiPost(payload) {
    const response = await fetch('<?= BASE_URL ?>/mobile/api_checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': '<?= e(csrf_token()) ?>'
        },
        body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Request failed.');
    }

    return data;
}

async function selectEmployee(value) {
    const data = await apiPost({ action: 'employee', value });
    selectedEmployee = data.employee;

    document.getElementById('employeeResult').innerHTML = `
        <div class="alert success">
            <strong>${escapeHtml(selectedEmployee.first_name + ' ' + selectedEmployee.last_name)}</strong><br>
            Employee #${escapeHtml(selectedEmployee.employee_number)}
        </div>
    `;
}

async function addTool(value) {
    const data = await apiPost({ action: 'tool', value });

    if (cart.has(data.tool.id)) {
        throw new Error('That tool is already in the cart.');
    }

    cart.set(data.tool.id, data.tool);
    renderCart();
}

function renderCart() {
    const body = document.getElementById('cartBody');

    if (cart.size === 0) {
        body.innerHTML = '<tr><td colspan="4">No tools scanned.</td></tr>';
        return;
    }

    body.innerHTML = '';

    for (const tool of cart.values()) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(tool.name)}</td>
            <td>${escapeHtml(tool.internal_id)}</td>
            <td>${escapeHtml(tool.status)}</td>
            <td><button class="btn secondary" type="button">Remove</button></td>
        `;
        row.querySelector('button').addEventListener('click', () => {
            cart.delete(tool.id);
            renderCart();
        });
        body.appendChild(row);
    }
}

document.getElementById('startScanner').addEventListener('click', () => {
    startScanner(decodedText => {
        if (!selectedEmployee) {
            document.getElementById('employeeBadge').value = decodedText;
            selectEmployee(decodedText).catch(error => alert(error.message));
        } else {
            document.getElementById('toolCode').value = decodedText;
            addTool(decodedText).catch(error => alert(error.message));
        }
    });
});

document.getElementById('selectEmployee').addEventListener('click', () => {
    selectEmployee(document.getElementById('employeeBadge').value.trim())
        .catch(error => alert(error.message));
});

document.getElementById('addTool').addEventListener('click', () => {
    addTool(document.getElementById('toolCode').value.trim())
        .then(() => document.getElementById('toolCode').value = '')
        .catch(error => alert(error.message));
});

document.getElementById('completeCheckout').addEventListener('click', async () => {
    if (!selectedEmployee) {
        alert('Select an employee first.');
        return;
    }

    if (cart.size === 0) {
        alert('Add at least one tool.');
        return;
    }

    try {
        const data = await apiPost({
            action: 'complete',
            employee_id: selectedEmployee.id,
            tool_ids: Array.from(cart.keys()),
            due_date: document.getElementById('dueDate').value,
            notes: document.getElementById('checkoutNotes').value
        });

        window.location.href = data.inspection_url;
    } catch (error) {
        alert(error.message);
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
