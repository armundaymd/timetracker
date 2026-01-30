function getBase() {
  const b = localStorage.getItem('baseUrl') || import.meta.env.VITE_API_URL || '';
  return b.replace(/\/$/, '');
}

function getToken() {
  return localStorage.getItem('token') || '';
}

export function setToken(token) {
  localStorage.setItem('token', token || '');
}

export async function api(url, options = {}) {
  const base = getBase();
  const token = getToken();
  const headers = {
    'Content-Type': 'application/json',
    ...(token && { Authorization: `Bearer ${token}` }),
    ...options.headers,
  };
  const res = await fetch(`${base}${url}`, { ...options, headers });
  if (res.status === 401) throw new Error('Unauthorized');
  return res;
}

export async function login(username, password) {
  const base = getBase();
  const res = await fetch(`${base}/api.php?action=login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password }),
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Login failed');
  return data.token;
}

export async function getStatus() {
  const res = await api('/api.php?action=status');
  return res.json();
}

export async function startTimer(title, description = '') {
  const base = getBase();
  const token = getToken();
  const fd = new FormData();
  fd.append('title', title || 'Untitled Task');
  if (description) fd.append('description', description);
  const res = await fetch(`${base}/api.php?action=start`, {
    method: 'POST',
    headers: token ? { Authorization: `Bearer ${token}` } : {},
    body: fd,
  });
  return res.json();
}

export async function stopTimer() {
  const res = await api('/api.php?action=stop', { method: 'POST', body: '{}' });
  return res.json();
}

export async function getEvents(start, end) {
  const res = await api(`/api.php?action=events&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
  if (!res.ok) return [];
  return res.json();
}

export async function createEvent(event) {
  const res = await api('/api.php?action=create', {
    method: 'POST',
    body: JSON.stringify(event),
  });
  return res.json();
}

export async function updateEvent(event) {
  const res = await api('/api.php?action=update', {
    method: 'POST',
    body: JSON.stringify(event),
  });
  return res.json();
}

export async function deleteEvent(id) {
  const res = await api('/api.php?action=delete', {
    method: 'POST',
    body: JSON.stringify({ id }),
  });
  return res.json();
}

export async function getFavorites() {
  const res = await api('/api.php?action=favorites');
  return res.json();
}

export async function toggleFavorite(title) {
  const res = await api('/api.php?action=toggle_favorite', {
    method: 'POST',
    body: JSON.stringify({ title }),
  });
  return res.json();
}

export async function getQuickButtons() {
  const res = await api('/api.php?action=quick_buttons');
  return res.json();
}

export async function getProjects() {
  const res = await api('/api.php?action=projects');
  return res.json();
}

export async function saveQuickButtons(titles) {
  const res = await api('/api.php?action=save_quick_buttons', {
    method: 'POST',
    body: JSON.stringify({ titles }),
  });
  return res.json();
}

export async function changePassword(currentPassword, newPassword) {
  const res = await api('/api.php?action=change_password', {
    method: 'POST',
    body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }),
  });
  return res.json();
}

export async function saveProject(project) {
  const res = await api('/api.php?action=save_project', {
    method: 'POST',
    body: JSON.stringify(project),
  });
  return res.json();
}

export async function deleteProject(id) {
  const res = await api('/api.php?action=delete_project', {
    method: 'POST',
    body: JSON.stringify({ id }),
  });
  return res.json();
}

export async function getHistory() {
  const res = await api('/api.php?action=history');
  return res.json();
}

export async function getAnalytics(start, end) {
  const res = await api(`/api.php?action=analytics&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
  return res.json();
}

export function getExportUrl() {
  const base = getBase();
  const token = getToken();
  return `${base}/api.php?action=export${token ? '&token=' + encodeURIComponent(token) : ''}`;
}

export function getExportCsvUrl(start, end) {
  const base = getBase();
  const token = getToken();
  let url = `${base}/api.php?action=export_csv`;
  if (start) url += '&start=' + encodeURIComponent(start);
  if (end) url += '&end=' + encodeURIComponent(end);
  if (token) url += '&token=' + encodeURIComponent(token);
  return url;
}

export async function bulkDeleteTaskIds(ids) {
  const res = await api('/api.php?action=bulk_delete', {
    method: 'POST',
    body: JSON.stringify({ ids }),
  });
  return res.json();
}

export async function bulkAssignProject(ids, projectId) {
  const res = await api('/api.php?action=bulk_assign_project', {
    method: 'POST',
    body: JSON.stringify({ ids, project_id: projectId || null }),
  });
  return res.json();
}

export function getSSEUrl() {
  const base = getBase();
  const token = getToken();
  return `${base}/sse.php?token=${encodeURIComponent(token)}`;
}
