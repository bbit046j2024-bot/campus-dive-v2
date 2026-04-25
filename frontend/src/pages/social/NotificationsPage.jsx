import { useState, useEffect } from 'react';
import { notificationApi } from '../../api/notifications';
import { Bell, CheckCircle, Clock, Trash2, Filter, MoreVertical, RefreshCw } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';

export default function NotificationsPage() {
    const { fetchUnreadCounts } = useAuth();
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(true);
    const [unreadCount, setUnreadCount] = useState(0);
    const [filter, setFilter] = useState('all'); // all, unread

    const fetchNotifications = async () => {
        setLoading(true);
        try {
            const response = await notificationApi.getNotifications();
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

    useEffect(() => {
        fetchNotifications();
    }, []);

    const handleMarkAsRead = async (id) => {
        try {
            const response = await notificationApi.markAsRead(id);
            if (response.success) {
                setNotifications(notifications.map(n => n.id === id ? { ...n, is_read: 1 } : n));
                setUnreadCount(prev => Math.max(0, prev - 1));
                fetchUnreadCounts();
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const handleMarkAllRead = async () => {
        try {
            const response = await notificationApi.markAllRead();
            if (response.success) {
                setNotifications(notifications.map(n => ({ ...n, is_read: 1 })));
                setUnreadCount(0);
                fetchUnreadCounts();
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
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

    const filteredNotifications = filter === 'unread' 
        ? notifications.filter(n => !n.is_read) 
        : notifications;

    return (
        <div className="max-w-4xl mx-auto px-4 py-8">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 className="text-3xl font-black tracking-tight dark:text-white mb-2">Notifications</h1>
                    <p className="text-slate-500 dark:text-slate-400 font-medium">
                        Stay updated with your social activity and system alerts.
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <button 
                        onClick={handleMarkAllRead}
                        disabled={unreadCount === 0}
                        className="flex items-center gap-2 px-4 py-2 bg-white dark:bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                    >
                        <CheckCircle className="w-4 h-4" />
                        Mark all as read
                    </button>
                    <button 
                        onClick={fetchNotifications}
                        className="p-2 bg-white dark:bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/10 transition-all shadow-sm"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            <div className="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
                <button 
                    onClick={() => setFilter('all')}
                    className={`px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all whitespace-nowrap ${
                        filter === 'all' 
                        ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/25' 
                        : 'bg-white dark:bg-white/5 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50'
                    }`}
                >
                    All Notifications ({notifications.length})
                </button>
                <button 
                    onClick={() => setFilter('unread')}
                    className={`px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all whitespace-nowrap ${
                        filter === 'unread' 
                        ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/25' 
                        : 'bg-white dark:bg-white/5 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50'
                    }`}
                >
                    Unread ({unreadCount})
                </button>
            </div>

            <div className="bg-white dark:bg-[#0B1120] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
                {loading && notifications.length === 0 ? (
                    <div className="p-12 text-center">
                        <div className="w-12 h-12 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
                        <p className="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest text-xs">Loading notifications...</p>
                    </div>
                ) : filteredNotifications.length > 0 ? (
                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                        {filteredNotifications.map(notif => (
                            <div 
                                key={notif.id} 
                                onClick={() => !notif.is_read && handleMarkAsRead(notif.id)}
                                className={`p-6 flex gap-4 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors cursor-pointer group ${!notif.is_read ? 'bg-primary-500/5' : ''}`}
                            >
                                <div className={`w-12 h-12 rounded-2xl bg-gradient-to-br ${getGradient(notif.type)} flex items-center justify-center text-white text-sm font-black shrink-0 shadow-lg group-hover:scale-105 transition-transform`}>
                                    {getInitials(notif.title)}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-start justify-between gap-4 mb-1">
                                        <h3 className={`text-base font-bold dark:text-white line-clamp-1 ${!notif.is_read ? 'text-primary-600 dark:text-primary-400' : ''}`}>
                                            {notif.title}
                                        </h3>
                                        <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1.5 shrink-0">
                                            <Clock className="w-3 h-3" />
                                            {new Date(notif.created_at).toLocaleDateString([], { month: 'short', day: 'numeric' })}
                                        </span>
                                    </div>
                                    <p className="text-slate-600 dark:text-slate-300 leading-relaxed mb-3">
                                        {notif.message}
                                    </p>
                                    <div className="flex items-center gap-4">
                                        {!notif.is_read && (
                                            <span className="flex items-center gap-1.5 text-[10px] font-black text-primary-500 uppercase tracking-widest">
                                                <div className="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse" />
                                                New Notification
                                            </span>
                                        )}
                                        {notif.is_read ? (
                                             <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                Read
                                            </span>
                                        ) : (
                                            <button 
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    handleMarkAsRead(notif.id);
                                                }}
                                                className="text-[10px] font-black text-slate-400 hover:text-primary-500 uppercase tracking-widest transition-colors"
                                            >
                                                Mark as read
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button className="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                                        <MoreVertical className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="p-20 text-center">
                        <div className="w-20 h-20 bg-slate-50 dark:bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6">
                            <Bell className="w-10 h-10 text-slate-300 dark:text-slate-600" />
                        </div>
                        <h3 className="text-lg font-bold dark:text-white mb-2">No notifications yet</h3>
                        <p className="text-slate-500 dark:text-slate-400 text-sm max-w-xs mx-auto">
                            When you receive alerts or social updates, they'll appear here.
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
