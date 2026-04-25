import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import api from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [unreadCounts, setUnreadCounts] = useState({ messages: 0, notifications: 0 });

    const fetchUnreadCounts = useCallback(async () => {
        if (!user) return;
        try {
            await api.get('/auth/me'); // This actually returns CSRF too
            // We need a combined unread count endpoint or use individual ones
            // For now, let's keep it simple but manage it here
            const [msgRes, notifRes] = await Promise.all([
                api.get('/messages/unread-count'),
                api.get('/notifications/unread-count')
            ]);
            setUnreadCounts({
                messages: msgRes.data.count || 0,
                notifications: notifRes.data.count || 0
            });
        } catch (err) {
            console.error("Failed to fetch unread counts", err);
        }
    }, [user]);

    const checkAuth = useCallback(async () => {
        try {
            const res = await api.get('/auth/me');
            setUser(res.data.user);
        } catch {
            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        checkAuth();
    }, [checkAuth]);

    useEffect(() => {
        if (user) {
            fetchUnreadCounts();
            const interval = setInterval(fetchUnreadCounts, 60000); // Check every minute instead of 30s
            return () => clearInterval(interval);
        }
    }, [user, fetchUnreadCounts]);

    const login = async (email, password) => {
        const res = await api.post('/auth/login', { email, password });
        setUser(res.data.user);
        return res;
    };

    const register = async (data) => {
        return await api.post('/auth/register', data);
    };

    const logout = async () => {
        await api.post('/auth/logout');
        setUser(null);
    };

    const updateUser = (data) => {
        setUser(prev => ({ ...prev, ...data }));
    };

    const isAdmin = user?.role === 'admin' || user?.role_name === 'Admin';
    const isManager = user?.role_name === 'Manager';
    const isInterviewer = user?.role_name === 'Interviewer';
    const isStudent = !isAdmin && !isManager && !isInterviewer;

    return (
        <AuthContext.Provider value={{
            user, loading, login, register, logout, updateUser, checkAuth,
            unreadCounts, fetchUnreadCounts, setUnreadCounts,
            isAdmin, isManager, isInterviewer, isStudent,
        }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth must be used within AuthProvider');
    return ctx;
}
