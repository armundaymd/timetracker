import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  getQuickButtons,
  saveQuickButtons,
  getProjects,
  saveProject,
  deleteProject,
  changePassword,
  getExportUrl,
  getExportCsvUrl,
} from '../api';

const MAX_QUICK = 8;

export default function Settings() {
  const [quickButtons, setQuickButtons] = useState([]);
  const [projects, setProjects] = useState([]);
  const [quickStatus, setQuickStatus] = useState('');
  const [projectStatus, setProjectStatus] = useState('');
  const [passwordStatus, setPasswordStatus] = useState({ text: '', type: '' });
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [dark, setDark] = useState(() => localStorage.getItem('darkMode') === 'true');
  const [newProjectName, setNewProjectName] = useState('');
  const [newProjectColor, setNewProjectColor] = useState('#0ea5e9');

  useEffect(() => {
    if (dark) document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
    localStorage.setItem('darkMode', dark ? 'true' : 'false');
  }, [dark]);

  useEffect(() => {
    getQuickButtons().then(setQuickButtons).catch(() => setQuickButtons([]));
    getProjects().then(setProjects).catch(() => setProjects([]));
  }, []);

  const handleSaveQuick = async () => {
    const titles = quickButtons.filter(Boolean);
    try {
      await saveQuickButtons(titles);
      setQuickButtons(titles);
      setQuickStatus('Saved.');
      setTimeout(() => setQuickStatus(''), 2000);
    } catch {
      setQuickStatus('Error saving.');
    }
  };

  const handleAddQuick = () => {
    if (quickButtons.length >= MAX_QUICK) return;
    setQuickButtons([...quickButtons, '']);
  };

  const handleRemoveQuick = (i) => {
    setQuickButtons(quickButtons.filter((_, j) => j !== i));
  };

  const handleQuickChange = (i, v) => {
    const next = [...quickButtons];
    next[i] = v;
    setQuickButtons(next);
  };

  const handleAddProject = async () => {
    const name = newProjectName.trim();
    if (!name) return;
    try {
      await saveProject({ name, color: newProjectColor });
      setProjects(await getProjects());
      setNewProjectName('');
      setProjectStatus('Added.');
      setTimeout(() => setProjectStatus(''), 2000);
    } catch {
      setProjectStatus('Error.');
    }
  };

  const handleSaveProject = async (p) => {
    try {
      await saveProject({ id: p.id, name: p.name, color: p.color });
      setProjects(await getProjects());
    } catch {}
  };

  const handleDeleteProject = async (id) => {
    try {
      await deleteProject(id);
      setProjects(await getProjects());
    } catch {}
  };

  const handleChangePassword = async () => {
    setPasswordStatus({ text: '', type: '' });
    if (!currentPassword || !newPassword) {
      setPasswordStatus({ text: 'Fill all fields.', type: 'error' });
      return;
    }
    if (newPassword !== confirmPassword) {
      setPasswordStatus({ text: 'New passwords do not match.', type: 'error' });
      return;
    }
    if (newPassword.length < 6) {
      setPasswordStatus({ text: 'New password must be at least 6 characters.', type: 'error' });
      return;
    }
    try {
      await changePassword(currentPassword, newPassword);
      setPasswordStatus({ text: 'Password updated.', type: 'success' });
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
    } catch (err) {
      setPasswordStatus({ text: err.message || 'Failed.', type: 'error' });
    }
  };

  return (
    <div className="min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white font-sans pb-8">
      <div className="max-w-2xl mx-auto px-4 py-6">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-xl font-semibold">Settings</h1>
          <Link to="/" className="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
            ← Back to Tracker
          </Link>
        </div>

        <div className="space-y-6">
          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 className="text-lg font-semibold mb-2">Appearance</h2>
            <div className="flex items-center justify-between">
              <span>Dark mode</span>
              <button
                type="button"
                onClick={() => setDark(!dark)}
                className="relative w-12 h-6 rounded-full bg-slate-300 dark:bg-slate-600 transition-colors"
              >
                <span className={`absolute top-1 w-4 h-4 rounded-full bg-white shadow transition-transform ${dark ? 'left-7' : 'left-1'}`} />
              </button>
            </div>
          </section>

          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 className="text-lg font-semibold mb-2">Change password</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">At least 6 characters.</p>
            <div className="space-y-3 mb-4">
              <input
                type="password"
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                placeholder="Current password"
                className="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
              />
              <input
                type="password"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                placeholder="New password"
                className="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
              />
              <input
                type="password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="Confirm new password"
                className="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
              />
            </div>
            {passwordStatus.text && (
              <p className={`text-sm mb-2 ${passwordStatus.type === 'error' ? 'text-red-500' : 'text-emerald-500'}`}>
                {passwordStatus.text}
              </p>
            )}
            <button
              type="button"
              onClick={handleChangePassword}
              className="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-medium"
            >
              Change password
            </button>
          </section>

          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 className="text-lg font-semibold mb-2">Projects (categories)</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">Type <strong>ProjectName: task</strong> in the timer.</p>
            <div className="space-y-2 mb-4">
              {projects.map((p) => (
                <div key={p.id} className="flex gap-2 items-center">
                  <input
                    type="color"
                    value={p.color || '#0ea5e9'}
                    onChange={(e) => handleSaveProject({ ...p, color: e.target.value })}
                    className="w-8 h-8 rounded cursor-pointer border border-slate-300 dark:border-slate-600"
                  />
                  <input
                    type="text"
                    value={p.name}
                    onChange={(e) => handleSaveProject({ ...p, name: e.target.value })}
                    onBlur={() => handleSaveProject(p)}
                    className="flex-1 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 max-w-[200px]"
                  />
                  <button type="button" onClick={() => handleDeleteProject(p.id)} className="px-2 py-1 rounded text-red-500 hover:bg-red-500/10">
                    ×
                  </button>
                </div>
              ))}
            </div>
            <div className="flex gap-2 flex-wrap items-center">
              <input
                type="text"
                value={newProjectName}
                onChange={(e) => setNewProjectName(e.target.value)}
                placeholder="New project name"
                className="w-40 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
              />
              <input
                type="color"
                value={newProjectColor}
                onChange={(e) => setNewProjectColor(e.target.value)}
                className="w-10 h-10 rounded cursor-pointer border border-slate-300 dark:border-slate-600"
              />
              <button type="button" onClick={handleAddProject} className="px-4 py-2 rounded-xl border border-sky-500 text-sky-500 hover:bg-sky-500 hover:text-white">
                Add project
              </button>
            </div>
            {projectStatus && <p className="text-sm text-slate-500 mt-2">{projectStatus}</p>}
          </section>

          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 className="text-lg font-semibold mb-2">Quick-action buttons</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">Up to {MAX_QUICK}. Shown next to the timer.</p>
            <div className="space-y-2 mb-4">
              {quickButtons.map((title, i) => (
                <div key={i} className="flex gap-2">
                  <input
                    type="text"
                    value={title}
                    onChange={(e) => handleQuickChange(i, e.target.value)}
                    placeholder="e.g. Email, Meeting"
                    className="flex-1 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
                  />
                  <button type="button" onClick={() => handleRemoveQuick(i)} className="px-3 py-2 rounded-xl border border-red-500 text-red-500 hover:bg-red-500 hover:text-white">
                    ×
                  </button>
                </div>
              ))}
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={handleAddQuick}
                disabled={quickButtons.length >= MAX_QUICK}
                className="px-4 py-2 rounded-xl border border-sky-500 text-sky-500 hover:bg-sky-500 hover:text-white disabled:opacity-50"
              >
                + Add button
              </button>
              <button type="button" onClick={handleSaveQuick} className="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-medium">
                Save
              </button>
              {quickStatus && <span className="text-sm text-slate-500">{quickStatus}</span>}
            </div>
          </section>

          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 className="text-lg font-semibold mb-2">Data export</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">Download tasks as JSON or CSV (Excel-friendly).</p>
            <div className="flex flex-wrap gap-2">
              <a href={getExportUrl()} download className="inline-block px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700">
                Export to JSON
              </a>
              <a href={getExportCsvUrl()} download className="inline-block px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700">
                Export to CSV
              </a>
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}
