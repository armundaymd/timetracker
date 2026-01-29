import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getAnalytics } from '../api';

function formatDuration(seconds) {
  if (!seconds || seconds < 0) return '0h 0m';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

function heatmapLevel(seconds) {
  if (!seconds || seconds < 3600) return 0;
  const h = seconds / 3600;
  if (h < 4) return 1;
  if (h < 8) return 2;
  return 3;
}

const LEVEL_COLORS = [
  'bg-slate-200 dark:bg-slate-600',
  'bg-emerald-300 dark:bg-emerald-800',
  'bg-emerald-500 dark:bg-emerald-600',
  'bg-emerald-700 dark:bg-emerald-400',
];

export default function Analytics() {
  const [start, setStart] = useState('');
  const [end, setEnd] = useState('');
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const e = new Date();
    const s = new Date();
    s.setDate(s.getDate() - 30);
    setStart(s.toISOString().slice(0, 10));
    setEnd(e.toISOString().slice(0, 10));
  }, []);

  useEffect(() => {
    if (!start || !end) return;
    setLoading(true);
    getAnalytics(start, end)
      .then(setData)
      .catch(() => setData(null))
      .finally(() => setLoading(false));
  }, [start, end]);

  const total = data?.total_seconds ?? 0;
  const activities = data?.by_activity ?? [];
  const byProject = data?.by_project ?? [];
  const heatmapDays = data?.heatmap_days ?? {};
  const maxSeconds = Math.max(...activities.map((a) => Number(a.seconds)), 1);

  const heatmapCells = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  for (let i = 0; i < 365; i++) {
    const d = new Date(today);
    d.setDate(d.getDate() - 364 + i);
    const key = d.toISOString().slice(0, 10);
    const sec = heatmapDays[key] || 0;
    heatmapCells.push({ key, sec, level: heatmapLevel(sec) });
  }

  return (
    <div className="min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white font-sans pb-8">
      <div className="max-w-2xl mx-auto px-4 py-6">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-xl font-semibold">Analytics</h1>
          <Link to="/" className="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
            ← Back to Tracker
          </Link>
        </div>

        <div className="space-y-6">
          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <label className="block text-sm font-semibold mb-2">Date range</label>
            <div className="flex flex-wrap gap-2 mb-4">
              <input
                type="date"
                value={start}
                onChange={(e) => setStart(e.target.value)}
                className="px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
              />
              <input
                type="date"
                value={end}
                onChange={(e) => setEnd(e.target.value)}
                className="px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
              />
            </div>
            <div className="flex gap-6 flex-wrap">
              <div>
                <div className="text-3xl font-bold">{formatDuration(total)}</div>
                <div className="text-sm text-slate-500 dark:text-slate-400">Total tracked time</div>
              </div>
              <div>
                <div className="text-3xl font-bold">{activities.length}</div>
                <div className="text-sm text-slate-500 dark:text-slate-400">Activities</div>
              </div>
            </div>
          </section>

          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 className="text-lg font-semibold mb-2">Contribution heatmap (last 365 days)</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mb-3">Tracked time per day.</p>
            <div className="flex flex-wrap gap-0.5">
              {heatmapCells.map(({ key, sec, level }) => (
                <span
                  key={key}
                  title={`${key}: ${formatDuration(sec)}`}
                  className={`w-3 h-3 rounded-sm ${LEVEL_COLORS[level]}`}
                />
              ))}
            </div>
            <div className="flex items-center gap-2 mt-2 text-xs text-slate-500 dark:text-slate-400">
              <span>Less</span>
              {LEVEL_COLORS.map((c, i) => (
                <span key={i} className={`w-2.5 h-2.5 rounded ${c}`} />
              ))}
              <span>More</span>
            </div>
          </section>

          <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 className="text-lg font-semibold mb-3">Time by activity</h2>
            {loading ? (
              <p className="text-sm text-slate-500">Loading...</p>
            ) : activities.length === 0 ? (
              <p className="text-sm text-slate-500">No tracked time in this range.</p>
            ) : (
              <div className="space-y-3">
                {activities.map((item) => {
                  const sec = Number(item.seconds);
                  const pct = total > 0 ? (100 * sec) / total : 0;
                  const barPct = (100 * sec) / maxSeconds;
                  return (
                    <div key={item.title} className="space-y-1">
                      <div className="flex justify-between text-sm">
                        <span className="font-medium truncate">{item.title}</span>
                        <span className="text-slate-500 flex-shrink-0">{formatDuration(sec)} · {pct.toFixed(0)}%</span>
                      </div>
                      <div className="h-2 rounded-full bg-slate-200 dark:bg-slate-600 overflow-hidden">
                        <div
                          className="h-full rounded-full bg-sky-500 transition-all"
                          style={{ width: `${barPct}%` }}
                        />
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </section>

          {byProject.length > 0 && (
            <section className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h2 className="text-lg font-semibold mb-3">Time by project</h2>
              <div className="space-y-3">
                {byProject.map((p) => {
                  const sec = Number(p.seconds);
                  const pct = total > 0 ? (100 * sec) / total : 0;
                  const maxProj = Math.max(...byProject.map((x) => Number(x.seconds)), 1);
                  const barPct = (100 * sec) / maxProj;
                  return (
                    <div key={p.name} className="space-y-1">
                      <div className="flex justify-between text-sm">
                        <span className="font-medium">{p.name}</span>
                        <span className="text-slate-500">{formatDuration(sec)} · {pct.toFixed(0)}%</span>
                      </div>
                      <div className="h-2 rounded-full bg-slate-200 dark:bg-slate-600 overflow-hidden">
                        <div
                          className="h-full rounded-full transition-all"
                          style={{ width: `${barPct}%`, backgroundColor: p.color || '#0ea5e9' }}
                        />
                      </div>
                    </div>
                  );
                })}
              </div>
            </section>
          )}
        </div>
      </div>
    </div>
  );
}
