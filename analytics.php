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
    <title>Analytics · Time Tracker</title>
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
        .analytics-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }
        .stat-big { font-size: 2rem; font-weight: 700; color: var(--text); }
        .stat-label { font-size: 0.875rem; color: var(--text-muted); }
        .bar-wrap { height: 24px; background: var(--border); border-radius: 999px; overflow: hidden; margin-bottom: 0.5rem; }
        .bar-fill { height: 100%; border-radius: 999px; background: var(--accent); min-width: 4px; transition: width 0.3s ease; }
        .fw-600 { font-weight: 600; }
        .activity-row { align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--border); }
        .activity-row:last-child { border-bottom: none; }
        html.dark {
            --bg-page: #0f172a;
            --bg-card: #1e293b;
            --border: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --accent-hover: #0284c7;
            --shadow: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.4);
        }
        html.dark .form-control { background: #334155; border-color: var(--border); color: var(--text); }
        html.dark .btn-outline-secondary { border-color: var(--border); color: var(--text-muted); }
        .heatmap-wrap { display: flex; flex-wrap: wrap; gap: 3px; margin-top: 0.5rem; }
        .heatmap-cell { width: 12px; height: 12px; border-radius: 2px; background: var(--border); }
        .heatmap-cell[data-level="0"] { background: #e2e8f0; }
        .heatmap-cell[data-level="1"] { background: #86efac; }
        .heatmap-cell[data-level="2"] { background: #22c55e; }
        .heatmap-cell[data-level="3"] { background: #15803d; }
        html.dark .heatmap-cell[data-level="0"] { background: #334155; }
        html.dark .heatmap-cell[data-level="1"] { background: #166534; }
        html.dark .heatmap-cell[data-level="2"] { background: #22c55e; }
        html.dark .heatmap-cell[data-level="3"] { background: #86efac; }
        .heatmap-legend { display: flex; gap: 8px; align-items: center; margin-top: 8px; font-size: 0.75rem; color: var(--text-muted); }
    </style>
</head>
<body>
<script>
(function(){var d=localStorage.getItem('darkMode');if(d==='true')document.documentElement.classList.add('dark');var m=document.getElementById('meta-theme-color');if(m)m.content=document.documentElement.classList.contains('dark')?'#0f172a':'#f1f5f9';})();
</script>
<div class="container py-4 px-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h4 class="mb-0 fw-600">Analytics</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm" style="min-height: 44px;">← Back to Tracker</a>
    </div>

    <div class="analytics-card">
        <label class="form-label fw-600">Date range</label>
        <div class="row g-2 mb-3">
            <div class="col-sm">
                <input type="date" id="range-start" class="form-control" style="font-size: 1rem; min-height: 44px;">
            </div>
            <div class="col-sm">
                <input type="date" id="range-end" class="form-control" style="font-size: 1rem; min-height: 44px;">
            </div>
            <div class="col-sm">
                <button type="button" class="btn btn-primary w-100" id="btn-apply" style="min-height: 44px;">Apply</button>
            </div>
        </div>
        <div class="d-flex gap-4 flex-wrap mb-3">
            <div>
                <div class="stat-big" id="stat-total">0h 0m</div>
                <div class="stat-label">Total tracked time</div>
            </div>
            <div>
                <div class="stat-big" id="stat-activities">0</div>
                <div class="stat-label">Activities</div>
            </div>
        </div>
    </div>

    <div class="analytics-card">
        <h5 class="mb-2 fw-600">Contribution heatmap (last 365 days)</h5>
        <p class="text-muted small mb-2">Tracked time per day. Don’t break the chain.</p>
        <div id="heatmap-container" class="heatmap-wrap"></div>
        <div class="heatmap-legend">
            <span>Less</span>
            <span class="heatmap-cell" data-level="0" style="width:10px;height:10px;"></span>
            <span class="heatmap-cell" data-level="1" style="width:10px;height:10px;"></span>
            <span class="heatmap-cell" data-level="2" style="width:10px;height:10px;"></span>
            <span class="heatmap-cell" data-level="3" style="width:10px;height:10px;"></span>
            <span>More</span>
        </div>
    </div>

    <div class="analytics-card">
        <h5 class="mb-3 fw-600">Time by activity</h5>
        <div id="activity-list"></div>
        <p id="no-data" class="text-muted small mb-0" style="display: none;">No tracked time in this range.</p>
    </div>

    <div class="analytics-card" id="by-project-card">
        <h5 class="mb-3 fw-600">Time by project</h5>
        <div id="by-project-list"></div>
        <p id="no-project-data" class="text-muted small mb-0" style="display: none;">No project time in this range. Use <strong>Project: task</strong> to categorize.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function formatDuration(seconds) {
    if (!seconds || seconds < 0) return '0h 0m';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return h + 'h ' + m + 'm';
    return m + 'm';
}

function heatmapLevel(seconds) {
    if (!seconds || seconds < 3600) return 0;
    const h = seconds / 3600;
    if (h < 4) return 1;
    if (h < 8) return 2;
    return 3;
}

function loadHeatmap(heatmapDays) {
    const container = document.getElementById('heatmap-container');
    container.innerHTML = '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const startDate = new Date(today);
    startDate.setDate(startDate.getDate() - 364);
    for (let i = 0; i < 365; i++) {
        const d = new Date(startDate);
        d.setDate(d.getDate() + i);
        const key = d.toISOString().slice(0, 10);
        const sec = heatmapDays[key] || 0;
        const level = heatmapLevel(sec);
        const cell = document.createElement('span');
        cell.className = 'heatmap-cell';
        cell.setAttribute('data-level', level);
        cell.title = key + ': ' + formatDuration(sec);
        container.appendChild(cell);
    }
}

function loadAnalytics() {
    const start = document.getElementById('range-start').value;
    const end = document.getElementById('range-end').value;
    if (!start || !end) return;
    fetch('api.php?action=analytics&start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end))
        .then(res => res.json())
        .then(data => {
            const total = data.total_seconds || 0;
            const activities = data.by_activity || [];
            const byProject = data.by_project || [];
            const heatmapDays = data.heatmap_days || {};
            document.getElementById('stat-total').textContent = formatDuration(total);
            document.getElementById('stat-activities').textContent = activities.length;
            loadHeatmap(heatmapDays);
            const listEl = document.getElementById('activity-list');
            const noData = document.getElementById('no-data');
            listEl.innerHTML = '';
            if (activities.length === 0) {
                noData.style.display = 'block';
            } else {
                noData.style.display = 'none';
                const maxSeconds = Math.max(...activities.map(a => parseInt(a.seconds, 10)), 1);
                activities.forEach(function(item) {
                    const sec = parseInt(item.seconds, 10);
                    const pct = total > 0 ? (100 * sec / total) : 0;
                    const barPct = maxSeconds > 0 ? (100 * sec / maxSeconds) : 0;
                    const row = document.createElement('div');
                    row.className = 'activity-row d-flex flex-wrap align-items-center gap-2';
                    row.innerHTML =
                        '<div class="flex-grow-1 min-w-0">' +
                        '<div class="d-flex justify-content-between align-items-center gap-2 mb-1">' +
                        '<span class="text-truncate fw-500">' + escapeHtml(item.title) + '</span>' +
                        '<span class="text-muted small flex-shrink-0">' + formatDuration(sec) + ' · ' + pct.toFixed(0) + '%</span>' +
                        '</div>' +
                        '<div class="bar-wrap"><div class="bar-fill" style="width:' + barPct + '%"></div></div>' +
                        '</div>';
                    listEl.appendChild(row);
                });
            }
            const projList = document.getElementById('by-project-list');
            const noProj = document.getElementById('no-project-data');
            projList.innerHTML = '';
            if (byProject.length === 0) {
                noProj.style.display = 'block';
            } else {
                noProj.style.display = 'none';
                const maxProj = Math.max(...byProject.map(p => parseInt(p.seconds, 10)), 1);
                byProject.forEach(function(p) {
                    const sec = parseInt(p.seconds, 10);
                    const pct = total > 0 ? (100 * sec / total) : 0;
                    const barPct = maxProj > 0 ? (100 * sec / maxProj) : 0;
                    const row = document.createElement('div');
                    row.className = 'activity-row d-flex flex-wrap align-items-center gap-2';
                    row.innerHTML =
                        '<div class="flex-grow-1 min-w-0">' +
                        '<div class="d-flex justify-content-between align-items-center gap-2 mb-1">' +
                        '<span class="fw-500">' + escapeHtml(p.name) + '</span>' +
                        '<span class="text-muted small">' + formatDuration(sec) + ' · ' + pct.toFixed(0) + '%</span>' +
                        '</div>' +
                        '<div class="bar-wrap"><div class="bar-fill" style="width:' + barPct + '%; background:' + (p.color || 'var(--accent)') + '"></div></div>' +
                        '</div>';
                    projList.appendChild(row);
                });
            }
        })
        .catch(function() {
            document.getElementById('activity-list').innerHTML = '<p class="text-muted small">Error loading data.</p>';
        });
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function setDefaultRange() {
    const end = new Date();
    const start = new Date();
    start.setDate(start.getDate() - 30);
    document.getElementById('range-start').value = start.toISOString().slice(0, 10);
    document.getElementById('range-end').value = end.toISOString().slice(0, 10);
}

setDefaultRange();
loadAnalytics();
document.getElementById('btn-apply').addEventListener('click', loadAnalytics);
document.getElementById('range-start').addEventListener('change', loadAnalytics);
document.getElementById('range-end').addEventListener('change', loadAnalytics);
</script>
</body>
</html>
