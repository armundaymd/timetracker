<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title>Settings · Time Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #f1f5f9;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --accent: #0ea5e9;
            --accent-hover: #0284c7;
            --radius: 14px;
            --radius-sm: 10px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.08);
        }
        * { -webkit-tap-highlight-color: transparent; }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-page);
            color: var(--text);
            padding-bottom: 2rem;
            min-height: 100vh;
        }
        .settings-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            max-width: 560px;
        }
        .quick-btn-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }
        .quick-btn-row input {
            flex: 1;
            min-height: 44px;
            font-size: 1rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        .quick-btn-row input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14,165,233,0.15);
        }
        .quick-btn-row .btn { min-height: 44px; min-width: 44px; border-radius: var(--radius-sm); }
        .btn-primary { background: var(--accent); border: none; font-weight: 600; border-radius: var(--radius-sm); }
        .btn-primary:hover { background: var(--accent-hover); }
        .fw-600 { font-weight: 600; }
    </style>
</head>
<body>
<div class="container py-4 px-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h4 class="mb-0 fw-600">Settings</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm" style="min-height: 44px;">← Back to Tracker</a>
    </div>

    <div class="settings-card">
        <h5 class="mb-2 fw-600">Quick-action buttons</h5>
        <p class="text-muted small mb-3">These appear next to "What are you doing?" so you can start common tasks with one click. Add up to 8.</p>
        <div id="quick-buttons-list"></div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btn-add-quick" style="min-height: 44px;">+ Add button</button>
        <div class="mt-3 d-flex align-items-center flex-wrap gap-2">
            <button type="button" class="btn btn-primary" id="btn-save" style="min-height: 44px;">Save</button>
            <span id="save-status" class="text-muted small"></span>
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
