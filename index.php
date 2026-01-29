<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$api_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/subscribe.php?token=" . $_SESSION['api_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a" id="meta-theme-color">
    <base href="<?php echo htmlspecialchars(rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/'); ?>">
    <title>My Personal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <style>
        :root {
            --bg-page: #f1f5f9;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --accent: #0ea5e9;
            --accent-hover: #0284c7;
            --success: #10b981;
            --success-hover: #059669;
            --danger: #ef4444;
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
        .timer-bar {
            background: var(--bg-card);
            padding: 1rem 0 1.25rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border);
        }
        .timer-bar .container { max-width: 900px; display: flex; flex-direction: column; align-items: center; }
        .timer-bar .container > .d-flex:first-child { width: 100%; justify-content: center; }
        .timer-bar .container > .d-flex:first-child .ms-auto { margin-left: auto; margin-right: auto; }
        .timer-display-row { display: flex; justify-content: center; align-items: center; }
        #quick-buttons-wrap .btn { font-size: 0.875rem; padding: 0.5rem 0.75rem; min-height: 44px; border-radius: var(--radius-sm); }
        #task-input {
            font-size: 1rem; /* 16px avoids iOS zoom on focus */
            min-height: 48px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        #task-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(14,165,233,0.15); }
        .btn-star {
            font-size: 1.35rem; padding: 0 0.6rem; min-width: 48px; min-height: 48px;
            border-radius: var(--radius-sm); border: 1px solid var(--border);
        }
        #timer-display {
            font-variant-numeric: tabular-nums;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            text-align: center;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }
        @media (min-width: 768px) {
            #timer-display { font-size: 3.25rem; }
        }
        #btn-action {
            min-height: 48px; padding-left: 1.5rem; padding-right: 1.5rem;
            border-radius: var(--radius-sm); font-weight: 600; border: none;
        }
        #btn-action.btn-success { background: var(--success); }
        #btn-action.btn-success:hover { background: var(--success-hover); }
        #btn-action.btn-danger { background: var(--danger); }
        #btn-action.btn-danger:hover { background: #dc2626; }
        .favorites-row { margin-top: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
        .favorite-chip {
            cursor: pointer; padding: 0.4rem 0.75rem; font-size: 0.875rem;
            border-radius: 999px; background: var(--bg-page); border: 1px solid var(--border);
            transition: background 0.15s, border-color 0.15s;
        }
        .favorite-chip:hover, .favorite-chip:active { background: var(--border); }
        #calendar-container {
            background: var(--bg-card);
            padding: 1.25rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin-top: 1.5rem;
            border: 1px solid var(--border);
        }
        @media (max-width: 767px) {
            #calendar-container { padding: 0.75rem; margin-left: -0.5rem; margin-right: -0.5rem; border-radius: 0; border-left: none; border-right: none; }
        }
        #calendar-container h4 { font-weight: 600; font-size: 1.125rem; }
        .fc { font-family: inherit; }
        .fc-event { cursor: pointer; border-radius: 6px; }
        .fc-theme-standard .fc-scrollgrid { border-color: var(--border); }
        .fc .fc-button { border-radius: var(--radius-sm); font-weight: 500; }
        .fc .fc-button-primary { background: var(--accent); border-color: var(--accent); }
        .fc .fc-button-primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
        a[href="settings.php"] { min-height: 44px; align-self: center; border-radius: var(--radius-sm); }
        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }
        html.dark {
            --bg-page: #0f172a;
            --bg-card: #1e293b;
            --border: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --accent-hover: #7dd3fc;
            --success: #34d399;
            --success-hover: #6ee7b7;
            --danger: #f87171;
            --shadow: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.4);
        }
        html.dark .fc-theme-standard .fc-scrollgrid { border-color: var(--border); }
        html.dark .fc .fc-button-primary { background: var(--accent); border-color: var(--accent); }
        html.dark .fc .fc-button-primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
        html.dark .fc .fc-button:not(.fc-button-primary) { color: var(--text-muted); border-color: var(--border); }
        html.dark .fc .fc-button:not(.fc-button-primary):hover { background: var(--border); }
        .fc .fc-col-header-cell-cushion { color: inherit; text-decoration: none; cursor: pointer; }
        html.dark .fc .fc-col-header-cell-cushion { color: inherit; }
        .task-input-wrap { position: relative; width: 100%; max-width: 280px; }
        #recommendations-dropdown {
            display: none; position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px;
            background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg); z-index: 1050; max-height: 280px; overflow-y: auto;
        }
        #recommendations-dropdown.show { display: block; }
        .recommendations-section { padding: 0.5rem 0.75rem 0.25rem; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .recommendation-item { display: block; width: 100%; padding: 0.6rem 0.75rem; text-align: left; font-size: 0.9375rem; background: none; border: none; color: var(--text); cursor: pointer; border-radius: 0; }
        .recommendation-item:hover, .recommendation-item:focus { background: var(--bg-page); }
    </style>
</head>
<body>
<script>
(function(){var d=localStorage.getItem('darkMode');if(d==='true')document.documentElement.classList.add('dark');var m=document.getElementById('meta-theme-color');if(m)m.content=document.documentElement.classList.contains('dark')?'#0f172a':'#f1f5f9';})();
</script>

<div class="timer-bar">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <div class="ms-auto d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-theme" title="Toggle dark mode" aria-label="Toggle dark mode" style="min-height: 44px; min-width: 44px; padding: 0;">🌙</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-undo" title="Undo last change" style="min-height: 44px; display: none;">Undo</button>
                <a href="analytics.php" class="btn btn-outline-secondary btn-sm" style="min-height: 44px;">Analytics</a>
                <a href="settings.php" class="btn btn-outline-secondary btn-sm" style="min-height: 44px;">Settings</a>
            </div>
        </div>
        <div class="timer-display-row mb-2">
            <div id="timer-display">00:00:00</div>
        </div>
        <div class="d-flex justify-content-center mb-3">
            <button type="button" id="btn-action" class="btn btn-success btn-lg px-5">Start</button>
        </div>
        <div class="task-input-wrap mb-2">
            <div class="d-flex gap-2">
                <input type="text" id="task-input" class="form-control" placeholder="What are you doing? (or Project: task)" list="history-list" autocomplete="off">
                <datalist id="history-list"></datalist>
                <button type="button" id="btn-star" class="btn btn-outline-secondary btn-star flex-shrink-0" title="Star as favorite" aria-label="Star as favorite">☆</button>
            </div>
            <div id="recommendations-dropdown" class="recommendations-dropdown" role="listbox"></div>
        </div>
        <div id="quick-buttons-wrap" class="d-flex flex-wrap gap-2 align-items-center justify-content-center mb-2"></div>
        <div class="d-flex justify-content-center">
            <select id="project-select" class="form-select" style="max-width: 200px; min-height: 44px;" title="Category (type Name: task to use)">
                <option value="">No project</option>
            </select>
        </div>
    </div>
</div>

<div class="container px-3 px-md-4" id="calendar-container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">My Schedule</h4>
        <button type="button" class="btn btn-outline-secondary btn-sm" style="min-height: 44px;" id="btn-subscribe-calendar">Subscribe to Calendar</button>
    </div>
    <div id="calendar"></div>
</div>

<div class="modal fade" id="subscribeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--radius); border: 1px solid var(--border);">
            <div class="modal-header border-bottom" style="border-color: var(--border) !important;">
                <h5 class="modal-title fw-600">Subscribe to Calendar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Add this URL to your calendar app (Google Calendar, Outlook, Apple Calendar, etc.) to see your tasks.</p>
                <input type="text" id="subscribe-url" class="form-control font-monospace small" value="<?php echo htmlspecialchars($api_url); ?>" readonly style="font-size: 0.8rem;">
                <div class="mt-3 d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-primary" id="btn-copy-subscribe" style="min-height: 44px;">Copy to clipboard</button>
                    <span id="subscribe-copy-status" class="text-success small fw-500" style="display: none;">Copied!</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: var(--radius); border: 1px solid var(--border);">
            <div class="modal-header border-bottom" style="border-color: var(--border) !important;">
                <h5 class="modal-title fw-600" id="modalTitle">Edit Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="entry-id">
                <div class="mb-3">
                    <label class="form-label fw-500">Task Description</label>
                    <input type="text" id="entry-title" class="form-control" list="history-list" style="font-size: 1rem;">
                </div>
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-500">Start (24h)</label>
                        <input type="datetime-local" id="entry-start" class="form-control" style="font-size: 1rem;">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-500">End (24h)</label>
                        <input type="datetime-local" id="entry-end" class="form-control" style="font-size: 1rem;">
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between border-top" style="border-color: var(--border) !important;">
                <button type="button" class="btn btn-danger" id="btn-delete" onclick="deleteEvent()" style="display:none;">Delete</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveEvent()">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let calendar;
let timerInterval;
let isRunning = false;
let lastDragResizeState = null;
let currentEditEvent = null;
const UNDO_MAX = 10;
let undoStack = [];
const modal = new bootstrap.Modal(document.getElementById('eventModal'));
const subscribeModal = new bootstrap.Modal(document.getElementById('subscribeModal'));

document.addEventListener('DOMContentLoaded', function() {
    // Real-time sync: when timer is started/stopped on another device, update UI
    (function() {
        var sseToken = <?php echo json_encode($_SESSION['api_token'] ?? ''); ?>;
        if (!sseToken) return;
        try {
            var evtSource = new EventSource("sse.php?token=" + encodeURIComponent(sseToken));
            evtSource.onmessage = function(event) {
                var data = null;
                try { data = JSON.parse(event.data); } catch (e) { return; }
                if (data && data.error) return;
                checkTimerStatus();
                if (typeof calendar !== 'undefined') calendar.refetchEvents();
            };
            evtSource.onerror = function() { evtSource.close(); };
        } catch (e) {}
    })();

    document.getElementById('btn-subscribe-calendar').addEventListener('click', function() {
        subscribeModal.show();
    });
    document.getElementById('subscribeModal').addEventListener('shown.bs.modal', function() {
        copySubscribeUrl();
    });
    document.getElementById('btn-copy-subscribe').addEventListener('click', function() {
        copySubscribeUrl();
    });

    // 1. INIT CALENDAR
    var calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        navLinks: true,
        navLinkDayClick: function(date) {
            calendar.changeView('timeGridDay', date);
        },
        events: function(info) {
            return fetch('api.php?action=events&start=' + encodeURIComponent(info.startStr) + '&end=' + encodeURIComponent(info.endStr))
                .then(function(res) { return res.ok ? res.json() : []; })
                .catch(function() { return []; });
        },
        editable: true,
        selectable: true,
        nowIndicator: true,
        eventResizableFromStart: true,
        scrollTime: '08:00:00',
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        height: window.innerWidth < 768 ? 'auto' : 580,
        windowResize: function() { calendar.updateSize(); },
        eventClick: function(info) {
            openModal(info.event);
        },
        eventDragStart: function(info) {
            lastDragResizeState = { id: info.event.id, title: info.event.title, start: toLocalISO(info.event.start), end: info.event.end ? toLocalISO(info.event.end) : null };
        },
        eventResizeStart: function(info) {
            lastDragResizeState = { id: info.event.id, title: info.event.title, start: toLocalISO(info.event.start), end: info.event.end ? toLocalISO(info.event.end) : null };
        },
        eventDrop: function(info) {
            if (lastDragResizeState) { pushUndo({ type: 'update', data: lastDragResizeState }); lastDragResizeState = null; }
            updateDbEvent(info.event);
        },
        eventResize: function(info) {
            if (lastDragResizeState) { pushUndo({ type: 'update', data: lastDragResizeState }); lastDragResizeState = null; }
            updateDbEvent(info.event);
        },
        // Select time slot to Add
        select: function(info) {
            openModal(null, info.startStr, info.endStr);
        }
    });
    calendar.render();

    // 2. INIT FAVORITES (then history), QUICK BUTTONS & TIMER
    loadFavorites();
    loadQuickButtons();
    loadProjects();
    checkTimerStatus();

    document.getElementById('project-select').addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        if (opt.value) document.getElementById('task-input').value = opt.text + ': ';
        this.selectedIndex = 0;
    });

    document.getElementById('task-input').addEventListener('input', function() {
        updateStarState();
        showRecommendations();
    });
    document.getElementById('task-input').addEventListener('focus', function() {
        updateStarState();
        showRecommendations();
    });
    document.getElementById('task-input').addEventListener('blur', hideRecommendations);
    document.getElementById('btn-star').addEventListener('click', toggleFavorite);
    document.getElementById('btn-undo').addEventListener('click', performUndo);

    var themeBtn = document.getElementById('btn-theme');
    var metaTheme = document.getElementById('meta-theme-color');
    function updateThemeBtn() {
        themeBtn.textContent = document.documentElement.classList.contains('dark') ? '☀️' : '🌙';
    }
    updateThemeBtn();
    themeBtn.addEventListener('click', function() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark ? 'true' : 'false');
        if (metaTheme) metaTheme.content = isDark ? '#0f172a' : '#f1f5f9';
        updateThemeBtn();
    });

    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.code === 'KeyS') { e.preventDefault(); document.getElementById('btn-action').click(); }
        if (e.altKey && e.code === 'KeyL') { e.preventDefault(); document.getElementById('task-input').focus(); }
    });

    setInterval(() => calendar.refetchEvents(), 300000);
});

