import { Link, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';
import { Moon, Sun, Menu, X, ArrowRight } from 'lucide-react';
import { useState } from 'react';

export default function PublicLayout() {
    const { user, isAdmin, isManager } = useAuth();
    const { dark, toggle } = useTheme();
    const [mobileOpen, setMobileOpen] = useState(false);
    const location = useLocation();

    const navLinks = [
        { to: '/', label: 'Home' },
        { to: '/about', label: 'About' },
    ];

    const dashboardPath = isAdmin || isManager ? "/admin" : "/dashboard";

    return (
        <div className="min-h-screen bg-surface-50 dark:bg-surface-950 transition-colors duration-500">
            {/* Navigation */}
            <nav className="fixed top-0 left-0 right-0 z-50 glass border-b border-white/10">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16 items-center">
                        <Link to="/" className="flex items-center gap-3 active:scale-95 transition-transform">
                            <img src="/logo.png" alt="Campus Dive" className="w-8 h-8 object-contain" />
                            <span className="font-extrabold text-xl tracking-tighter bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent">
                                Campus Dive
                            </span>
                        </Link>

                        {/* Desktop Nav */}
                        <div className="hidden md:flex items-center gap-8">
                            {navLinks.map(link => (
                                <Link
                                    key={link.to}
                                    to={link.to}
                                    className={`text-sm font-semibold transition-colors ${location.pathname === link.to
                                            ? 'text-primary-500'
                                            : 'text-surface-600 dark:text-surface-400 hover:text-primary-500'
                                        }`}
                                >
                                    {link.label}
                                </Link>
                            ))}

                            <div className="h-6 w-px bg-surface-200 dark:bg-surface-800 mx-2" />

                            <button onClick={toggle} className="btn-icon w-9 h-9">
                                {dark ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
                            </button>

                            {user ? (
                                <Link to={dashboardPath} className="btn-primary py-2 px-5 text-sm flex items-center gap-2">
                                    Dashboard <ArrowRight className="w-4 h-4" />
                                </Link>
                            ) : (
                                <div className="flex items-center gap-3">
                                    <Link to="/login" className="text-sm font-bold text-surface-600 dark:text-surface-400 hover:text-primary-500 transition-colors">
                                        Sign In
                                    </Link>
                                    <Link to="/register" className="btn-primary py-2 px-5 text-sm">
                                        Join Now
                                    </Link>
                                </div>
                            )}
                        </div>

                        {/* Mobile Toggle */}
                        <div className="md:hidden flex items-center gap-4">
                            <button onClick={toggle} className="btn-icon">
                                {dark ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
                            </button>
                            <button onClick={() => setMobileOpen(!mobileOpen)} className="btn-icon">
                                {mobileOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile Menu */}
                {mobileOpen && (
                    <div className="md:hidden border-t border-white/10 bg-white dark:bg-surface-900 animate-fade-in">
                        <div className="px-4 py-6 space-y-4 text-center">
                            {navLinks.map(link => (
                                <Link
                                    key={link.to}
                                    to={link.to}
                                    onClick={() => setMobileOpen(false)}
                                    className="block text-lg font-bold text-surface-700 dark:text-surface-200"
                                >
                                    {link.label}
                                </Link>
                            ))}
                            <hr className="border-white/10" />
                            {user ? (
                                <Link to={dashboardPath} onClick={() => setMobileOpen(false)} className="btn-primary w-full py-3 inline-flex justify-center items-center gap-2">
                                    Go to Dashboard <ArrowRight className="w-4 h-4" />
                                </Link>
                            ) : (
                                <div className="space-y-3">
                                    <Link to="/login" onClick={() => setMobileOpen(false)} className="block font-bold text-primary-500 py-2">
                                        Sign In
                                    </Link>
                                    <Link to="/register" onClick={() => setMobileOpen(false)} className="btn-primary w-full py-3 inline-flex justify-center">
                                        Register as Student
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </nav>

            {/* Content */}
            <main className="pt-16">
                <Outlet />
            </main>

            {/* Simple Footer */}
            <footer className="py-12 border-t border-white/10 bg-surface-50 dark:bg-surface-950">
                <div className="max-w-7xl mx-auto px-4 text-center">
                    <div className="flex items-center justify-center gap-3 mb-6">
                        <img src="/logo.png" alt="Campus Dive" className="w-6 h-6 object-contain" />
                        <span className="font-bold text-lg tracking-tight">Campus Dive</span>
                    </div>
                    <p className="text-surface-500 dark:text-surface-400 text-sm mb-8">
                        Technical University of Mombasa • TUM Tech Group
                    </p>
                    <div className="flex justify-center gap-8 mb-8">
                        {navLinks.map(link => (
                            <Link key={link.to} to={link.to} className="text-xs font-bold uppercase tracking-wider text-surface-400 hover:text-primary-500 transition-colors">
                                {link.label}
                            </Link>
                        ))}
                    </div>
                    <p className="text-xs text-surface-400">
                        &copy; {new Date().getFullYear()} Campus Dive. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}
