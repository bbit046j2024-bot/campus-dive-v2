import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { useTheme } from '../../context/ThemeContext';
import { 
    User, Mail, Lock, Eye, EyeOff, Phone, 
    CreditCard, ArrowRight, Moon, Sun, 
    CheckCircle, XCircle, ChevronLeft, Building2 
} from 'lucide-react';
import api from '../../api/client';

export default function RegisterPage() {
    const [step, setStep] = useState(1);
    const [form, setForm] = useState({
        firstname: '', lastname: '', email: '', phone: '', 
        student_id: '', department: 'Computer Science',
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
    const strengthColors = ['', 'bg-rose-500', 'bg-orange-500', 'bg-amber-500', 'bg-emerald-500', 'bg-emerald-600'];

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

    const departments = [
        'Computer Science',
        'Information Technology',
        'Software Engineering',
        'Business & Commerce',
        'Electrical Engineering',
        'Mechanical Engineering',
        'Arts & Social Sciences'
    ];

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
            {/* Left Panel - Visual */}
            <div className="hidden lg:flex lg:w-[45%] relative overflow-hidden">
                <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=2070')] bg-cover bg-center">
                    <div className="absolute inset-0 bg-indigo-950/90 backdrop-blur-sm" />
                </div>
                
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-transparent to-emerald-500/10" />

                <div className="relative flex flex-col justify-center px-24 z-10">
                    <div className="animate-slide-up" style={{ animationDelay: '100ms' }}>
                        <div className="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center mb-10 shadow-glow-indigo">
                            <Building2 className="w-8 h-8 text-white" />
                        </div>
                        <h1 className="text-6xl font-black text-white mb-6 tracking-tight leading-[1.1]">
                            Join the <br/>
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-indigo-200">Mainframe.</span>
                        </h1>
                        <p className="text-lg text-indigo-100/60 max-w-sm leading-relaxed font-medium mb-12">
                            Initialize your student credentials to access exclusive campus hubs and networking channels.
                        </p>

                        <div className="flex items-center gap-4">
                            <div className={`w-3 h-3 rounded-full transition-all duration-500 ${step === 1 ? 'bg-indigo-500 w-8' : 'bg-white/20'}`} />
                            <div className={`w-3 h-3 rounded-full transition-all duration-500 ${step === 2 ? 'bg-indigo-500 w-8' : 'bg-white/20'}`} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Right Panel - Form */}
            <div className="flex-1 flex flex-col items-center justify-center p-8 lg:p-20 relative overflow-y-auto custom-scrollbar">
                <div className="w-full max-w-xl animate-stagger">
                    <div className="flex items-center justify-between mb-12">
                        <div className="flex items-center gap-3">
                             <div className="w-2 h-8 bg-indigo-600 rounded-full" />
                             <span className="text-[10px] font-black uppercase tracking-[0.3em] text-surface-400">Step {step} of 2</span>
                        </div>
                        <button onClick={toggle} className="w-12 h-12 rounded-2xl flex items-center justify-center bg-surface-100 dark:bg-white/5 text-surface-600 dark:text-surface-400 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                            {dark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                        </button>
                    </div>

                    <div className="mb-10">
                        <h2 className="text-4xl font-black mb-3 tracking-tight text-indigo-950 dark:text-white uppercase transition-all">
                            {step === 1 ? 'Personal' : 'Academic'} <span className="text-indigo-600">Sync</span>
                        </h2>
                        <p className="text-surface-500 font-bold uppercase tracking-widest text-[10px] opacity-60 leading-relaxed">
                            {step === 1 ? 'Establish your digital identity within the network' : 'Secure your profile with campus credentials'}
                        </p>
                    </div>

                    {errors.general && (
                        <div className="mb-8 p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-[10px] font-black uppercase tracking-widest text-rose-600 flex items-center gap-3 animate-shake">
                            <XCircle className="w-4 h-4" /> {errors.general}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {step === 1 ? (
                            <div className="space-y-6 animate-in fade-in slide-in-from-right-8 duration-500">
                                <div className="grid grid-cols-2 gap-6">
                                    <div className="group">
                                        <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1">First Name</label>
                                        <div className="relative">
                                            <User className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                            <input type="text" value={form.firstname} onChange={update('firstname')} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" placeholder="John" required />
                                        </div>
                                    </div>
                                    <div className="group">
                                        <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1">Last Name</label>
                                        <div className="relative">
                                            <User className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                            <input type="text" value={form.lastname} onChange={update('lastname')} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" placeholder="Doe" required />
                                        </div>
                                    </div>
                                </div>

                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1">Email Address</label>
                                    <div className="relative">
                                        <Mail className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <input type="email" value={form.email} onChange={update('email')} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" placeholder="john@student.tum.ac.ke" required />
                                    </div>
                                </div>

                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1">Phone Number</label>
                                    <div className="relative">
                                        <Phone className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <input type="tel" value={form.phone} onChange={update('phone')} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" placeholder="+254 XXX XXX XXX" required />
                                    </div>
                                </div>

                                <button type="button" onClick={() => setStep(2)} className="btn-v2-primary w-full py-5 text-xs font-black uppercase tracking-[0.2em] shadow-glow-indigo group mt-4 flex items-center justify-center gap-3">
                                    NEXT PROTOCOL <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </button>
                            </div>
                        ) : (
                            <div className="space-y-6 animate-in fade-in slide-in-from-left-8 duration-500">
                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1">Department / Faculty</label>
                                    <div className="relative">
                                        <Building2 className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <select 
                                            value={form.department} 
                                            onChange={update('department')} 
                                            className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none appearance-none cursor-pointer"
                                        >
                                            {departments.map(d => <option key={d} value={d} className="dark:bg-surface-900">{d}</option>)}
                                        </select>
                                    </div>
                                </div>

                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1">Student ID Number</label>
                                    <div className="relative">
                                        <CreditCard className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <input type="text" value={form.student_id} onChange={update('student_id')} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" placeholder="CS/001/2024" required />
                                    </div>
                                </div>

                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1">Master Password</label>
                                    <div className="relative">
                                        <Lock className="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                        <input type={showPassword ? 'text' : 'password'} value={form.password} onChange={update('password')} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-12 pr-12 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" placeholder="••••••••••••" required minLength={6} />
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
                                            <p className="text-[9px] font-black uppercase tracking-widest text-surface-400">Entropy: <span className={strengthColors[strength].replace('bg-', 'text-')}>{strengthLabels[strength]}</span></p>
                                        </div>
                                    )}
                                </div>

                                <div className="flex gap-4 mt-8">
                                    <button type="button" onClick={() => setStep(1)} className="p-4 rounded-2xl bg-surface-100 dark:bg-white/5 text-surface-500 hover:text-indigo-600 transition-all shadow-sm">
                                        <ChevronLeft className="w-6 h-6" />
                                    </button>
                                    <button type="submit" disabled={loading} className="btn-v2-primary flex-1 py-5 text-xs font-black uppercase tracking-[0.2em] shadow-glow-indigo flex items-center justify-center gap-3">
                                        {loading ? (
                                            <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                        ) : (
                                            <>INITIALIZE PROFILE <ArrowRight className="w-4 h-4" /></>
                                        )}
                                    </button>
                                </div>
                            </div>
                        )}

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
                            className="w-full py-4 px-6 rounded-2xl bg-white dark:bg-white/5 border border-surface-200 dark:border-white/5 hover:border-indigo-600 transition-all flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-widest text-surface-600 dark:text-surface-300 shadow-sm group"
                        >
                            <img src="https://www.google.com/favicon.ico" alt="Google" className="w-4 h-4 group-hover:scale-110 transition-transform" />
                            SYNC GOOGLE ACCOUNT
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
",Description:
