import { useState, useEffect } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { useTheme } from '../../context/ThemeContext';
import { Lock, Eye, EyeOff, Sun, Moon, CheckCircle, ArrowRight, ShieldCheck } from 'lucide-react';

export default function ResetPasswordPage() {
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [success, setSuccess] = useState(false);
    const [errors, setErrors] = useState({});
    const [params] = useSearchParams();
    const token = params.get('token');
    const navigate = useNavigate();
    const toast = useToast();
    const { dark, toggle } = useTheme();

    useEffect(() => {
        if (!token) {
            toast.error('Invalid or missing reset link.');
            navigate('/login');
        }
    }, [token]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        if (password !== confirmPassword) {
            setErrors({ confirm_password: 'Passwords do not match.' });
            return;
        }
        setLoading(true);
        try {
            await api.post('/auth/reset-password', {
                token,
                password,
                confirm_password: confirmPassword
            });
            setSuccess(true);
            toast.success('Password reset successful!');
            setTimeout(() => navigate('/login'), 3000);
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
            <div className="hidden lg:flex lg:w-[55%] relative overflow-hidden">
                <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1614741118887-7a4ee193a5fa?q=80&w=2070')] bg-cover bg-center">
                    <div className="absolute inset-0 bg-indigo-950/90 backdrop-blur-sm" />
                </div>
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-transparent to-emerald-500/10" />
                <div className="relative flex flex-col justify-center px-24 z-10">
                    <div className="animate-slide-up">
                        <div className="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center mb-10 shadow-glow-indigo">
                            <img src="/logo.png" alt="Logo" className="w-10 h-10 object-contain" />
                        </div>
                        <h1 className="text-7xl font-black text-white mb-6 tracking-tight leading-[1.1]">
                            New<br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-indigo-200">Passcode.</span>
                        </h1>
                        <p className="text-xl text-indigo-100/60 max-w-lg leading-relaxed font-medium">
                            Create a strong password to secure your recruitment profile and protect your progress.
                        </p>
                    </div>
                </div>
                <div className="absolute bottom-12 left-24 right-12 flex items-center justify-between text-[10px] font-black uppercase tracking-[0.3em] text-white/30">
                    <span>© 2026 CAMPUS DIVE GLOBAL</span>
                    <span className="text-indigo-400">SECURITY: ENCRYPTED</span>
                </div>
            </div>

            <div className="flex-1 flex items-center justify-center p-8 lg:p-20 relative">
                <div className="w-full max-w-md">
                    <div className="flex items-center justify-between mb-16">
                        <div className="flex items-center gap-3">
                            <div className="w-2 h-8 bg-indigo-600 rounded-full" />
                            <span className="text-[10px] font-black uppercase tracking-[0.3em] text-surface-400">Security Reset</span>
                        </div>
                        <button onClick={toggle} className="w-12 h-12 rounded-2xl flex items-center justify-center bg-surface-100 dark:bg-white/5 text-surface-600 dark:text-surface-400 hover:bg-indigo-600 hover:text-white transition-all">
                            {dark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                        </button>
                    </div>

                    {success ? (
                        <div className="text-center animate-fade-in">
                            <div className="w-20 h-20 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mb-8 mx-auto">
                                <CheckCircle className="w-10 h-10 text-emerald-500" />
                            </div>
                            <h2 className="text-4xl font-black mb-4 tracking-tight text-indigo-950 dark:text-white uppercase">Password Reset!</h2>
                            <p className="text-surface-500 dark:text-surface-400 mb-10 font-bold">
                                Your passcode has been updated. Redirecting to login...
                            </p>
                            <Link to="/login" className="btn-v2-primary py-4 px-8 text-[10px] font-black uppercase tracking-[0.2em] inline-flex items-center gap-3 shadow-glow-indigo">
                                Go to Login <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>
                    ) : (
                        <>
                            <div className="mb-12">
                                <h2 className="text-4xl font-black mb-3 tracking-tight text-indigo-950 dark:text-white uppercase">Reset <span className="text-indigo-600">Password</span></h2>
                                <p className="text-surface-500 font-bold uppercase tracking-widest text-[10px] opacity-60">Enter and confirm your new passcode below</p>
                            </div>

                            {errors.general && (
                                <div className="mb-8 p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-[10px] font-black uppercase tracking-widest text-rose-600 flex items-center gap-3">
                                    {errors.general}
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">New Passcode</label>
                                    <div className="relative">
                                        <Lock className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            value={password}
                                            onChange={e => setPassword(e.target.value)}
                                            className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-14 pr-14 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.password ? 'border-rose-500' : ''}`}
                                            placeholder="••••••••••••"
                                            required
                                            minLength={6}
                                        />
                                        <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-5 top-1/2 -translate-y-1/2 text-surface-300 hover:text-indigo-600 transition-colors">
                                            {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                                        </button>
                                    </div>
                                    {errors.password && <p className="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest ml-1">{errors.password}</p>}
                                </div>

                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Confirm Passcode</label>
                                    <div className="relative">
                                        <ShieldCheck className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            value={confirmPassword}
                                            onChange={e => setConfirmPassword(e.target.value)}
                                            className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-14 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.confirm_password ? 'border-rose-500' : ''}`}
                                            placeholder="••••••••••••"
                                            required
                                        />
                                    </div>
                                    {errors.confirm_password && <p className="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest ml-1">{errors.confirm_password}</p>}
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
                                            <Lock className="w-4 h-4" /> Update Password
                                        </span>
                                    )}
                                </button>
                            </form>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
