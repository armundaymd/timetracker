import { useState, useEffect, useRef, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import {
  getStatus,
  startTimer,
  stopTimer,
  getEvents,
  createEvent,
  updateEvent,
  deleteEvent,
  bulkDeleteTaskIds,
  bulkAssignProject,
  getFavorites,
  toggleFavorite,
  getQuickButtons,
  getProjects,
  getHistory,
  getSSEUrl,
} from '../api';
import { useAuth } from '../context/AuthContext';

function formatTime(date) {
  const d = new Date(date);
  const offset = d.getTimezoneOffset() * 60000;
  return new Date(d - offset).toISOString().slice(0, 16);
}

const DEFAULT_TITLE = 'Time Tracker';
const RUNNING_FAVICON = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ef4444"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2" stroke="white" stroke-width="2" fill="none"/></svg>';

export default function Tracker() {
  const { token, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [status, setStatus] = useState(null);
  const [taskTitle, setTaskTitle] = useState('');
  const [taskDescription, setTaskDescription] = useState('');
  const [timerStr, setTimerStr] = useState('00:00:00');
  const [favorites, setFavorites] = useState([]);
  const [quickButtons, setQuickButtons] = useState([]);
  const [projects, setProjects] = useState([]);
  const [history, setHistory] = useState([]);
  const [modal, setModal] = useState(null);
  const [forgetStopDismissed, setForgetStopDismissed] = useState(false);
  const [selectionMode, setSelectionMode] = useState(false);
  const [selectedEventIds, setSelectedEventIds] = useState([]);
  const [bulkProjectId, setBulkProjectId] = useState('');
  const timerRef = useRef(null);
  const calendarRef = useRef(null);
  const taskInputRef = useRef(null);
  const prevFaviconRef = useRef(null);
  const dropdownRef = useRef(null);
  const [showHistoryDropdown, setShowHistoryDropdown] = useState(false);
  const [dropdownPosition, setDropdownPosition] = useState({ top: 0, left: 0, width: 0 });
  const hideDropdownTimerRef = useRef(null);
  const [confirmModal, setConfirmModal] = useState(null);
  const [calendarInitialView] = useState(() =>
    typeof window !== 'undefined' && window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek'
  );

  const fetchStatus = useCallback(async () => {
    try {
      const data = await getStatus();
      setStatus(data && data.is_running === 1 ? data : null);
      if (data && data.is_running === 1) {
        setTaskTitle(data.title || '');
      }
    } catch {
      setStatus(null);
    }
  }, []);

  useEffect(() => {
    fetchStatus();
  }, [fetchStatus]);

  // Click-to-restart from Analytics: populate input and start timer
  useEffect(() => {
    const startTask = location.state?.startTask;
    if (!startTask || typeof startTask !== 'string') return;
    setTaskTitle(startTask);
    navigate('.', { replace: true, state: {} });
    startTimer(startTask).then(() => {
      fetchStatus();
      if (calendarRef.current) calendarRef.current.getApi().refetchEvents();
    }).catch(() => {});
  }, [location.state?.startTask, navigate, fetchStatus]);

  // Favicon & document title when timer is running
  useEffect(() => {
    const link = document.querySelector("link[rel*='icon']") || (() => {
      const l = document.createElement('link');
      l.rel = 'icon';
      document.head.appendChild(l);
      return l;
    })();
    if (status) {
      prevFaviconRef.current = link.href;
      link.href = RUNNING_FAVICON;
      link.type = 'image/svg+xml';
      document.title = `${timerStr} · ${status.title || 'Timer'} – ${DEFAULT_TITLE}`;
    } else {
      if (prevFaviconRef.current) link.href = prevFaviconRef.current;
      document.title = DEFAULT_TITLE;
    }
    return () => { document.title = DEFAULT_TITLE; };
  }, [status, timerStr]);


  useEffect(() => {
    if (!status) {
      setTimerStr('00:00:00');
      if (timerRef.current) clearInterval(timerRef.current);
      return;
    }
    const start = new Date(status.start_time);
    const tick = () => {
      const diff = Math.floor((Date.now() - start) / 1000);
      const h = String(Math.floor(diff / 3600)).padStart(2, '0');
      const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
      const s = String(diff % 60).padStart(2, '0');
      setTimerStr(`${h}:${m}:${s}`);
    };
    tick();
    timerRef.current = setInterval(tick, 1000);
    return () => clearInterval(timerRef.current);
  }, [status]);

  useEffect(() => {
    if (!token) return;
    getFavorites().then(setFavorites).catch(() => {});
    getQuickButtons().then(setQuickButtons).catch(() => {});
    getProjects().then(setProjects).catch(() => {});
  }, [token]);

  useEffect(() => {
    getHistory().then((data) => {
      const list = [...(favorites || []), ...(data || []).filter((t) => !favorites.includes(t))];
      setHistory(list.slice(0, 20));
    }).catch(() => {});
  }, [token, favorites]);

  useEffect(() => {
    if (!token) return;
    try {
      const es = new EventSource(getSSEUrl());
      es.onmessage = () => fetchStatus();
      es.onerror = () => es.close();
      return () => es.close();
    } catch {}
  }, [token, fetchStatus]);

  const handleStartStop = useCallback(async () => {
    try {
      if (status) await stopTimer();
      else await startTimer(taskTitle || 'Untitled Task', taskDescription);
      await fetchStatus();
      if (calendarRef.current) calendarRef.current.getApi().refetchEvents();
      if (!status) getHistory().then((d) => setHistory(d || []));
    } catch {}
  }, [status, taskTitle, taskDescription, fetchStatus]);

  useEffect(() => {
    const onKeyDown = (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
        if (e.key.toLowerCase() === 'n' && (e.metaKey || e.ctrlKey)) {
          e.preventDefault();
          taskInputRef.current?.focus();
        }
        return;
      }
      if (e.key.toLowerCase() === 's') {
        e.preventDefault();
        handleStartStop();
      }
      if (e.key.toLowerCase() === 'n') {
        e.preventDefault();
        taskInputRef.current?.focus();
      }
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [handleStartStop]);

  const handleStar = async () => {
    const title = taskTitle.trim();
    if (!title) return;
    try {
      await toggleFavorite(title);
      const list = await getFavorites();
      setFavorites(list || []);
    } catch {}
  };

  const eventsUrl = (info) =>
    getEvents(info.startStr, info.endStr).catch(() => []);

  const handleEventClick = (info) => {
    if (selectionMode) {
      const id = info.event.id;
      setSelectedEventIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
      return;
    }
    const ext = info.event.extendedProps || {};
    setModal({
      id: info.event.id,
      title: info.event.title,
      description: ext.description ?? info.event.description ?? '',
      start: formatTime(info.event.start),
      end: info.event.end ? formatTime(info.event.end) : '',
    });
  };

  const handleSelect = (info) => {
    if (selectionMode) return;
    setModal({ id: null, title: '', description: '', start: formatTime(info.start), end: formatTime(info.end) });
  };

  const handleSaveEvent = async () => {
    if (!modal) return;
    try {
      const payload = { title: modal.title, description: modal.description ?? '', start: modal.start, end: modal.end };
      if (modal.id) await updateEvent({ id: modal.id, ...payload });
      else await createEvent(payload);
      setModal(null);
      if (calendarRef.current) calendarRef.current.getApi().refetchEvents();
    } catch {}
  };

  const handleDeleteEvent = () => {
    if (!modal?.id) return;
    setConfirmModal({
      message: 'Delete this task?',
      onConfirm: async () => {
        try {
          await deleteEvent(modal.id);
          setModal(null);
          setConfirmModal(null);
          if (calendarRef.current) calendarRef.current.getApi().refetchEvents();
        } catch {}
      },
    });
  };

  const currentProjectColor = status?.project_id ? (projects.find((p) => p.id === Number(status.project_id))?.color) : null;

  const handleBulkDelete = () => {
    if (selectedEventIds.length === 0) return;
    setConfirmModal({
      message: `Delete ${selectedEventIds.length} task(s)?`,
      onConfirm: async () => {
        try {
          await bulkDeleteTaskIds(selectedEventIds);
          setSelectedEventIds([]);
          setSelectionMode(false);
          setConfirmModal(null);
          if (calendarRef.current) calendarRef.current.getApi().refetchEvents();
        } catch {}
      },
    });
  };

  const handleBulkAssign = async () => {
    if (selectedEventIds.length === 0) return;
    try {
      await bulkAssignProject(selectedEventIds, bulkProjectId ? Number(bulkProjectId) : null);
      setSelectedEventIds([]);
      setBulkProjectId('');
      setSelectionMode(false);
      if (calendarRef.current) calendarRef.current.getApi().refetchEvents();
    } catch {}
  };

  const [dark, setDark] = useState(() => localStorage.getItem('darkMode') === 'true');
  useEffect(() => {
    if (dark) document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
    localStorage.setItem('darkMode', dark ? 'true' : 'false');
  }, [dark]);

  return (
    <div className="min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white font-sans pb-8">
      {/* "Did you forget to stop?" banner */}
      {status?.forget_stop && !forgetStopDismissed && (
        <div className="sticky top-0 z-[60] bg-amber-100 dark:bg-amber-900/40 border-b border-amber-300 dark:border-amber-700 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
          <span className="text-amber-900 dark:text-amber-100 font-medium">
            Did you forget to stop? This timer has been running for over 8 hours.
          </span>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => setForgetStopDismissed(true)}
              className="px-3 py-1.5 rounded-lg bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100"
            >
              Confirm (keep running)
            </button>
            <button
              type="button"
              onClick={async () => {
                await stopTimer();
                await fetchStatus();
                setForgetStopDismissed(true);
                if (calendarRef.current) calendarRef.current.getApi().refetchEvents();
              }}
              className="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white"
            >
              Adjust (stop now)
            </button>
          </div>
        </div>
      )}

      {/* Timer bar – sticky */}
      <div className="sticky top-0 z-50 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm">
        <div className="max-w-4xl mx-auto px-4 py-4">
          <div className="flex flex-wrap items-center gap-2 mb-3">
            <div className="flex flex-wrap gap-2">
              {quickButtons.map((title) => (
                <button
                  key={title}
                  type="button"
                  onClick={() => setTaskTitle(title)}
                  className="px-3 py-2 text-sm rounded-lg border border-sky-500 text-sky-500 hover:bg-sky-500 hover:text-white"
                >
                  {title}
                </button>
              ))}
            </div>
            <div className="ml-auto flex items-center gap-2">
              <button
                type="button"
                onClick={() => setDark(!dark)}
                className="p-2 rounded-lg border border-slate-300 dark:border-slate-600"
                aria-label="Toggle dark mode"
              >
                {dark ? '☀️' : '🌙'}
              </button>
              <Link to="/analytics" className="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700">
                Analytics
              </Link>
              <Link to="/settings" className="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700">
                Settings
              </Link>
            </div>
          </div>

          <div className="flex justify-center mb-4">
            <div className={`text-4xl md:text-6xl font-bold tabular-nums tracking-wide ${status ? 'animate-pulse' : ''}`}>
              {timerStr}
            </div>
          </div>

          <div className="space-y-3">
            <div
              className={`flex gap-2 rounded-xl border border-slate-300 dark:border-slate-600 transition-colors ${currentProjectColor ? 'border-l-4' : ''}`}
              style={currentProjectColor ? { borderLeftColor: currentProjectColor } : {}}
            >
              <select
                className="w-36 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-3 text-sm"
                onChange={(e) => {
                  const opt = e.target.options[e.target.selectedIndex];
                  if (opt.value) setTaskTitle(opt.text + ': ');
                  e.target.selectedIndex = 0;
                }}
              >
                <option value="">No project</option>
                {projects.map((p) => (
                  <option key={p.id} value={p.id}>{p.name}</option>
                ))}
              </select>
              <div className="flex-1 relative" ref={dropdownRef}>
                <input
                  ref={taskInputRef}
                  type="text"
                  value={taskTitle}
                  onChange={(e) => setTaskTitle(e.target.value)}
                  onFocus={() => {
                    if (hideDropdownTimerRef.current) clearTimeout(hideDropdownTimerRef.current);
                    setShowHistoryDropdown(true);
                    const el = taskInputRef.current;
                    if (el) {
                      const rect = el.getBoundingClientRect();
                      setDropdownPosition({ top: rect.bottom + 4, left: rect.left, width: rect.width });
                    }
                  }}
                  onBlur={() => {
                    hideDropdownTimerRef.current = setTimeout(() => setShowHistoryDropdown(false), 180);
                  }}
                  placeholder="What are you doing? (or Project: task) — N to focus"
                  className="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                />
                {showHistoryDropdown && createPortal(
                  <div
                    className="fixed z-[9999] max-h-60 overflow-auto rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl py-1 min-w-[200px]"
                    style={{ top: dropdownPosition.top, left: dropdownPosition.left, width: Math.max(dropdownPosition.width, 200) }}
                    onMouseDown={(e) => e.preventDefault()}
                  >
                    <div className="px-3 py-1.5 text-xs text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-700">
                      Recent & favorites — click ★ to add/remove favorite
                    </div>
                    {history.length === 0 ? (
                      <div className="px-3 py-4 text-sm text-slate-500 dark:text-slate-400">
                        No recent tasks. Start a timer to see them here.
                      </div>
                    ) : (
                      history.map((title) => (
                        <div
                          key={title}
                          className="flex items-center gap-2 w-full px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 text-left"
                        >
                          <button
                            type="button"
                            onClick={() => {
                              setTaskTitle(title);
                              setShowHistoryDropdown(false);
                              taskInputRef.current?.focus();
                            }}
                            className="flex-1 min-w-0 truncate text-left text-sm"
                          >
                            {title}
                          </button>
                        <button
                          type="button"
                          onClick={async () => {
                            try {
                              await toggleFavorite(title);
                              const list = await getFavorites();
                              setFavorites(list || []);
                            } catch {}
                          }}
                          title={favorites.includes(title) ? 'Remove from favorites' : 'Add to favorites'}
                          className="flex-shrink-0 flex items-center gap-1 px-2 py-1.5 rounded-md border border-amber-400 dark:border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-800/50 text-sm font-medium"
                          aria-label={favorites.includes(title) ? `Unstar ${title}` : `Star ${title}`}
                        >
                          <span className="text-base leading-none" aria-hidden>{favorites.includes(title) ? '★' : '☆'}</span>
                          <span>{favorites.includes(title) ? 'Fav' : 'Star'}</span>
                        </button>
                        </div>
                      ))
                    )}
                  </div>,
                  document.body
                )}
              </div>
              <button
                type="button"
                onClick={handleStar}
                title={favorites.includes(taskTitle.trim()) ? 'Unstar' : 'Star'}
                className="px-3 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-lg"
              >
                {favorites.includes(taskTitle.trim()) ? '★' : '☆'}
              </button>
            </div>
            <div className="flex justify-center md:justify-start">
              <button
                type="button"
                onClick={handleStartStop}
                className={`px-8 py-3 rounded-xl font-semibold text-lg text-white ${status ? 'bg-red-500 hover:bg-red-600' : currentProjectColor ? 'hover:opacity-90' : 'bg-emerald-500 hover:bg-emerald-600'}`}
                style={!status && currentProjectColor ? { backgroundColor: currentProjectColor } : {}}
                title="Start/Stop (S)"
              >
                {status ? 'Stop' : 'Start'}
              </button>
            </div>
          </div>

          {favorites.length > 0 && (
            <div className="flex flex-wrap gap-2 mt-3">
              {favorites.map((title) => (
                <span
                  key={title}
                  className="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-full border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 group"
                >
                  <button
                    type="button"
                    onClick={() => setTaskTitle(title)}
                    className="text-left truncate max-w-[200px]"
                  >
                    {title}
                  </button>
                  <button
                    type="button"
                    onClick={async (e) => {
                      e.stopPropagation();
                      try {
                        await toggleFavorite(title);
                        setFavorites((prev) => prev.filter((t) => t !== title));
                      } catch {}
                    }}
                    title="Remove from favorites"
                    className="text-slate-400 hover:text-red-500 dark:hover:text-red-400 ml-0.5 rounded-full p-0.5 leading-none"
                    aria-label={`Unstar ${title}`}
                  >
                    ×
                  </button>
                </span>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Calendar */}
      <div className="max-w-4xl mx-auto px-4 mt-6">
        <div className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-lg p-4">
          <div className="flex flex-wrap justify-between items-center gap-2 mb-3">
            <h2 className="text-lg font-semibold">My Schedule</h2>
            <div className="flex items-center gap-2">
              {selectionMode ? (
                <>
                  <span className="text-sm text-slate-500">{selectedEventIds.length} selected</span>
                  <select value={bulkProjectId} onChange={(e) => setBulkProjectId(e.target.value)} className="text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1">
                    <option value="">No project</option>
                    {projects.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                  </select>
                  <button type="button" onClick={handleBulkAssign} className="text-sm px-2 py-1 rounded-lg border border-sky-500 text-sky-500 hover:bg-sky-500 hover:text-white">Assign project</button>
                  <button type="button" onClick={handleBulkDelete} className="text-sm px-2 py-1 rounded-lg border border-red-500 text-red-500 hover:bg-red-500 hover:text-white">Delete selected</button>
                  <button type="button" onClick={() => { setSelectionMode(false); setSelectedEventIds([]); }} className="text-sm px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600">Cancel</button>
                </>
              ) : (
                <button type="button" onClick={() => setSelectionMode(true)} className="text-sm px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700">Select tasks</button>
              )}
              <button
              type="button"
              onClick={() => logout()}
              className="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
            >
              Log out
            </button>
            </div>
          </div>
          <style>{`
            @media (max-width: 768px) {
              .calendar-mobile-wrap .fc .fc-toolbar.fc-header-toolbar {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                justify-content: center;
                align-items: center;
              }
              .calendar-mobile-wrap .fc .fc-toolbar-chunk {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: center;
                gap: 0.35rem;
              }
              .calendar-mobile-wrap .fc .fc-toolbar-chunk:first-child { order: 1; }
              .calendar-mobile-wrap .fc .fc-toolbar-chunk:nth-child(2) { order: -1; width: 100%; }
              .calendar-mobile-wrap .fc .fc-toolbar-chunk:last-child { order: 2; }
              .calendar-mobile-wrap .fc .fc-toolbar-title {
                font-size: 0.9375rem;
                margin: 0;
              }
              .calendar-mobile-wrap .fc .fc-button {
                padding: 0.35rem 0.6rem;
                font-size: 0.8125rem;
              }
            }
          `}</style>
          <div className="calendar-mobile-wrap">
          <FullCalendar
            ref={calendarRef}
            plugins={[timeGridPlugin, dayGridPlugin, interactionPlugin]}
            initialView={calendarInitialView}
            headerToolbar={{ left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' }}
            buttonText={{ today: 'Today', month: 'Month', week: 'Week', day: 'Day' }}
            events={eventsUrl}
            eventDataTransform={(event) => ({ ...event, extendedProps: { ...(event.extendedProps || {}), description: event.description } })}
            editable
            selectable
            selectMirror
            dayMaxEvents
            slotLabelFormat={{ hour: '2-digit', minute: '2-digit', hour12: false }}
            eventTimeFormat={{ hour: '2-digit', minute: '2-digit', hour12: false }}
            eventResizableFromStart
            eventClick={handleEventClick}
            select={handleSelect}
            eventDrop={async (info) => {
              try {
                await updateEvent({
                  id: info.event.id,
                  title: info.event.title,
                  start: formatTime(info.event.start),
                  end: info.event.end ? formatTime(info.event.end) : null,
                });
              } catch {}
            }}
            eventResize={async (info) => {
              try {
                await updateEvent({
                  id: info.event.id,
                  title: info.event.title,
                  start: formatTime(info.event.start),
                  end: info.event.end ? formatTime(info.event.end) : null,
                });
              } catch {}
            }}
            height="auto"
          />
          </div>
        </div>
      </div>

      {/* Confirm modal (no browser confirm popup) */}
      {confirmModal && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" onClick={() => setConfirmModal(null)}>
          <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700" onClick={(e) => e.stopPropagation()}>
            <p className="text-slate-700 dark:text-slate-200 mb-6">{confirmModal.message}</p>
            <div className="flex justify-end gap-2">
              <button type="button" onClick={() => setConfirmModal(null)} className="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600">
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
                confirmModal.onConfirm();
              }}
                className="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Edit modal */}
      {modal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" onClick={() => setModal(null)}>
          <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-semibold mb-4">{modal.id ? 'Edit task' : 'New task'}</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Task</label>
                <input
                  type="text"
                  value={modal.title}
                  onChange={(e) => setModal((m) => ({ ...m, title: e.target.value }))}
                  className="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Notes (optional)</label>
                <textarea
                  value={modal.description ?? ''}
                  onChange={(e) => setModal((m) => ({ ...m, description: e.target.value }))}
                  rows={2}
                  className="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 resize-none"
                  placeholder="Details without cluttering the title"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Start</label>
                  <input
                    type="datetime-local"
                    value={modal.start}
                    onChange={(e) => setModal((m) => ({ ...m, start: e.target.value }))}
                    className="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">End</label>
                  <input
                    type="datetime-local"
                    value={modal.end}
                    onChange={(e) => setModal((m) => ({ ...m, end: e.target.value }))}
                    className="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
                  />
                </div>
              </div>
            </div>
            <div className="flex justify-between mt-6">
              <div>
                {modal.id && (
                  <button type="button" onClick={handleDeleteEvent} className="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm">
                    Delete
                  </button>
                )}
              </div>
              <div className="flex gap-2">
                <button type="button" onClick={() => setModal(null)} className="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600">
                  Cancel
                </button>
                <button type="button" onClick={handleSaveEvent} className="px-4 py-2 rounded-lg bg-sky-500 hover:bg-sky-600 text-white">
                  Save
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
