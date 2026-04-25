import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Users, UserCheck, Clock, XCircle, TrendingUp, ArrowRight, MessageSquare, Bell, Calendar } from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import NotificationDropdown from '../../components/social/NotificationDropdown';
import { SkeletonStats, SkeletonTable } from '../../components/ui/Skeleton';
import { StatusBadge, UserAvatar } from '../../components/ui/StatusBadge';
import api from '../../api/client';

export default function AdminDashboard() {
    const { user, isAdmin, isManager, isInterviewer } = useAuth();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [showNotifications, setShowNotifications] = useState(false);

    useEffect(() => {
        const endpoint = isInterviewer ? '/admin/interviews' : '/admin/dashboard';
        api.get(endpoint).then(res => {
            setData(res.data);
            setLoading(false);
        }).catch(() => setLoading(false));
    }, [isInterviewer]);

    if (loading) {
        return (
            <div className="space-y-6 animate-fade-in">
                <SkeletonStats count={4} />
                <SkeletonTable />
            </div>
        );
    }

    // Role-based title and description
    const getDashboardHeader = () => {
        if (isAdmin) return { title: "Admin", sub: "Command Center", desc: "Global campus recruitment overview & analytics" };
        if (isManager) return { title: "Manager", sub: "Recruitment Portal", desc: "Manage student applications and workflow stages" };
        if (isInterviewer) return { title: "Interviewer", sub: "Evaluation Desk", desc: "Review candidates and manage your interview schedule" };
        return { title: "Dashboard", sub: "Portal", desc: "Overview of your activities" };
    };

    const header = getDashboardHeader();

    const stats = data?.stats || {};
    const statCards = isInterviewer ? [
        { label: 'Upcoming Interviews', value: stats.upcoming || 0, icon: Calendar, color: 'blue', change: null },
        { label: 'Completed', value: stats.completed || 0, icon: UserCheck, color: 'emerald', change: null },
        { label: 'Assigned Students', value: stats.assigned || 0, icon: Users, color: 'amber', change: null },
        { label: 'Pending Feedback', value: stats.pending_feedback || 0, icon: Clock, color: 'red', change: null },
    ] : [
        { label: 'Total Students', value: stats.total_students, icon: Users, color: 'blue', change: '+12%' },
        { label: 'Pending', value: stats.pending, icon: Clock, color: 'amber', change: null },
        { label: 'Approved', value: stats.approved, icon: UserCheck, color: 'emerald', change: '+8%' },
        { label: 'Rejected', value: stats.rejected, icon: XCircle, color: 'red', change: null },
    ];

    const colorClasses = {
        blue: { bg: 'bg-blue-50 dark:bg-blue-900/20', text: 'text-blue-600 dark:text-blue-400' },
        amber: { bg: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-600 dark:text-amber-400' },
        emerald: { bg: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-600 dark:text-emerald-400' },
        red: { bg: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-600 dark:text-red-400' },
    };

    return (
        <div className="space-y-10 animate-fade-in pb-20">
            <div className="flex items-center justify-between animate-stagger relative z-[100]" style={{ animationDelay: '0ms' }}>
                <div>
                    <h1 className="text-3xl md:text-4xl font-black tracking-tight text-indigo-950 dark:text-white">
                        {header.title} <span className="text-indigo-600 font-display">{header.sub}</span>
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-medium">{header.desc}</p>
                </div>
                <div className="flex items-center gap-4">
                    <div className="relative">
                        <button
                            onClick={() => setShowNotifications(!showNotifications)}
                            className={`w-14 h-14 rounded-3xl border flex items-center justify-center transition-all duration-300 relative shadow-soft ${showNotifications ? 'bg-indigo-600 text-white shadow-glow' : 'bg-white dark:bg-surface-900 border-surface-200 dark:border-surface-800 text-surface-400 hover:border-indigo-200'}`}
                        >
                            <Bell className={`w-6 h-6 ${showNotifications ? '' : 'hover:animate-swing'}`} />
                            {data?.unread_notifications > 0 && (
                                <span className="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white dark:border-surface-900 animate-bounce">
                                    {data.unread_notifications}
                                </span>
                            )}
                        </button>

                        {showNotifications && (
                            <NotificationDropdown
                                notifications={data?.notifications}
                                unreadCount={data?.unread_notifications}
                                onClose={() => setShowNotifications(false)}
                            />
                        )}
                    </div>
                    {!isInterviewer && (
                        <Link to="/admin/students" className="btn-v2-primary shadow-lifted shadow-indigo-500/20">
                            <Users className="w-4 h-4" /> MANAGE POOL
                        </Link>
                    )}
                </div>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {statCards.map((stat, index) => (
                    <div key={stat.label} className="card-premium p-8 animate-stagger" style={{ animationDelay: `${(index + 1) * 100}ms` }}>
                        <div className="flex items-center justify-between mb-6">
                            <div className={`w-12 h-12 rounded-2xl ${colorClasses[stat.color].bg} flex items-center justify-center shadow-sm`}>
                                <stat.icon className={`w-6 h-6 ${colorClasses[stat.color].text}`} />
                            </div>
                            {stat.change && (
                                <span className="flex items-center gap-1 text-[10px] font-black text-emerald-600 bg-emerald-500/10 px-3 py-1 rounded-full tracking-widest uppercase">
                                    <TrendingUp className="w-3 h-3" /> {stat.change}
                                </span>
                            )}
                        </div>
                        <p className="text-4xl font-display font-black text-surface-900 dark:text-white">{stat.value || 0}</p>
                        <p className="text-xs font-black text-surface-500 mt-2 uppercase tracking-widest opacity-60">{stat.label}</p>
                    </div>
                ))}
            </div>

            {/* Chart & Recent Students */}
            <div className="grid grid-cols-1 lg:grid-cols-5 gap-8">
                {/* Trends Chart */}
                <div className="lg:col-span-3 card-premium p-8 animate-stagger" style={{ animationDelay: '500ms' }}>
                    <h3 className="font-display font-black text-xl mb-8 text-surface-900 dark:text-white flex items-center gap-3">
                        <div className="w-2 h-6 bg-indigo-600 rounded-full" />
                        Application Trends
                    </h3>
                    {data?.trends?.length > 0 ? (
                        <ResponsiveContainer width="100%" height={320}>
                            <AreaChart data={data.trends}>
                                <defs>
                                    <linearGradient id="colorCount" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#8b5cf6" stopOpacity={0.3} />
                                        <stop offset="95%" stopColor="#8b5cf6" stopOpacity={0} />
                                    </linearGradient>
                                    <linearGradient id="colorApproved" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#10b981" stopOpacity={0.3} />
                                        <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} className="stroke-surface-100 dark:stroke-surface-800" />
                                <XAxis 
                                    dataKey="month" 
                                    axisLine={false}
                                    tickLine={false}
                                    className="text-[10px] font-black uppercase tracking-widest" 
                                    tick={{ fill: '#94a3b8' }} 
                                    dy={10}
                                />
                                <YAxis 
                                    axisLine={false}
                                    tickLine={false}
                                    className="text-[10px] font-black" 
                                    tick={{ fill: '#94a3b8' }} 
                                />
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: 'rgba(255, 255, 255, 0.8)',
                                        backdropFilter: 'blur(12px)',
                                        border: '1px solid rgba(255, 255, 255, 0.2)',
                                        borderRadius: '16px',
                                        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
                                        padding: '12px'
                                    }}
                                    itemStyle={{ fontSize: '12px', fontWeight: 'bold' }}
                                />
                                <Area type="monotone" dataKey="count" stroke="#8b5cf6" fillOpacity={1} fill="url(#colorCount)" strokeWidth={3} name="Total Pipeline" />
                                <Area type="monotone" dataKey="approved" stroke="#10b981" fillOpacity={1} fill="url(#colorApproved)" strokeWidth={3} name="Approved Only" />
                            </AreaChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="h-64 flex flex-col items-center justify-center text-surface-400 gap-4">
                            <TrendingUp className="w-12 h-12 opacity-10" />
                            <p className="font-medium font-display">No trend data captured yet</p>
                        </div>
                    )}
                </div>

                {/* Recent Students */}
                <div className="lg:col-span-2 card-premium overflow-hidden animate-stagger" style={{ animationDelay: '600ms' }}>
                    <div className="flex items-center justify-between p-8 border-b border-surface-100 dark:border-white/5">
                        <h3 className="font-display font-black text-xl text-surface-900 dark:text-white">Recent Entries</h3>
                        <Link to="/admin/students" className="btn-v2-secondary !px-4 !py-2 text-[10px] font-black tracking-widest">
                            VIEW ALL <ArrowRight className="w-3 h-3 ml-1" />
                        </Link>
                    </div>
                    <div className="divide-y divide-surface-100 dark:divide-white/5">
                        {data?.recent_students?.slice(0, 6).map(student => (
                            <Link
                                key={student.id}
                                to={`/admin/students?view=${student.id}`}
                                className="flex items-center gap-4 p-6 hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition-all group"
                            >
                                <UserAvatar user={student} size="sm" />
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-black truncate text-surface-900 dark:text-white group-hover:text-indigo-600 transition-colors uppercase tracking-tight">{student.firstname} {student.lastname}</p>
                                    <p className="text-[10px] text-surface-500 font-bold uppercase opacity-60">Submitted {new Date(student.created_at).toLocaleDateString()}</p>
                                </div>
                                <StatusBadge status={student.status} />
                            </Link>
                        ))}
                    </div>
                </div>
            </div>

            {/* Quick Actions */}
            <div className="card-premium p-8 animate-stagger" style={{ animationDelay: '700ms' }}>
                <h3 className="font-display font-black text-xl mb-6 text-surface-900 dark:text-white">Quick Actions</h3>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Link to="/admin/students?status=pending" className="flex items-center gap-4 p-5 rounded-3xl border border-surface-100 dark:border-white/5 hover:border-indigo-200 dark:hover:border-indigo-900 group hover:bg-indigo-50/50 dark:hover:bg-indigo-500/5 transition-all shadow-sm">
                        <div className="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <Clock className="w-6 h-6 text-amber-600" />
                        </div>
                        <div>
                            <p className="text-sm font-black text-surface-900 dark:text-white uppercase tracking-tight">Review Pending</p>
                            <p className="text-xs text-surface-500 font-bold opacity-60">{stats.pending || 0} AWAITING ACTION</p>
                        </div>
                    </Link>
                    <Link to="/messages" className="flex items-center gap-4 p-5 rounded-3xl border border-surface-100 dark:border-white/5 hover:border-indigo-200 dark:hover:border-indigo-900 group hover:bg-indigo-50/50 dark:hover:bg-indigo-500/5 transition-all shadow-sm">
                        <div className="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <MessageSquare className="w-6 h-6 text-indigo-600" />
                        </div>
                        <div>
                            <p className="text-sm font-black text-surface-900 dark:text-white uppercase tracking-tight">Direct Messages</p>
                            <p className="text-xs text-surface-500 font-bold opacity-60">{data?.unread_messages || 0} UNREAD MESSAGES</p>
                        </div>
                    </Link>
                    <Link to="/admin/analytics" className="flex items-center gap-4 p-5 rounded-3xl border border-surface-100 dark:border-white/5 hover:border-indigo-200 dark:hover:border-indigo-900 group hover:bg-indigo-50/50 dark:hover:bg-indigo-500/5 transition-all shadow-sm">
                        <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <TrendingUp className="w-6 h-6 text-emerald-600" />
                        </div>
                        <div>
                            <p className="text-sm font-black text-surface-900 dark:text-white uppercase tracking-tight">Performance Reports</p>
                            <p className="text-xs text-surface-500 font-bold opacity-60">VIEW ANALYTICS</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    );
}
