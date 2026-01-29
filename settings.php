<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – Time Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; padding-bottom: 50px; }
        .settings-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 560px; }
        .quick-btn-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
        .quick-btn-row input { flex: 1; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Settings</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">← Back to Tracker</a>
    </div>

    <div class="settings-card">
        <h5 class="mb-2">Quick-action buttons</h5>
        <p class="text-muted small mb-3">These appear next to "What are you doing?" so you can start common tasks with one click. Add up to 8.</p>
        <div id="quick-buttons-list"></div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btn-add-quick">+ Add button</button>
        <div class="mt-3">
            <button type="button" class="btn btn-primary" id="btn-save">Save</button>
            <span id="save-status" class="ms-2 text-muted small"></span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const MAX_QUICK_BUTTONS = 8;
let quickButtons = [];

function loadQuickButtons() {
    fetch('api.php?action=quick_buttons')
        .then(res => res.json())
        .then(data => {
            quickButtons = Array.isArray(data) ? data : [];
            renderQuickButtons();
        });
}

function renderQuickButtons() {
    const list = document.getElementById('quick-buttons-list');
    list.innerHTML = '';
    quickButtons.forEach((title, i) => {
        const row = document.createElement('div');
        row.className = 'quick-btn-row';
        row.innerHTML = `
            <input type="text" class="form-control form-control-sm" value="${escapeHtml(title)}" placeholder="e.g. Email, Meeting" data-index="${i}">
            <button type="button" class="btn btn-outline-danger btn-sm remove-quick" data-index="${i}" title="Remove">×</button>
        `;
        list.appendChild(row);
    });
    list.querySelectorAll('.remove-quick').forEach(btn => {
        btn.addEventListener('click', () => {
            quickButtons.splice(parseInt(btn.dataset.index), 1);
            renderQuickButtons();
        });
    });
    list.querySelectorAll('input').forEach(inp => {
        inp.addEventListener('change', () => {
            quickButtons[parseInt(inp.dataset.index)] = inp.value.trim();
        });
    });
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

document.getElementById('btn-add-quick').addEventListener('click', () => {
    if (quickButtons.length >= MAX_QUICK_BUTTONS) {
        document.getElementById('save-status').textContent = 'Maximum ' + MAX_QUICK_BUTTONS + ' buttons.';
        return;
    }
    quickButtons.push('');
    renderQuickButtons();
    const rows = document.getElementById('quick-buttons-list').querySelectorAll('.quick-btn-row');
    const lastInput = rows[rows.length - 1].querySelector('input');
    if (lastInput) lastInput.focus();
});

document.getElementById('btn-save').addEventListener('click', () => {
    const inputs = document.getElementById('quick-buttons-list').querySelectorAll('input');
    const titles = [];
    inputs.forEach(inp => { titles.push(inp.value.trim()); });
    const toSave = titles.filter(t => t !== '');

    fetch('api.php?action=save_quick_buttons', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ titles: toSave })
    })
    .then(res => res.json())
    .then(() => {
        document.getElementById('save-status').textContent = 'Saved.';
        quickButtons = toSave;
        renderQuickButtons();
        setTimeout(() => { document.getElementById('save-status').textContent = ''; }, 2000);
    })
    .catch(() => { document.getElementById('save-status').textContent = 'Error saving.'; });
});

loadQuickButtons();
</script>
</body>
</html>