function loadProjects() {
    fetch('api.php?action=projects')
        .then(res => res.json())
        .then(data => {
            var sel = document.getElementById('project-select');
            sel.innerHTML = '<option value="">No project</option>';
            (data || []).forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name;
                opt.style.borderLeft = '3px solid ' + (p.color || '#0ea5e9');
                sel.appendChild(opt);
            });
        })
        .catch(function() {});
}

function pushUndo(entry) {
    undoStack.push(entry);
    if (undoStack.length > UNDO_MAX) undoStack.shift();
    updateUndoButton();
}

function updateUndoButton() {
    const btn = document.getElementById('btn-undo');
    btn.style.display = undoStack.length ? 'inline-block' : 'none';
}

function performUndo() {
    const entry = undoStack.pop();
    if (!entry) return;
    updateUndoButton();
    if (entry.type === 'delete') {
        fetch('api.php?action=create', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ title: entry.data.title, start: entry.data.start, end: entry.data.end }) })
            .then(() => calendar.refetchEvents()).catch(() => {});
    } else if (entry.type === 'update') {
        fetch('api.php?action=update', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: entry.data.id, title: entry.data.title, start: entry.data.start, end: entry.data.end }) })
            .then(() => calendar.refetchEvents()).catch(() => {});
    } else if (entry.type === 'create') {
        fetch('api.php?action=delete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: entry.data.id }) })
            .then(() => calendar.refetchEvents()).catch(() => {});
    }
}

