import React, { createContext, useState, useContext, useEffect } from 'react';
import { apiService } from '../services/api';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Check for existing session on mount
  useEffect(() => {
    // Hydrate from localStorage (fallback when no session endpoint is available)
    try {
      const stored = localStorage.getItem('dz_user');
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed && typeof parsed === 'object') {
          setUser(parsed);
        }
      }
    } catch {}
    setLoading(false);
  }, []);

  const login = async (email, password) => {
    try {
      const res = await apiService.login({ email, password });
      if (res.data && res.data.success) {
        setUser(res.data.user);
        try { localStorage.setItem('dz_user', JSON.stringify(res.data.user)); } catch {}
        return { success: true, user: res.data.user };
      }
      return { success: false, error: res.data?.message || 'فشل تسجيل الدخول' };
    } catch (error) {
      return { success: false, error: 'فشل تسجيل الدخول' };
    }
  };

  const logout = () => {
    setUser(null);
    try { localStorage.removeItem('dz_user'); } catch {}
    // Optionally, call a logout endpoint to clear cookie
  };

  const signup = async (name, email, password) => {
    try {
      const res = await apiService.signup({ name, email, password });
      if (res.data && res.data.success) {
        setUser(res.data.user);
        try { localStorage.setItem('dz_user', JSON.stringify(res.data.user)); } catch {}
        return { success: true, user: res.data.user };
      }
      return { success: false, error: res.data?.error || 'فشل التسجيل' };
    } catch (e) {
      return { success: false, error: 'فشل التسجيل' };
    }
  };

  const isAdmin = () => {
    return user && user.role === 'admin';
  };

  const isAuthenticated = () => {
    return !!user;
  };

  const value = {
    user,
    login,
    logout,
    signup,
    isAdmin,
    isAuthenticated,
    loading
  };

  return (
    <AuthContext.Provider value={value}>
      {!loading && children}
    </AuthContext.Provider>
  );
};

export default AuthContext;