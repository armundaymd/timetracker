<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a" id="meta-theme-color">
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
        html.dark {
            --bg-page: #0f172a;
            --bg-card: #1e293b;
            --border: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --accent-hover: #0ea5e9;
            --shadow: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.4);
        }
        html.dark .text-muted { color: var(--text-muted) !important; }
        html.dark .form-control, html.dark .form-select { background: #334155; border-color: var(--border); color: var(--text); }
        html.dark .btn-outline-primary { border-color: var(--accent); color: var(--accent); }
        html.dark .btn-outline-primary:hover { background: var(--accent); color: #0f172a; }
        html.dark .btn-outline-secondary { border-color: var(--border); color: var(--text-muted); }
        html.dark .btn-outline-danger { border-color: #f87171; color: #f87171; }
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
<script>
(function(){var d=localStorage.getItem('darkMode');if(d==='true')document.documentElement.classList.add('dark');var m=document.getElementById('meta-theme-color');if(m)m.content=document.documentElement.classList.contains('dark')?'#0f172a':'#0f172a';})();
</script>
<div class="container py-4 px-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h4 class="mb-0 fw-600">Settings</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm" style="min-height: 44px;">← Back to Tracker</a>
    </div>

    <div class="settings-card mb-4">
        <h5 class="mb-2 fw-600">Appearance</h5>
        <div class="d-flex align-items-center justify-content-between">
            <span>Dark mode</span>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="dark-mode-toggle" style="cursor: pointer;">
                <label class="form-check-label" for="dark-mode-toggle"></label>
            </div>
        </div>
    </div>

    <div class="settings-card mb-4">
        <h5 class="mb-2 fw-600">Change password</h5>
        <p class="text-muted small mb-3">Enter your current password and choose a new one (at least 6 characters).</p>
        <div class="mb-3">
            <label class="form-label">Current password</label>
            <input type="password" id="current-password" class="form-control" style="font-size: 1rem; min-height: 44px;" autocomplete="current-password">
        </div>
        <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" id="new-password" class="form-control" style="font-size: 1rem; min-height: 44px;" autocomplete="new-password">
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input type="password" id="confirm-password" class="form-control" style="font-size: 1rem; min-height: 44px;" autocomplete="new-password">
        </div>
        <span id="password-status" class="text-muted small d-block mb-2"></span>
        <button type="button" class="btn btn-primary" id="btn-change-password" style="min-height: 44px;">Change password</button>
    </div>

    <div class="settings-card mb-4">
        <h5 class="mb-2 fw-600">Projects (categories)</h5>
        <p class="text-muted small mb-3">Type <strong>ProjectName: task</strong> in the timer (e.g. Work: Fix bug) to categorize. Analytics will group by project.</p>
        <div id="projects-list"></div>
        <div class="d-flex gap-2 mt-2 flex-wrap">
            <input type="text" id="new-project-name" class="form-control" placeholder="New project name" style="max-width: 180px; min-height: 44px;">
            <input type="color" id="new-project-color" value="#0ea5e9" title="Color" style="width: 44px; height: 44px; padding: 2px; cursor: pointer; border-radius: var(--radius-sm);">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-project" style="min-height: 44px;">Add project</button>
        </div>
        <span id="project-status" class="text-muted small d-block mt-2"></span>
    </div>

    <div class="settings-card mb-4">
        <h5 class="mb-2 fw-600">Data export</h5>
        <p class="text-muted small mb-2">Download all your tasks as JSON for backup.</p>
        <a href="api.php?action=export" class="btn btn-outline-secondary" style="min-height: 44px;" download>Export to JSON</a>
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

function loadProjects() {
    fetch('api.php?action=projects')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('projects-list');
            list.innerHTML = '';
            (data || []).forEach(p => {
                const row = document.createElement('div');
                row.className = 'quick-btn-row align-items-center';
                row.innerHTML = `
                    <input type="color" value="${p.color || '#0ea5e9'}" data-id="${p.id}" class="project-color" style="width: 28px; height: 28px; padding: 0; border: none; cursor: pointer; border-radius: 6px;">
                    <input type="text" class="form-control form-control-sm" value="${escapeHtml(p.name)}" data-id="${p.id}" style="flex: 1; max-width: 200px;">
                    <button type="button" class="btn btn-outline-danger btn-sm delete-project" data-id="${p.id}" title="Delete">×</button>
                `;
                list.appendChild(row);
            });
            list.querySelectorAll('.delete-project').forEach(btn => {
                btn.addEventListener('click', () => {
                    fetch('api.php?action=delete_project', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: btn.dataset.id }) })
                        .then(() => loadProjects());
                });
            });
            list.querySelectorAll('.project-color, .form-control[data-id]').forEach(el => {
                if (el.classList.contains('project-color')) {
                    el.addEventListener('change', () => {
                        const name = list.querySelector('input[data-id="' + el.dataset.id + '"]:not(.project-color)').value.trim();
                        if (name) fetch('api.php?action=save_project', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: parseInt(el.dataset.id), name, color: el.value }) });
                    });
                } else {
                    el.addEventListener('blur', () => {
                        const colorEl = list.querySelector('.project-color[data-id="' + el.dataset.id + '"]');
                        if (el.value.trim() && colorEl) fetch('api.php?action=save_project', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: parseInt(el.dataset.id), name: el.value.trim(), color: colorEl.value }) });
                    });
                }
            });
        });
}
document.getElementById('btn-add-project').addEventListener('click', () => {
    const name = document.getElementById('new-project-name').value.trim();
    const color = document.getElementById('new-project-color').value;
    if (!name) { document.getElementById('project-status').textContent = 'Enter a name.'; return; }
    fetch('api.php?action=save_project', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name, color }) })
        .then(res => res.json())
        .then(() => { loadProjects(); document.getElementById('new-project-name').value = ''; document.getElementById('project-status').textContent = 'Added.'; setTimeout(() => document.getElementById('project-status').textContent = '', 2000); })
        .catch(() => document.getElementById('project-status').textContent = 'Error.');
});
loadProjects();