// --- TIMER LOGIC ---
document.getElementById('btn-action').addEventListener('click', () => {
    const title = document.getElementById('task-input').value;
    const action = isRunning ? 'stop' : 'start';
    
    const formData = new FormData();
    formData.append('title', title);

    fetch(`api.php?action=${action}`, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(() => {
        checkTimerStatus();
        setTimeout(() => calendar.refetchEvents(), 500);
        if(action === 'start') loadHistory();
    })
    .catch(() => { checkTimerStatus(); });
});

function checkTimerStatus() {
    fetch('api.php?action=status')
    .then(res => res.json())
    .then(data => {
        if (data && data.is_running == 1) {
            isRunning = true;
            document.getElementById('task-input').value = data.title;
            document.getElementById('btn-action').innerText = 'Stop';
            document.getElementById('btn-action').classList.replace('btn-success', 'btn-danger');
            startClock(new Date(data.start_time));
        } else {
            isRunning = false;
            document.getElementById('btn-action').innerText = 'Start';
            document.getElementById('btn-action').classList.replace('btn-danger', 'btn-success');
            stopClock();
        }
    })
    .catch(() => { isRunning = false; stopClock(); });
}

function startClock(startTime) {
    if(timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        const diff = Math.floor((new Date() - startTime) / 1000);
        const h = Math.floor(diff / 3600).toString().padStart(2, '0');
        const m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
        const s = (diff % 60).toString().padStart(2, '0');
        document.getElementById('timer-display').innerText = `${h}:${m}:${s}`;
    }, 1000);
}

