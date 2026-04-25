import { useState, useEffect } from 'react';
import { Outlet, NavLink, Link, useLocation } from 'react-router-dom';
import { 
    Home, Users, User, Settings, Bell, Plus, Search, 
    Moon, Sun, ChevronDown, LogOut, ExternalLink, MessageSquare, LayoutDashboard,
    ShieldCheck, ChevronRight
} from 'lucide-react';
import { socialApi } from '../../api/social';
import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';
import { UserAvatar } from '../ui/StatusBadge';
import PopularGroupsWidget from '../social/PopularGroupsWidget';
import AnnouncementsWidget from '../social/AnnouncementsWidget';
import NotificationDropdown from '../social/NotificationDropdown';
import CreatePostModal from '../social/CreatePostModal';

export default function SocialLayout() {
    const { user, logout } = useAuth();
    const { dark, toggle } = useTheme();
    const [showNotifications, setShowNotifications] = useState(false);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showUserMenu, setShowUserMenu] = useState(false);
    const [managedHubs, setManagedHubs] = useState([]);

    useEffect(() => {
        fetchManagedHubs();
    }, []);

    const fetchManagedHubs = async () => {
        try {
            const res = await socialApi.getGroups();
            setManagedHubs(res.data.filter(g => g.user_role === 'manager' || g.user_role === 'admin'));
        } catch (err) {
            console.error('Failed to fetch managed hubs:', err);
        }
    };

    const navItems = [
        { to: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
        { to: '/social', icon: Home, label: 'Home Feed' },
        { to: '/social/groups', icon: Users, label: 'My Groups' },
        { to: '/social/profile', icon: User, label: 'My Profile' },
    ];

    const quickLinks = [
        { to: '/social/groups/1', label: 'Group Profile' },
        { to: '/social/posts/1', label: 'Single Post' },
    ];

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-surface-950 transition-colors duration-500">
            {/* Social Header */}
            <header className="sticky top-0 z-50 bg-white/70 dark:bg-surface-900/70 backdrop-blur-xl border-b border-slate-200 dark:border-white/5 px-4 h-16 flex items-center justify-between transition-all">
                <div className="flex items-center gap-4 max-w-7xl mx-auto w-full">
                    <Link to="/social" className="flex items-center gap-2 shrink-0">
                        <div className="w-8 h-8 rounded-lg bg-primary-500 flex items-center justify-center text-white shadow-glow shadow-primary-500/20">
                            <span className="font-black text-xs">CD</span>
                        </div>
                        <span className="font-black tracking-tighter text-lg dark:text-white hidden sm:inline-block">
                            Campus Dive
                        </span>
                    </Link>

                    <div className="hidden md:flex items-center bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-slate-800 rounded-full px-4 py-1.5 flex-1 max-w-md mx-8 group focus-within:border-primary-500/50 transition-all">
                        <Search className="w-4 h-4 text-slate-400 group-focus-within:text-primary-500 transition-colors" />
                        <input 
                            type="text" 
                            placeholder="Search groups, posts, or people..." 
                            className="bg-transparent border-none focus:ring-0 text-sm w-full dark:text-white placeholder:text-slate-500"
                        />
                    </div>

                    <div className="flex-1 md:hidden" />

                    <div className="flex items-center gap-2 sm:gap-4">
                        <button 
                            onClick={() => setShowCreateModal(true)}
                            className="btn-primary px-4 sm:px-6 h-10 rounded-full text-xs font-black uppercase tracking-widest shadow-glow shadow-primary-500/20"
                        >
                            <Plus className="w-4 h-4" />
                            <span className="hidden sm:inline">Post</span>
                        </button>

                        <div className="relative">
                            <button 
                                onClick={() => setShowNotifications(!showNotifications)}
                                className={`w-10 h-10 rounded-full flex items-center justify-center transition-all ${showNotifications ? 'bg-primary-500 text-white shadow-glow shadow-primary-500/20' : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10'}`}
                            >
                                <Bell className="w-5 h-5" />
                                <div className="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 border-2 border-white dark:border-surface-900 rounded-full" />
                            </button>
                            {showNotifications && <NotificationDropdown onClose={() => setShowNotifications(false)} />}
                        </div>

                        <button 
                            onClick={toggle}
                            className="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-white/10 transition-all"
                        >
                            {dark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                        </button>

                        <div className="relative">
                            <button 
                                onClick={() => setShowUserMenu(!showUserMenu)}
                                className="flex items-center gap-2 p-1 rounded-full bg-slate-100 dark:bg-white/10 border border-slate-200 dark:border-white/10 hover:border-primary-500/50 transition-all"
                            >
                                <UserAvatar user={user} size="sm" />
                                <ChevronDown className={`w-4 h-4 text-slate-500 transition-transform ${showUserMenu ? 'rotate-180' : ''}`} />
                            </button>
                            
                            {showUserMenu && (
                                <div className="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-[#0B1120] rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                                    <div className="p-4 border-b border-slate-100 dark:border-slate-800">
                                        <p className="font-bold text-sm dark:text-white truncate">{user?.firstname} {user?.lastname}</p>
                                        <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">{user?.role_name}</p>
                                    </div>
                                    <div className="p-2 space-y-1">
                                        <Link to="/social/profile" className="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                            <User className="w-4 h-4" />
                                            <span>Profile</span>
                                        </Link>
                                        <Link to="/settings" className="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                            <Settings className="w-4 h-4" />
                                            <span>Settings</span>
                                        </Link>
                                        <button 
                                            onClick={logout}
                                            className="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                                        >
                                            <LogOut className="w-4 h-4" />
                                            <span>Logout</span>
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content Area */}
            <main className="max-w-7xl mx-auto px-4 py-8">
                <div className="grid grid-cols-1 lg:grid-cols-[220px_1fr_280px] gap-8">
                    
                    {/* LEFT COLUMN: NAVIGATION */}
                    <aside className="hidden lg:block space-y-8">
                        <section>
                            <h3 className="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase mb-4 px-4">Menu</h3>
                            <nav className="space-y-1">
                                {navItems.map(item => (
                                    <NavLink
                                        key={item.to}
                                        to={item.to}
                                        end={item.to === '/social'}
                                        className={({ isActive }) => `
                                            flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all
                                            ${isActive 
                                                ? 'bg-primary-500/10 text-primary-500 shadow-sm border border-primary-500/20' 
                                                : 'text-slate-500 dark:text-slate-400 hover:bg-white dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white'
                                            }
                                        `}
                                    >
                                        <item.icon className="w-5 h-5" />
                                        <span>{item.label}</span>
                                    </NavLink>
                                ))}
                            </nav>
                        </section>

                        {managedHubs.length > 0 && (
                            <section>
                                <h3 className="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase mb-4 px-4">Managed Hubs</h3>
                                <nav className="space-y-1">
                                    {managedHubs.map(hub => (
                                        <Link
                                            key={hub.id}
                                            to={`/social/manager/${hub.slug}`}
                                            className="flex items-center gap-3 px-4 py-3 rounded-2xl text-[11px] font-black uppercase tracking-tight text-slate-500 hover:text-primary-500 hover:bg-white dark:hover:bg-white/5 transition-all group"
                                        >
                                            <div className="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-all overflow-hidden">
                                                {hub.avatar_url ? (
                                                    <img src={hub.avatar_url} alt="" className="w-full h-full object-cover" />
                                                ) : (
                                                    hub.icon_initials || hub.name[0]
                                                )}
                                            </div>
                                            <span className="flex-1 truncate">{hub.name}</span>
                                            <ChevronRight className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </Link>
                                    ))}
                                </nav>
                            </section>
                        )}

                        <section>
                            <h3 className="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase mb-4 px-4">Quick Links</h3>
                            <nav className="space-y-1">
                                {quickLinks.map(item => (
                                    <Link
                                        key={item.to}
                                        to={item.to}
                                        className="flex items-center justify-between gap-3 px-4 py-3 rounded-2xl text-sm font-bold text-slate-500 dark:text-slate-400 hover:bg-white dark:hover:bg-white/5 transition-all group"
                                    >
                                        <div className="flex items-center gap-3">
                                            <ExternalLink className="w-4 h-4 opacity-50 group-hover:opacity-100 transition-opacity" />
                                            <span>{item.label}</span>
                                        </div>
                                    </Link>
                                ))}
                            </nav>
                        </section>

                        <div className="px-4 py-6 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-6 text-white shadow-xl shadow-indigo-500/20 relative overflow-hidden group">
                            <div className="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700" />
                            <h4 className="font-black text-lg mb-2 relative z-10">TUM Tech</h4>
                            <p className="text-[10px] font-bold uppercase tracking-widest opacity-80 mb-4 relative z-10">Student Innovation</p>
                            <button className="w-full py-2 bg-white text-primary-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-colors relative z-10">
                                Learn More
                            </button>
                        </div>
                    </aside>

                    {/* CENTER COLUMN: FEED / CONTENT */}
                    <div className="space-y-6">
                        <div className="md:hidden flex items-center gap-2 overflow-x-auto pb-4 no-scrollbar">
                            {navItems.map(item => (
                                <NavLink
                                    key={item.to}
                                    to={item.to}
                                    end={item.to === '/social'}
                                    className={({ isActive }) => `
                                        flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap
                                        ${isActive 
                                            ? 'bg-primary-500 text-white shadow-glow shadow-primary-500/20' 
                                            : 'bg-white dark:bg-surface-900 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800'
                                        }
                                    `}
                                >
                                    <item.icon className="w-4 h-4" />
                                    <span>{item.label}</span>
                                </NavLink>
                            ))}
                        </div>
                        <Outlet />
                    </div>

                    {/* RIGHT COLUMN: WIDGETS */}
                    <aside className="hidden xl:block space-y-8">
                        <div className="bg-primary-500/5 dark:bg-primary-500/10 border border-primary-500/20 rounded-3xl p-6 text-center">
                            <div className="w-12 h-12 bg-primary-500 rounded-2xl flex items-center justify-center text-white mx-auto mb-4 animate-pulse-soft">
                                <MessageSquare className="w-6 h-6" />
                            </div>
                            <h4 className="font-black text-xs uppercase tracking-widest dark:text-white mb-2">TUM Tech Group</h4>
                            <p className="text-[10px] text-slate-500 font-medium leading-relaxed">
                                Join the official developer platform for TUM students.
                            </p>
                        </div>
                        <PopularGroupsWidget />
                        <AnnouncementsWidget />
                        
                        <footer className="px-6 text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 space-y-2">
                            <div className="flex flex-wrap gap-x-4 gap-y-2">
                                <Link to="#" className="hover:text-primary-500">Privacy</Link>
                                <Link to="#" className="hover:text-primary-500">Terms</Link>
                                <Link to="#" className="hover:text-primary-500">About</Link>
                                <Link to="#" className="hover:text-primary-500">TUM Hub</Link>
                            </div>
                            <p>© 2026 Campus Dive</p>
                        </footer>
                    </aside>

                </div>
            </main>

            <CreatePostModal isOpen={showCreateModal} onClose={() => setShowCreateModal(false)} />
        </div>
    );
}
