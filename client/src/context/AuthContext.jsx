import { createContext, useContext, useState, useEffect } from 'react';
import { setToken as apiSetToken } from '../api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setTokenState] = useState(() => localStorage.getItem('token') || '');
  const [ready, setReady] = useState(false);

  useEffect(() => {
    setReady(true);
  }, []);

  const setToken = (t) => {
    setTokenState(t || '');
    apiSetToken(t);
  };

  const logout = () => setToken('');

  return (
    <AuthContext.Provider value={{ token, setToken, logout, ready }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