function stopClock() {
    if(timerInterval) clearInterval(timerInterval);
    document.getElementById('timer-display').innerText = "00:00:00";
}

let favoritesList = [];
let quickButtonsList = [];
let historyList = [];

function loadFavorites() {
    fetch('api.php?action=favorites')
    .then(res => res.json())
    .then(data => {
        favoritesList = Array.isArray(data) ? data : [];
        loadHistory();
    })
    .catch(() => { loadHistory(); });
}

function loadHistory() {
    fetch('api.php?action=history')
    .then(res => res.json())
    .then(data => {
        historyList = Array.isArray(data) ? data : [];
    })
    .catch(() => {});
}

function showRecommendations() {
    const input = document.getElementById('task-input');
    const dropdown = document.getElementById('recommendations-dropdown');
    const val = (input.value || '').trim().toLowerCase();
    dropdown.innerHTML = '';
    let hasItems = false;
    if (favoritesList.length > 0) {
        const section = document.createElement('div');
        section.className = 'recommendations-section';
        section.textContent = 'Recommended';
        dropdown.appendChild(section);
        const filtered = val ? favoritesList.filter(t => t.toLowerCase().includes(val)) : favoritesList.slice(0, 8);
        filtered.forEach(title => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'recommendation-item';
            btn.textContent = title;
            btn.addEventListener('click', () => {
                input.value = title;
                dropdown.classList.remove('show');
                updateStarState();
            });
            dropdown.appendChild(btn);
            hasItems = true;
        });
    }
    if (historyList.length > 0) {
        const section = document.createElement('div');
        section.className = 'recommendations-section';
        section.textContent = 'Recent';
        dropdown.appendChild(section);
        const filtered = val ? historyList.filter(t => t.toLowerCase().includes(val)) : historyList.slice(0, 6);
        filtered.forEach(title => {
            if (favoritesList.includes(title)) return;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'recommendation-item';
            btn.textContent = title;
            btn.addEventListener('click', () => {
                input.value = title;
                dropdown.classList.remove('show');
                updateStarState();
            });
            dropdown.appendChild(btn);
            hasItems = true;
        });
    }
    if (hasItems) dropdown.classList.add('show'); else dropdown.classList.remove('show');
}

