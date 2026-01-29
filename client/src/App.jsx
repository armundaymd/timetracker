import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import Login from './pages/Login';
import Tracker from './pages/Tracker';
import Settings from './pages/Settings';
import Analytics from './pages/Analytics';

function PrivateRoute({ children }) {
  const { token, ready } = useAuth();
  if (!ready) return <div className="min-h-screen flex items-center justify-center bg-slate-100 dark:bg-slate-900">Loading...</div>;
  if (!token) return <Navigate to="/login" replace />;
  return children;
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/" element={<PrivateRoute><Tracker /></PrivateRoute>} />
      <Route path="/settings" element={<PrivateRoute><Settings /></PrivateRoute>} />
      <Route path="/analytics" element={<PrivateRoute><Analytics /></PrivateRoute>} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