(function() {
    var toggle = document.getElementById('dark-mode-toggle');
    var meta = document.getElementById('meta-theme-color');
    if (localStorage.getItem('darkMode') === 'true') toggle.checked = true;
    function applyDark(enabled) {
        if (enabled) { document.documentElement.classList.add('dark'); localStorage.setItem('darkMode', 'true'); }
        else { document.documentElement.classList.remove('dark'); localStorage.setItem('darkMode', 'false'); }
        if (meta) meta.content = enabled ? '#0f172a' : '#f1f5f9';
    }
    toggle.addEventListener('change', function() { applyDark(toggle.checked); });
})();

document.getElementById('btn-change-password').addEventListener('click', function() {
    var current = document.getElementById('current-password').value;
    var newP = document.getElementById('new-password').value;
    var confirmP = document.getElementById('confirm-password').value;
    var status = document.getElementById('password-status');
    status.textContent = '';
    if (!current || !newP) { status.textContent = 'Fill in all fields.'; status.classList.add('text-danger'); return; }
    if (newP !== confirmP) { status.textContent = 'New passwords do not match.'; status.classList.add('text-danger'); return; }
    if (newP.length < 6) { status.textContent = 'New password must be at least 6 characters.'; status.classList.add('text-danger'); return; }
    fetch('api.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ current_password: current, new_password: newP })
    })
    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
    .then(function(result) {
        if (result.ok) {
            status.textContent = 'Password updated.';
            status.classList.remove('text-danger');
            status.classList.add('text-success');
            document.getElementById('current-password').value = '';
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-password').value = '';
            setTimeout(function() { status.textContent = ''; }, 3000);
        } else {
            status.textContent = result.data.error || 'Failed to change password.';
            status.classList.add('text-danger');
        }
    })
    .catch(function() { status.textContent = 'Network error.'; status.classList.add('text-danger'); });
});
</script>
</body>
</html>
