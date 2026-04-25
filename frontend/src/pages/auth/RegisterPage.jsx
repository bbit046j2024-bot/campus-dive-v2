import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { useTheme } from '../../context/ThemeContext';
import { User, Mail, Lock, Eye, EyeOff, Phone, CreditCard, ArrowRight, Moon, Sun, CheckCircle, XCircle } from 'lucide-react';
import api from '../../api/client';

export default function RegisterPage() {
    const [form, setForm] = useState({
        firstname: '', lastname: '', email: '', phone: '', student_id: '',
        password: '', confirm_password: '',
    });
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [success, setSuccess] = useState(false);
    const { register } = useAuth();
    const toast = useToast();
    const { dark, toggle } = useTheme();

    const update = (field) => (e) => setForm(f => ({ ...f, [field]: e.target.value }));

    // Password strength
    const getStrength = (pw) => {
        let s = 0;
        if (pw.length >= 6) s++;
        if (pw.length >= 8) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[0-9]/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        return s;
    };

    const strength = getStrength(form.password);
    const strengthLabels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Excellent'];
    const strengthColors = ['', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-emerald-500', 'bg-emerald-600'];

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setLoading(true);

        try {
            await register(form);
            setSuccess(true);
            toast.success('Registration successful! Check your email.');
        } catch (err) {
            if (err.errors) setErrors(err.errors);
            else setErrors({ general: err.message });
        } finally {
            setLoading(false);
        }
    };

    if (success) {
        return (
            <div className="min-h-screen flex items-center justify-center p-8 bg-surface-50 dark:bg-surface-950">
                <div className="max-w-md w-full text-center animate-stagger">
                    <div className="w-24 h-24 rounded-3xl bg-emerald-500/10 flex items-center justify-center mx-auto mb-10 shadow-glow-emerald animate-bounce-soft">
                        <CheckCircle className="w-12 h-12 text-emerald-500" />
                    </div>
                    <h2 className="text-4xl font-black mb-4 tracking-tight text-indigo-950 dark:text-white uppercase transition-all">Verification <span className="text-emerald-500">Sent</span></h2>
                    <p className="text-surface-500 font-bold uppercase tracking-widest text-[10px] opacity-60 mb-12 leading-relaxed">
                        Authorized credentials detected. We've dispatched a synchronization link to <span className="text-indigo-600">{form.email}</span>. 
                        Awaiting activation...
                    </p>
                    <Link to="/login" className="btn-v2-primary py-5 px-12 text-xs font-black uppercase tracking-[0.2em] shadow-glow-indigo inline-flex items-center gap-3">
                        TERMINAL ACCESS <ArrowRight className="w-4 h-4" />
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen flex bg-surface-50 dark:bg-surface-950 transition-colors duration-500 overflow-hidden">
            {/* Left Panel - Visual (Digital Industrial Aesthetic) */}
            <div className="hidden lg:flex lg:w-[45%] relative overflow-hidden">
                <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070')] bg-cover bg-center">
                    <div className="absolute inset-0 bg-indigo-950/90 backdrop-blur-sm" />
                </div>
                
                {/* Dynamic Overlays */}
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-transparent to-emerald-500/10" />

                <div className="relative flex flex-col justify-center px-24 z-10">
                    <div className="animate-slide-up" style={{ animationDelay: '100ms' }}>
                        <div className="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center mb-10 shadow-glow-indigo">
                            <img src="/logo.png" alt="Logo" className="w-10 h-10 object-contain" />
                        </div>
                        <h1 className="text-6xl font-black text-white mb-6 tracking-tight leading-[1.1]">
                            Initialize <br/>
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-indigo-200">Registration.</span>
                        </h1>
                        <p className="text-lg text-indigo-100/60 max-w-sm leading-relaxed font-medium mb-12">
                            Deploy your professional profile to our global recruitment mainframe.
                        </p>

                        <div className="space-y-6">
                            {[
                                { t: 'Real-time Deployment Tracking', d: 'Monitor status in milliseconds' },
                                { t: 'Encrypted Asset Management', d: 'Military-grade document storage' },
                                { t: 'Direct Logic Channel', d: 'Low-latency communication with peers' }
                            ].map((item, i) => (
                                <div key={i} className="flex items-center gap-5 p-4 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/10 transition-colors group cursor-default">
                                    <div className="w-10 h-10 rounded-xl bg-indigo-600/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <CheckCircle className="w-5 h-5 text-indigo-400" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-black text-white uppercase tracking-widest">{item.t}</p>
                                        <p className="text-indigo-300/40 text-[10px] font-bold mt-0.5">{item.d}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {/* Right Panel - Form (Scrollable) */}
            <div className="flex-1 flex flex-col items-center justify-start p-8 lg:p-20 relative overflow-y-auto custom-scrollbar">
                <div className="w-full max-w-xl animate-stagger">
                    {/* Theme & Meta */}
                    <div className="flex items-center justify-between mb-12">
                        <div className="flex items-center gap-3">
                             <div className="w-2 h-8 bg-indigo-600 rounded-full" />
                             <span className="text-[10px] font-black uppercase tracking-[0.3em] text-surface-400">Onboarding Protocol</span>
                        </div>
                        <button onClick={toggle} className="w-12 h-12 rounded-2xl flex items-center justify-center bg-surface-100 dark:bg-white/5 text-surface-600 dark:text-surface-400 hover:bg-indigo-600 hover:text-white transition-all">
                            {dark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                        </button>
                    </div>

                    <div className="mb-10">
                        <h2 className="text-4xl font-black mb-3 tracking-tight text-indigo-950 dark:text-white uppercase transition-all">New <span className="text-indigo-600">Asset</span> Registration</h2>
                        <p className="text-surface-500 font-bold uppercase tracking-widest text-[10px] opacity-60 leading-relaxed">System registration grants access to the high-intelligence deployment network</p>
                    </div>

                    {errors.general && (
                        <div className="mb-8 p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-[10px] font-black uppercase tracking-widest text-rose-600 flex items-center gap-3 animate-shake">
                            <XCircle className="w-4 h-4" /> {errors.general}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6 pb-12">
                        <div className="grid grid-cols-2 gap-6">
                            <div className="group">
                                <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">First Name Reference</label>
                                <div className="relative">
                                    <User className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                    <input type="text" value={form.firstname} onChange={update('firstname')} className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.firstname ? 'border-rose-500' : ''}`} placeholder="e.g. John" required />
                                </div>
                            </div>
                            <div className="group">
                                <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Family Name Reference</label>
                                <div className="relative">
                                    <User className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                    <input type="text" value={form.lastname} onChange={update('lastname')} className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.lastname ? 'border-rose-500' : ''}`} placeholder="e.g. Doe" required />
                                </div>
                            </div>
                        </div>

                        <div className="group">
                            <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Global Email Address</label>
                            <div className="relative">
                                <Mail className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                <input type="email" value={form.email} onChange={update('email')} className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.email ? 'border-rose-500' : ''}`} placeholder="candidate@network.com" required />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-6">
                            <div className="group">
                                <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Telecom Reference</label>
                                <div className="relative">
                                    <Phone className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                    <input type="tel" value={form.phone} onChange={update('phone')} className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.phone ? 'border-rose-500' : ''}`} placeholder="+254 XXX XXX" required />
                                </div>
                            </div>
                            <div className="group">
                                <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Institutional ID</label>
                                <div className="relative">
                                    <CreditCard className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                    <input type="text" value={form.student_id} onChange={update('student_id')} className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.student_id ? 'border-rose-500' : ''}`} placeholder="ID-000-X" required />
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-6">
                            <div className="group">
                                <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Encryption Passcode</label>
                                <div className="relative">
                                    <Lock className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                    <input type={showPassword ? 'text' : 'password'} value={form.password} onChange={update('password')} className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-12 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.password ? 'border-rose-500' : ''}`} placeholder="••••••••••••" required minLength={6} />
                                    <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-5 top-1/2 -translate-y-1/2 text-surface-300 hover:text-indigo-600 transition-colors">
                                        {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                    </button>
                                </div>
                                {form.password && (
                                    <div className="mt-3 px-1">
                                        <div className="flex gap-1.5 mb-1.5">
                                            {[1, 2, 3, 4, 5].map(i => (
                                                <div key={i} className={`h-1.5 flex-1 rounded-full transition-all duration-500 ${i <= strength ? strengthColors[strength] : 'bg-surface-200 dark:bg-white/5'}`} />
                                            ))}
                                        </div>
                                        <p className="text-[9px] font-black uppercase tracking-widest text-surface-400">Entropy Strength: <span className={strengthColors[strength].replace('bg-', 'text-')}>{strengthLabels[strength]}</span></p>
                                    </div>
                                )}
                            </div>
                            <div className="group">
                                <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Re-verify Passcode</label>
                                <div className="relative">
                                    <Lock className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                    <input type="password" value={form.confirm_password} onChange={update('confirm_password')} className={`w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none ${errors.confirm_password ? 'border-rose-500' : ''}`} placeholder="••••••••••••" required />
                                </div>
                            </div>
                        </div>

                        <button type="submit" disabled={loading} className="btn-v2-primary w-full py-5 text-xs font-black uppercase tracking-[0.2em] shadow-glow-indigo group mt-4">
                            {loading ? (
                                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin mx-auto" />
                            ) : (
                                <span className="flex items-center justify-center gap-3">
                                    INITIALIZE ASSET PROFILE <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </span>
                            )}
                        </button>

                        <div className="relative py-8 flex items-center gap-4">
                            <div className="flex-1 h-px bg-surface-200 dark:bg-white/5" />
                            <span className="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em]">External Identity</span>
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
                            className="w-full py-4 px-6 rounded-2xl bg-white dark:bg-white/5 border border-surface-200 dark:border-white/5 hover:border-indigo-600 transition-all flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-widest text-surface-600 dark:text-surface-300 shadow-sm"
                        >
                            <img src="https://www.google.com/favicon.ico" alt="Google" className="w-4 h-4 grayscale opacity-70" />
                            LINK GOOGLE IDENTITY
                        </button>

                        <p className="mt-12 text-center text-[10px] font-black text-surface-400 uppercase tracking-widest">
                            Existing Asset?{' '}
                            <Link to="/login" className="text-indigo-600 hover:underline underline-offset-4">
                                Authentication Terminal
                            </Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    );
}
