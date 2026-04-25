import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';
import { UserAvatar } from '../ui/StatusBadge';
import {
    LayoutDashboard, Users, MessageSquare, FileText, Settings,
    LogOut, Shield, BarChart3, Moon, Sun, Menu, X, ChevronDown, Users2
} from 'lucide-react';
import { useState } from 'react';

export default function Sidebar() {
    const { user, logout, isAdmin, isManager, isInterviewer, unreadCounts } = useAuth();
    const { dark, toggle } = useTheme();
    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    const getBadge = (label) => {
        if (label === 'Messages' && unreadCounts?.messages > 0) return unreadCounts.messages;
        if (label === 'Dashboard' && unreadCounts?.notifications > 0) return unreadCounts.notifications;
        return null;
    };

    const studentLinks = [
        { to: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
        { to: '/documents', icon: FileText, label: 'Documents' },
        { to: '/messages', icon: MessageSquare, label: 'Messages' },
        { to: '/social', icon: Users2, label: 'Social Hub' },
        { to: '/settings', icon: Settings, label: 'Settings' },
    ];

    const adminLinks = [
        { to: '/admin', icon: LayoutDashboard, label: 'Dashboard' },
        { to: '/admin/students', icon: Users, label: 'Students' },
        { to: '/admin/roles', icon: Shield, label: 'Roles', roles: ['Admin'] },
        { to: '/admin/social', icon: Users2, label: 'Hub Management', roles: ['Admin', 'Manager'] },
        { to: '/admin/social-hub', icon: Users2, label: 'Social Hub' },
        { to: '/admin/analytics', icon: BarChart3, label: 'Analytics', roles: ['Admin'] },
        { to: '/messages', icon: MessageSquare, label: 'Messages' },
        { to: '/settings', icon: Settings, label: 'Settings' },
    ];

    const filteredAdminLinks = adminLinks.filter(link => {
        if (!link.roles) return true;
        const userRole = user?.role_name || user?.role;
        return link.roles.includes(userRole);
    });

    const links = isAdmin || isManager || isInterviewer ? filteredAdminLinks : studentLinks;

    const sidebarContent = (
        <div className="flex flex-col h-full bg-surface-50 dark:bg-surface-950 transition-colors duration-500 overflow-hidden">
            {/* Logo Section */}
            <div className="flex items-center gap-4 px-8 py-10 border-b border-surface-200 dark:border-white/5 relative">
                 <div className="absolute top-0 left-0 w-1 h-32 bg-indigo-600 blur-xl opacity-20" />
                <div className="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-glow-indigo group cursor-pointer transition-transform hover:scale-110 active:scale-95">
                    <img src="/logo.png" alt="CD" className="w-7 h-7 object-contain brightness-0 invert" />
                </div>
                {!collapsed && (
                    <span className="font-black text-2xl tracking-tighter text-indigo-950 dark:text-white uppercase transition-all">
                        Campus<span className="text-indigo-600 font-display">Dive</span>
                    </span>
                )}
            </div>

            {/* Navigation Section */}
            <nav className="flex-1 px-4 py-10 space-y-3 overflow-y-auto custom-scrollbar">
                <div className="px-4 mb-6">
                    <p className="text-[9px] font-black uppercase tracking-[0.4em] text-surface-400 opacity-60">Control Console</p>
                </div>
                {links.map((link, index) => (
                    <NavLink
                        key={link.to}
                        to={link.to === '/admin/social-hub' ? '/social' : link.to}
                        target={link.target}
                        rel={link.target === '_blank' ? 'noopener noreferrer' : undefined}
                        style={{ animationDelay: `${index * 50}ms` }}
                        end={link.to === '/dashboard' || link.to === '/admin'}
                        onClick={() => setMobileOpen(false)}
                        className={({ isActive }) =>
                            `flex items-center gap-4 px-5 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all duration-500 group animate-stagger
                            ${isActive && !link.target
                                ? 'bg-indigo-600 text-white shadow-glow-indigo translate-x-2'
                                : 'text-surface-500 dark:text-surface-400 hover:bg-surface-100/80 dark:hover:bg-white/5 hover:text-indigo-600 dark:hover:text-indigo-400'
                            }`
                        }
                    >
                        <div className={`relative transition-transform duration-500 group-hover:scale-125`}>
                            <link.icon className="w-5 h-5 shrink-0" />
                            <div className={`absolute -inset-2 bg-indigo-500/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity`} />
                        </div>
                        {!collapsed && <span className="flex-1 tracking-[0.15em]">{link.label}</span>}
                        {!collapsed && getBadge(link.label) && (
                            <span className="bg-indigo-500 text-white text-[9px] h-5 min-w-5 flex items-center justify-center rounded-full font-black shadow-glow-indigo px-1">
                                {getBadge(link.label)}
                            </span>
                        )}
                    </NavLink>
                ))}
            </nav>

            {/* User & Settings Section */}
            <div className="p-6 border-t border-surface-200 dark:border-white/5 space-y-6 bg-surface-100/30 dark:bg-white/2">
                <button
                    onClick={toggle}
                    className="w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-surface-500 dark:text-surface-400 hover:bg-indigo-600 hover:text-white transition-all group overflow-hidden relative"
                >
                    <div className="w-5 h-5 flex items-center justify-center relative z-10">
                        {dark ? <Sun className="w-5 h-5 group-hover:rotate-90 transition-transform duration-500" /> : <Moon className="w-5 h-5 group-hover:-rotate-12 transition-transform duration-500" />}
                    </div>
                    {!collapsed && <span className="relative z-10">{dark ? 'Lumina Mode' : 'Shadow Mode'}</span>}
                </button>

                <div className="p-5 rounded-3xl bg-white dark:bg-white/5 border border-surface-200 dark:border-white/10 shadow-sm relative group">
                    <div className="flex items-center gap-4">
                        <div className="relative">
                            <div className="absolute -inset-1 bg-indigo-500 blur opacity-0 group-hover:opacity-20 transition-opacity" />
                            <UserAvatar user={user} size="sm" className="relative border-2 border-indigo-600/20" />
                        </div>
                        {!collapsed && (
                            <div className="flex-1 min-w-0">
                                <p className="text-[11px] font-black truncate text-indigo-950 dark:text-white uppercase tracking-tight">{user?.firstname} {user?.lastname}</p>
                                <p className="text-[8px] text-surface-400 font-black uppercase tracking-[0.2em] opacity-60 mt-0.5">{user?.role_name || user?.role}</p>
                            </div>
                        )}
                    </div>
                    {!collapsed && (
                        <button
                            onClick={handleLogout}
                            className="w-full mt-6 flex items-center justify-center gap-3 py-3 rounded-xl text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-500/5 hover:bg-rose-600 hover:text-white transition-all uppercase tracking-widest border border-rose-600/10"
                        >
                            <LogOut className="w-3.5 h-3.5" />
                            LOGOUT PROTOCOL
                        </button>
                    )}
                </div>
            </div>
        </div>
    );

    return (
        <>
            {/* Mobile toggle */}
            <button
                onClick={() => setMobileOpen(true)}
                className="fixed top-6 left-6 z-[200] lg:hidden w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-surface-900 shadow-glow-indigo border border-indigo-600/20 active:scale-95 transition-transform"
            >
                <Menu className="w-6 h-6 text-indigo-600" />
            </button>

            {/* Mobile overlay */}
            {mobileOpen && (
                <div className="fixed inset-0 z-[300] lg:hidden">
                    <div className="absolute inset-0 bg-indigo-950/60 backdrop-blur-sm transition-all" onClick={() => setMobileOpen(false)} />
                    <div className="relative w-80 h-full bg-surface-50 dark:bg-surface-950 flex flex-col animate-slide-in-right shadow-2xl overflow-hidden">
                        <button onClick={() => setMobileOpen(false)} className="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-xl bg-surface-100 dark:bg-white/10 text-surface-600 dark:text-surface-400 hover:bg-rose-500 hover:text-white transition-all z-10">
                            <X className="w-5 h-5" />
                        </button>
                        {sidebarContent}
                    </div>
                </div>
            )}

            {/* Desktop sidebar */}
            <aside className={`hidden lg:flex flex-col h-screen sticky top-0 border-r border-surface-200 dark:border-white/10 transition-all duration-500 ease-in-out z-[200] ${collapsed ? 'w-24' : 'w-80'}`}>
                {sidebarContent}
            </aside>
        </>
    );
}