function hideRecommendations() {
    setTimeout(function() {
        document.getElementById('recommendations-dropdown').classList.remove('show');
    }, 150);
}

function loadQuickButtons() {
    fetch('api.php?action=quick_buttons')
    .then(res => res.json())
    .then(data => {
        quickButtonsList = Array.isArray(data) ? data : [];
        const wrap = document.getElementById('quick-buttons-wrap');
        wrap.innerHTML = '';
        quickButtonsList.forEach(title => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-primary btn-sm quick-btn';
            btn.textContent = title;
            btn.addEventListener('click', () => {
                document.getElementById('task-input').value = title;
                document.getElementById('task-input').focus();
                updateStarState();
            });
            wrap.appendChild(btn);
        });
    })
    .catch(() => {});
}

function updateStarState() {
    const title = document.getElementById('task-input').value.trim();
    const btn = document.getElementById('btn-star');
    btn.textContent = favoritesList.includes(title) ? '★' : '☆';
    btn.title = favoritesList.includes(title) ? 'Unstar favorite' : 'Star as favorite';
}

function copySubscribeUrl() {
    const input = document.getElementById('subscribe-url');
    const status = document.getElementById('subscribe-copy-status');
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(function() {
            status.style.display = 'inline';
            setTimeout(function() { status.style.display = 'none'; }, 2500);
        }).catch(function() {
            input.select();
            document.execCommand('copy');
            status.textContent = 'Copied!';
            status.style.display = 'inline';
            setTimeout(function() { status.style.display = 'none'; }, 2500);
        });
    } else {
        input.select();
        document.execCommand('copy');
        status.textContent = 'Copied!';
        status.style.display = 'inline';
        setTimeout(function() { status.style.display = 'none'; }, 2500);
    }
}

