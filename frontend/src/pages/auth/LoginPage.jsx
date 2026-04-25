import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { useTheme } from '../../context/ThemeContext';
import { Mail, Lock, Eye, EyeOff, ArrowRight, Moon, Sun, CheckCircle, XCircle } from 'lucide-react';
import api from '../../api/client';

export default function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const { login } = useAuth();
    const toast = useToast();
    const { dark, toggle } = useTheme();
    const navigate = useNavigate();
    const [params] = useSearchParams();

    const verified = params.get('verified') === 'true';

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setLoading(true);

        try {
            const res = await login(email, password);
            toast.success('Welcome back!');
            const role = res.data.user.role || res.data.user.role_name;
            navigate(role === 'admin' || role === 'Admin' ? '/admin' : '/dashboard');
        } catch (err) {
            if (err.errors) {
                setErrors(err.errors);
            } else {
                setErrors({ general: err.message });
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex bg-surface-50 dark:bg-surface-950 transition-colors duration-500 overflow-hidden">
            {/* Left Panel - Visual (Digital Industrial Aesthetic) */}
            <div className="hidden lg:flex lg:w-[55%] relative overflow-hidden">
                <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1542744173-8e7e53415bb0?q=80&w=2070')] bg-cover bg-center">
                    <div className="absolute inset-0 bg-indigo-950/90 backdrop-blur-sm" />
                </div>
                
                {/* Dynamic Overlays */}
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-transparent to-emerald-500/10" />
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(99,102,241,0.15)_0%,transparent_70%)]" />

                <div className="relative flex flex-col justify-center px-24 z-10">
                    <div className="animate-slide-up" style={{ animationDelay: '100ms' }}>
                        <div className="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center mb-10 shadow-glow-indigo">
                            <img src="/logo.png" alt="Logo" className="w-10 h-10 object-contain" />
                        </div>
                        <h1 className="text-7xl font-black text-white mb-6 tracking-tight leading-[1.1]">
                            The Future <br/>
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-indigo-200">Of Talent.</span>
                        </h1>
                        <p className="text-xl text-indigo-100/60 max-w-lg leading-relaxed font-medium mb-12">
                            A high-intelligence recruitment ecosystem designed to bridge the gap between academic excellence and industrial leadership.
                        </p>

                        <div className="flex items-center gap-10">
                            <div className="flex -space-x-3">
                                {[1,2,3,4].map((i) => (
                                    <div key={i} className="w-12 h-12 rounded-full border-2 border-indigo-900 bg-surface-800 flex items-center justify-center text-[10px] font-black text-white overflow-hidden">
                                        <img src={`https://i.pravatar.cc/100?img=${i+10}`} alt="User" />
                                    </div>
                                ))}
                                <div className="w-12 h-12 rounded-full border-2 border-indigo-900 bg-indigo-600 flex items-center justify-center text-[10px] font-black text-white">
                                    +5k
                                </div>
                            </div>
                            <div className="h-10 w-px bg-white/10" />
                            <div>
                                <p className="text-white font-black uppercase tracking-widest text-[10px]">Active Candidates</p>
                                <p className="text-indigo-400 text-xs font-bold mt-0.5">READY FOR DEPLOYMENT</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom Stats Badge */}
                <div className="absolute bottom-12 left-24 right-12 flex items-center justify-between text-[10px] font-black uppercase tracking-[0.3em] text-white/30 animate-fade-in delay-700">
                    <span>© 2026 CAMPUS DIVE GLOBAL</span>
                    <div className="flex gap-6">
                        <span className="text-indigo-400">SECURITY: ENCRYPTED</span>
                        <span>STATUS: OPERATIONAL</span>
                    </div>
                </div>
            </div>

            {/* Right Panel - Form */}
            <div className="flex-1 flex items-center justify-center p-8 lg:p-20 relative">
                <div className="w-full max-w-md animate-stagger">
                    {/* Theme & Meta */}
                    <div className="flex items-center justify-between mb-16">
                        <div className="flex items-center gap-3">
                             <div className="w-2 h-8 bg-indigo-600 rounded-full" />
                             <span className="text-[10px] font-black uppercase tracking-[0.3em] text-surface-400">Identity Portal</span>
                        </div>
                        <button onClick={toggle} className="w-12 h-12 rounded-2xl flex items-center justify-center bg-surface-100 dark:bg-white/5 text-surface-600 dark:text-surface-400 hover:bg-indigo-600 hover:text-white transition-all">
                            {dark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                        </button>
                    </div>

                    <div className="mb-12">
                        <h2 className="text-4xl font-black mb-3 tracking-tight text-indigo-950 dark:text-white uppercase transition-all">Welcome <span className="text-indigo-600">Back</span></h2>
                        <p className="text-surface-500 font-bold uppercase tracking-widest text-[10px] opacity-60">Authorize to access your recruitment mainframe</p>
                    </div>

                    {errors.general && (
                        <div className="mb-8 p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-[10px] font-black uppercase tracking-widest text-rose-600 flex items-center gap-3 animate-shake">
                            <XCircle className="w-4 h-4" /> {errors.general}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="group">
                            <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Email Reference</label>
                            <div className="relative">
                                <Mail className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                <input
                                    type="email"
                                    value={email}
                                    onChange={e => setEmail(e.target.value)}
                                    className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-14 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.email ? 'border-rose-500' : ''}`}
                                    placeholder="your-alias@domain.com"
                                    required
                                />
                            </div>
                        </div>

                        <div className="group">
                            <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Passcode</label>
                            <div className="relative">
                                <Lock className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    value={password}
                                    onChange={e => setPassword(e.target.value)}
                                    className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-14 pr-14 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.password ? 'border-rose-500' : ''}`}
                                    placeholder="••••••••••••"
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute right-5 top-1/2 -translate-y-1/2 text-surface-300 hover:text-indigo-600 transition-colors"
                                >
                                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                                </button>
                            </div>
                        </div>

                        <div className="flex items-center justify-between pt-2">
                             <Link to="/forgot-password" weights="black" className="text-[10px] font-black uppercase tracking-widest text-surface-400 hover:text-indigo-600 transition-colors">
                                Recovery Protocol?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            disabled={loading}
                            className="btn-v2-primary w-full py-5 text-xs font-black uppercase tracking-[0.2em] shadow-glow-indigo group"
                        >
                            {loading ? (
                                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin mx-auto" />
                            ) : (
                                <span className="flex items-center justify-center gap-3">
                                    ESTABLISH CONNECTION <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </span>
                            )}
                        </button>

                        <div className="relative py-8 flex items-center gap-4">
                            <div className="flex-1 h-px bg-surface-200 dark:bg-white/5" />
                            <span className="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em]">Or Continue With</span>
                            <div className="flex-1 h-px bg-surface-200 dark:bg-white/5" />
                        </div>

                        <button
                            type="button"
                            onClick={async () => {
                                try {
                                    const res = await api.get('/auth/google-url');
                                    if (res.data?.url) window.location.href = res.data.url;
                                } catch (err) {
                                    toast.error('Google ID Failure');
                                }
                            }}
                            className="w-full py-4 px-6 rounded-2xl bg-white dark:bg-white/5 border border-surface-200 dark:border-indigo-500/30 hover:border-indigo-600 hover:shadow-lg hover:shadow-indigo-500/10 transition-all flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-widest text-surface-700 dark:text-surface-200 shadow-sm"
                        >
                            <img src="https://www.google.com/favicon.ico" alt="Google" className="w-4 h-4" />
                            Sign in with Google
                        </button>
                    </form>

                    <p className="mt-12 text-center text-[10px] font-black text-surface-400 uppercase tracking-widest">
                        New Asset?{' '}
                        <Link to="/register" className="text-indigo-600 hover:underline underline-offset-4">
                            Initialize Registration
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
