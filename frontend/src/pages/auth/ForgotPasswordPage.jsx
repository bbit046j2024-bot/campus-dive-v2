import { useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { useTheme } from '../../context/ThemeContext';
import { Mail, ArrowLeft, Sun, Moon, CheckCircle, Send } from 'lucide-react';

export default function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [success, setSuccess] = useState(false);
    const [errors, setErrors] = useState({});
    const { dark, toggle } = useTheme();
    const toast = useToast();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setLoading(true);
        try {
            await api.post('/auth/forgot-password', { email });
            setSuccess(true);
            toast.success('Reset link sent if email exists.');
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
                            Account<br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-indigo-200">Recovery.</span>
                        </h1>
                        <p className="text-xl text-indigo-100/60 max-w-lg leading-relaxed font-medium">
                            Don't worry — it happens to the best of us. Enter your email and we'll send you a secure reset link instantly.
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
                            <span className="text-[10px] font-black uppercase tracking-[0.3em] text-surface-400">Recovery Portal</span>
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
                            <h2 className="text-4xl font-black mb-4 tracking-tight text-indigo-950 dark:text-white uppercase">Check Your Email</h2>
                            <p className="text-surface-500 dark:text-surface-400 mb-10 font-bold">
                                We've sent reset instructions to <strong className="text-indigo-600">{email}</strong>.
                            </p>
                            <button
                                onClick={() => { setSuccess(false); setEmail(''); }}
                                className="btn-v2-primary py-4 px-8 text-[10px] font-black uppercase tracking-[0.2em] mr-4"
                            >
                                Resend Link
                            </button>
                            <Link to="/login" className="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:underline underline-offset-4">
                                Back to Login
                            </Link>
                        </div>
                    ) : (
                        <>
                            <Link to="/login" className="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-surface-400 hover:text-indigo-600 transition-colors mb-12">
                                <ArrowLeft className="w-4 h-4" /> Back to Login
                            </Link>

                            <div className="mb-12">
                                <h2 className="text-4xl font-black mb-3 tracking-tight text-indigo-950 dark:text-white uppercase">Forgot <span className="text-indigo-600">Password?</span></h2>
                                <p className="text-surface-500 font-bold uppercase tracking-widest text-[10px] opacity-60">Enter your email to receive a secure reset link</p>
                            </div>

                            {errors.general && (
                                <div className="mb-8 p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-[10px] font-black uppercase tracking-widest text-rose-600 flex items-center gap-3">
                                    {errors.general}
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Email Address</label>
                                    <div className="relative">
                                        <Mail className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <input
                                            type="email"
                                            value={email}
                                            onChange={e => setEmail(e.target.value)}
                                            className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-14 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.email ? 'border-rose-500' : ''}`}
                                            placeholder="your-email@domain.com"
                                            required
                                        />
                                    </div>
                                    {errors.email && <p className="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest ml-1">{errors.email}</p>}
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
                                            <Send className="w-4 h-4" /> Send Reset Link
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