function toggleFavorite() {
    const title = document.getElementById('task-input').value.trim();
    if (!title) return;
    fetch('api.php?action=toggle_favorite', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title })
    })
    .then(res => res.json())
    .then(data => {
        if (data.starred) {
            if (!favoritesList.includes(title)) favoritesList.push(title);
        } else {
            favoritesList = favoritesList.filter(t => t !== title);
        }
        loadHistory();
        updateStarState();
    })
    .catch(() => {});
}

// --- MODAL & CALENDAR LOGIC ---
function openModal(event, startStr = null, endStr = null) {
    if (event) {
        currentEditEvent = { id: event.id, title: event.title, start: toLocalISO(event.start), end: event.end ? toLocalISO(event.end) : null };
        document.getElementById('modalTitle').innerText = 'Edit Task';
        document.getElementById('entry-id').value = event.id;
        document.getElementById('entry-title').value = event.title;
        document.getElementById('entry-start').value = toLocalISO(event.start);
        document.getElementById('entry-end').value = event.end ? toLocalISO(event.end) : '';
        document.getElementById('btn-delete').style.display = 'block';
    } else {
        currentEditEvent = null;
        // Create Mode
        document.getElementById('modalTitle').innerText = 'New Task';
        document.getElementById('entry-id').value = '';
        document.getElementById('entry-title').value = '';
        document.getElementById('entry-start').value = startStr.substring(0, 16); // Remove timezone bits
        document.getElementById('entry-end').value = endStr.substring(0, 16);
        document.getElementById('btn-delete').style.display = 'none';
    }
    modal.show();
}

function saveEvent() {
    const id = document.getElementById('entry-id').value;
    const title = document.getElementById('entry-title').value;
    const start = document.getElementById('entry-start').value;
    const end = document.getElementById('entry-end').value;

    const action = id ? 'update' : 'create';
    if (action === 'update' && currentEditEvent) {
        pushUndo({ type: 'update', data: currentEditEvent });
    }

    fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, title, start, end })
    })
    .then(res => res.json())
    .then(data => {
        if (action === 'create' && data && data.id) {
            pushUndo({ type: 'create', data: { id: data.id } });
        }
        modal.hide();
        calendar.refetchEvents();
    })
    .catch(() => { modal.hide(); });
}

function deleteEvent() {
    const id = document.getElementById('entry-id').value;
    const title = document.getElementById('entry-title').value;
    const start = document.getElementById('entry-start').value;
    const end = document.getElementById('entry-end').value;
    if (!confirm("Are you sure?")) return;

    pushUndo({ type: 'delete', data: { title, start, end } });

    fetch('api.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    }).then(() => {
        modal.hide();
        calendar.refetchEvents();
    }).catch(() => { modal.hide(); });
}

function updateDbEvent(event) {
    fetch('api.php?action=update', {
        method: 'POST',
        body: JSON.stringify({
            id: event.id,
            title: event.title,
            start: toLocalISO(event.start),
            end: event.end ? toLocalISO(event.end) : null
        })
    });
}

// Helper to handle JS Date to DateTime-local input format
function toLocalISO(date) {
    const offset = date.getTimezoneOffset() * 60000;
    const localISOTime = (new Date(date - offset)).toISOString().slice(0, -1);
    return localISOTime.substring(0, 16);
}
</script>
</body>
</html>