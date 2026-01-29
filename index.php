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
    <meta name="theme-color" content="#0f172a">
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
        .timer-bar .container { max-width: 900px; }
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
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            min-width: 100px;
            text-align: center;
        }
        @media (min-width: 768px) {
            #timer-display { font-size: 1.75rem; min-width: 120px; }
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
    </style>
</head>
<body>

<div class="timer-bar">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <div id="quick-buttons-wrap" class="d-flex flex-wrap gap-2 align-items-center"></div>
            <a href="settings.php" class="btn btn-outline-secondary btn-sm ms-auto">Settings</a>
        </div>
        <div class="d-flex flex-column flex-md-row align-items-stretch gap-2 gap-md-3">
            <div class="flex-grow-1 w-100 d-flex gap-2">
                <input type="text" id="task-input" class="form-control" placeholder="What are you doing?" list="history-list" autocomplete="off">
                <datalist id="history-list"></datalist>
                <button type="button" id="btn-star" class="btn btn-outline-secondary btn-star flex-shrink-0" title="Star as favorite" aria-label="Star as favorite">☆</button>
            </div>
            <div class="d-flex align-items-center gap-2 flex-md-nowrap">
                <div id="timer-display" class="order-2 order-md-1">00:00:00</div>
                <button type="button" id="btn-action" class="btn btn-success flex-grow-1 flex-md-grow-0 order-1 order-md-2">Start</button>
            </div>
        </div>
        <div id="favorites-row" class="d-flex favorites-row align-items-center" style="display: none;"></div>
    </div>
</div>

<div class="container px-3 px-md-4" id="calendar-container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">My Schedule</h4>
        <button type="button" class="btn btn-outline-secondary btn-sm" style="min-height: 44px;" onclick="alert('Subscription URL:\n<?php echo addslashes($api_url); ?>')">Subscribe to Calendar</button>
    </div>
    <div id="calendar"></div>
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
const modal = new bootstrap.Modal(document.getElementById('eventModal'));

document.addEventListener('DOMContentLoaded', function() {
    // 1. INIT CALENDAR
    var calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        events: 'api.php?action=events',
        editable: true,
        selectable: true,
        nowIndicator: true,
        scrollTime: '08:00:00',
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        height: window.innerWidth < 768 ? 'auto' : 580,
        windowResize: function() { calendar.updateSize(); },
        // Click to Edit
        eventClick: function(info) {
            openModal(info.event);
        },
        // Drag to Move/Resize
        eventDrop: function(info) { updateDbEvent(info.event); },
        eventResize: function(info) { updateDbEvent(info.event); },
        // Select time slot to Add
        select: function(info) {
            openModal(null, info.startStr, info.endStr);
        }
    });
    calendar.render();

    // 2. INIT FAVORITES (then history), QUICK BUTTONS & TIMER
    loadFavorites(); // calls loadHistory() when done so datalist gets favorites first
    loadQuickButtons();
    checkTimerStatus();

    document.getElementById('task-input').addEventListener('input', updateStarState);
    document.getElementById('task-input').addEventListener('focus', updateStarState);
    document.getElementById('btn-star').addEventListener('click', toggleFavorite);

    // Refresh calendar every 5 mins to show updated "running" blocks
    setInterval(() => calendar.refetchEvents(), 300000);
});

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
        setTimeout(() => calendar.refetchEvents(), 500); // Small delay to ensure DB updates
        if(action === 'start') loadHistory(); // Refresh autocomplete
    });
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
    });
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

function loadHistory() {
    fetch('api.php?action=history')
    .then(res => res.json())
    .then(data => {
        const list = document.getElementById('history-list');
        list.innerHTML = '';
        (favoritesList || []).forEach(item => {
            let opt = document.createElement('option');
            opt.value = item;
            list.appendChild(opt);
        });
        (data || []).forEach(item => {
            if (!favoritesList.includes(item)) {
                let opt = document.createElement('option');
                opt.value = item;
                list.appendChild(opt);
            }
        });
    });
}

function loadFavorites() {
    fetch('api.php?action=favorites')
    .then(res => res.json())
    .then(data => {
        favoritesList = Array.isArray(data) ? data : [];
        renderFavoritesRow();
        loadHistory();
    });
}

function renderFavoritesRow() {
    const row = document.getElementById('favorites-row');
    row.innerHTML = '';
    if (favoritesList.length === 0) {
        row.style.display = 'none';
        return;
    }
    row.style.display = 'flex';
    favoritesList.forEach(title => {
        const chip = document.createElement('span');
        chip.className = 'badge bg-light text-dark border favorite-chip';
        chip.textContent = title;
        chip.title = 'Click to use';
        chip.addEventListener('click', () => {
            document.getElementById('task-input').value = title;
            document.getElementById('task-input').focus();
            updateStarState();
        });
        row.appendChild(chip);
    });
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
    });
}

function updateStarState() {
    const title = document.getElementById('task-input').value.trim();
    const btn = document.getElementById('btn-star');
    btn.textContent = favoritesList.includes(title) ? '★' : '☆';
    btn.title = favoritesList.includes(title) ? 'Unstar favorite' : 'Star as favorite';
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
        renderFavoritesRow();
        loadHistory();
        updateStarState();
    });
}

// --- MODAL & CALENDAR LOGIC ---
function openModal(event, startStr = null, endStr = null) {
    if (event) {
        // Edit Mode
        document.getElementById('modalTitle').innerText = 'Edit Task';
        document.getElementById('entry-id').value = event.id;
        document.getElementById('entry-title').value = event.title;
        // Format dates for input type="datetime-local" (YYYY-MM-DDTHH:mm)
        document.getElementById('entry-start').value = toLocalISO(event.start);
        document.getElementById('entry-end').value = event.end ? toLocalISO(event.end) : '';
        document.getElementById('btn-delete').style.display = 'block';
    } else {
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
    
    fetch(`api.php?action=${action}`, {
        method: 'POST',
        body: JSON.stringify({ id, title, start, end })
    }).then(() => {
        modal.hide();
        calendar.refetchEvents();
    });
}

function deleteEvent() {
    const id = document.getElementById('entry-id').value;
    if(!confirm("Are you sure?")) return;
    
    fetch('api.php?action=delete', {
        method: 'POST',
        body: JSON.stringify({ id })
    }).then(() => {
        modal.hide();
        calendar.refetchEvents();
    });
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