<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$api_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/subscribe.php?token=" . $_SESSION['api_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Personal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <style>
        body { background-color: #f4f6f9; padding-bottom: 50px; }
        .timer-bar { background: white; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        #timer-display { font-family: 'Courier New', monospace; font-weight: bold; font-size: 2rem; color: #2c3e50; min-width: 160px; text-align: center; }
        #calendar-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px; }
        .fc-event { cursor: pointer; }
    </style>
</head>
<body>

<div class="timer-bar">
    <div class="container d-flex flex-column flex-md-row align-items-center gap-3">
        <div class="flex-grow-1 w-100">
            <input type="text" id="task-input" class="form-control form-control-lg" placeholder="What are you doing?" list="history-list">
            <datalist id="history-list"></datalist>
        </div>
        <div id="timer-display">00:00:00</div>
        <button id="btn-action" class="btn btn-success btn-lg px-5">Start</button>
    </div>
</div>

<div class="container" id="calendar-container">
    <div class="d-flex justify-content-between mb-3">
        <h4>My Schedule</h4>
        <button class="btn btn-outline-secondary btn-sm" onclick="alert('Subscription URL:\n<?php echo $api_url; ?>')">📅 Subscribe to Calendar</button>
    </div>
    <div id="calendar"></div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Edit Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="entry-id">
                <div class="mb-3">
                    <label>Task Description</label>
                    <input type="text" id="entry-title" class="form-control" list="history-list">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label>Start Time</label>
                        <input type="datetime-local" id="entry-start" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label>End Time</label>
                        <input type="datetime-local" id="entry-end" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-danger" id="btn-delete" onclick="deleteEvent()" style="display:none;">Delete</button>
                <div>
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
        scrollTime: '08:00:00', // Scroll to 8 AM by default
        
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

    // 2. INIT HISTORY & TIMER
    loadHistory();
    checkTimerStatus();
    
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

function loadHistory() {
    fetch('api.php?action=history')
    .then(res => res.json())
    .then(data => {
        const list = document.getElementById('history-list');
        list.innerHTML = '';
        data.forEach(item => {
            let opt = document.createElement('option');
            opt.value = item;
            list.appendChild(opt);
        });
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