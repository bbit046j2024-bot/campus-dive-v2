import { useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { useTheme } from '../../context/ThemeContext';
import { Mail, ArrowLeft, Sun, Moon, CheckCircle } from 'lucide-react';

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
        <div className="min-h-screen flex bg-surface-50 dark:bg-surface-950 transition-colors duration-500">
            {/* Left Panel - Visual */}
            <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden">
                <div className="absolute inset-0 bg-mesh-primary opacity-50" />
                <div className="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900" />

                <div className="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-primary-400/20 rounded-full blur-[120px] animate-pulse-soft" />
                <div className="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-indigo-500/20 rounded-full blur-[120px]" />

                <div className="relative flex flex-col justify-center px-20 z-10 animate-premium">
                    <div className="w-16 h-16 rounded-3xl bg-white/10 backdrop-blur-2xl border border-white/20 flex items-center justify-center text-white font-bold text-2xl mb-12 shadow-glow">
                        CD
                    </div>
                    <h1 className="text-6xl font-extrabold text-white mb-8 tracking-tighter leading-tight">
                        Reset<br /><span className="text-primary-300">Password</span>.
                    </h1>
                    <p className="text-xl text-primary-100/80 max-w-md leading-relaxed font-light">
                        Don't worry, it happens to the best of us. Let's get you back into your account.
                    </p>
                </div>
            </div>

            {/* Right Panel - Form */}
            <div className="flex-1 flex items-center justify-center p-8 sm:p-16 relative">
                <div className="absolute top-0 right-0 w-64 h-64 bg-primary-500/5 rounded-full blur-3xl -z-10" />
                <div className="absolute bottom-0 left-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -z-10" />

                <div className="w-full max-w-md animate-premium delay-150">
                    <div className="flex justify-end mb-12">
                        <button onClick={toggle} className="btn-icon bg-surface-100/50 dark:bg-surface-800/50 glass">
                            {dark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                        </button>
                    </div>

                    <Link to="/login" className="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700 mb-8 transition-colors">
                        <ArrowLeft className="w-4 h-4" /> Back to Login
                    </Link>

                    {success ? (
                        <div className="animate-premium">
                            <div className="w-20 h-20 rounded-3xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-8">
                                <CheckCircle className="w-10 h-10 text-emerald-500" />
                            </div>
                            <h2 className="text-4xl font-extrabold mb-4 tracking-tight">Check your email</h2>
                            <p className="text-surface-600 dark:text-surface-400 text-lg mb-10">
                                We've sent password reset instructions to <strong>{email}</strong>.
                            </p>
                            <button onClick={() => setSuccess(false)} className="btn-secondary w-full py-3">
                                Resend Email
                            </button>
                        </div>
                    ) : (
                        <>
                            <h2 className="text-4xl font-extrabold mb-3 tracking-tight">Forgot password?</h2>
                            <p className="text-surface-500 dark:text-surface-400 mb-10 text-lg font-medium">No problem! Enter your email below.</p>

                            {errors.general && (
                                <div className="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400">
                                    {errors.general}
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Email Address</label>
                                    <div className="relative">
                                        <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400" />
                                        <input
                                            type="email"
                                            value={email}
                                            onChange={e => setEmail(e.target.value)}
                                            className={`input-field pl-12 ${errors.email ? 'border-red-500 focus:ring-red-500/50' : ''}`}
                                            placeholder="name@university.edu"
                                            required
                                        />
                                    </div>
                                    {errors.email && <p className="mt-1 text-sm text-red-500 font-medium">{errors.email}</p>}
                                </div>

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="btn-primary w-full py-3 text-lg font-bold"
                                >
                                    {loading ? (
                                        <div className="w-6 h-6 border-3 border-white/30 border-t-white rounded-full animate-spin mx-auto" />
                                    ) : 'Send Reset Link'}
                                </button>
                            </form>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
