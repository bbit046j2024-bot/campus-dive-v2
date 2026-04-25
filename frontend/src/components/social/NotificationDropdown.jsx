import { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { notificationApi } from '../../api/notifications';

export default function NotificationDropdown({ notifications: initialNotifications, unreadCount: initialUnreadCount, onClose }) {
    const { isAdmin, isManager } = useAuth();
    const dropdownRef = useRef(null);
    const [notifications, setNotifications] = useState(initialNotifications || []);
    const [unreadCount, setUnreadCount] = useState(initialUnreadCount || 0);
    const [loading, setLoading] = useState(!initialNotifications);

    useEffect(() => {
        if (!initialNotifications) {
            fetchNotifications();
        }
    }, [initialNotifications]);

    const fetchNotifications = async () => {
        setLoading(true);
        try {
            const response = await notificationApi.getNotifications(5);
            if (response.success) {
                setNotifications(response.data.notifications || []);
                setUnreadCount(response.data.unread_count || 0);
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        } finally {
            setLoading(false);
        }
    };

    const getInitials = (title) => {
        if (!title) return '??';
        return title.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
    };

    const getGradient = (type) => {
        if (!type) return 'from-blue-500 to-indigo-600';
        switch(type.toLowerCase()) {
            case 'success': return 'from-emerald-500 to-teal-600';
            case 'warning': return 'from-amber-500 to-orange-600';
            case 'error': return 'from-rose-500 to-pink-600';
            default: return 'from-blue-500 to-indigo-600';
        }
    };

    useEffect(() => {
        function handleClickOutside(event) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                onClose();
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [onClose]);

    return (
        <div 
            ref={dropdownRef}
            className="absolute right-0 top-full mt-4 w-80 bg-white dark:bg-[#0B1120] rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200"
        >
            <div className="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 className="font-black text-sm tracking-tight dark:text-white">Notifications</h3>
                {unreadCount > 0 && (
                    <span className="bg-primary-500 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">{unreadCount} New</span>
                )}
            </div>
            
            <div className="max-h-[400px] overflow-y-auto">
                {notifications.length > 0 ? notifications.map(notif => (
                    <div 
                        key={notif.id} 
                        className={`p-4 flex gap-3 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors cursor-pointer ${!notif.is_read ? 'bg-primary-500/5' : ''}`}
                    >
                        <div className={`w-10 h-10 rounded-full bg-gradient-to-br ${getGradient(notif.type)} flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm`}>
                            {getInitials(notif.title)}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-bold dark:text-white mb-0.5 line-clamp-1">{notif.title}</p>
                            <p className="text-sm leading-snug dark:text-slate-200 mb-1 line-clamp-2">
                                {notif.message}
                            </p>
                            <div className="flex items-center gap-2">
                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    {new Date(notif.created_at).toLocaleDateString()}
                                </span>
                                {!notif.is_read && <div className="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse" />}
                            </div>
                        </div>
                    </div>
                )) : (
                    <div className="p-8 text-center text-slate-400">
                        <p className="text-xs font-bold uppercase tracking-widest">No notifications</p>
                    </div>
                )}
            </div>
            
            <Link 
                to="/notifications" 
                onClick={onClose}
                className="block p-4 bg-slate-50 dark:bg-white/5 border-t border-slate-100 dark:border-slate-800 text-center text-[10px] font-black tracking-[0.2em] text-slate-400 hover:text-primary-500 transition-colors uppercase"
            >
                View Full Notification →
            </Link>
        </div>
    );
}
