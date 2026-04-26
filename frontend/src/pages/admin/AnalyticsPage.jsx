import { useState, useEffect } from 'react';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { 
    BarChart3, TrendingUp, Users, CheckCircle, 
    XCircle, Clock, Mail, ShieldCheck, Zap 
} from 'lucide-react';

export default function AnalyticsPage() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [testingEmail, setTestingEmail] = useState(false);
    const toast = useToast();

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const res = await api.get('/admin/dashboard');
            setData(res.data);
        } catch (err) {
            toast.error('Failed to load analytics: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleTestEmail = async () => {
        setTestingEmail(true);
        try {
            await api.post('/admin/system/test-email');
            toast.success('Diagnostic email dispatched successfully!');
        } catch (err) {
            toast.error('Email Dispatch Failed. Check SMTP credentials.');
        } finally {
            setTestingEmail(false);
        }
    };

    if (loading) return (
        <div className="flex items-center justify-center min-h-[400px]">
            <div className="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
        </div>
    );

    const stats = [
        { label: 'Total Students', value: data?.stats?.total_students || 0, icon: Users, color: 'text-indigo-500', bg: 'bg-indigo-50 dark:bg-indigo-900/10' },
        { label: 'Approved', value: data?.stats?.approved || 0, icon: CheckCircle, color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-900/10' },
        { label: 'Pending Sync', value: data?.stats?.pending || 0, icon: Clock, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-900/10' },
        { label: 'Decommissioned', value: data?.stats?.rejected || 0, icon: XCircle, color: 'text-rose-500', bg: 'bg-rose-50 dark:bg-rose-900/10' },
    ];

    return (
        <div className="animate-fade-in space-y-10 pb-20">
            <div className="flex items-center justify-between animate-stagger" style={{ animationDelay: '0ms' }}>
                <div>
                    <h1 className="text-3xl md:text-4xl font-black tracking-tight text-indigo-950 dark:text-white flex items-center gap-4">
                        Intelligence <span className="text-indigo-600 font-display">& Assets</span>
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-medium uppercase text-[10px] tracking-[0.2em] opacity-70">Strategic performance metrics and uplink diagnostics</p>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {stats.map((stat, i) => (
                    <div key={i} className="card-premium p-8 flex items-center gap-5 animate-stagger" style={{ animationDelay: `${(i + 1) * 100}ms` }}>
                        <div className={`w-14 h-14 rounded-3xl flex items-center justify-center shadow-sm ${stat.bg}`}>
                            <stat.icon className={`w-7 h-7 ${stat.color}`} />
                        </div>
                        <div>
                            <p className="text-[10px] font-black text-surface-500 uppercase tracking-[0.2em] opacity-60 mb-1">{stat.label}</p>
                            <p className="text-3xl font-display font-black text-surface-900 dark:text-white">{stat.value}</p>
                        </div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 card-premium p-8 lg:p-10 animate-stagger" style={{ animationDelay: '500ms' }}>
                    <div className="flex items-center justify-between mb-12">
                        <h3 className="font-display font-black text-xl text-surface-900 dark:text-white flex items-center gap-3">
                            <div className="w-2 h-6 bg-indigo-600 rounded-full" />
                            Enrollment Velocity
                        </h3>
                        <span className="text-[10px] font-black px-4 py-2 rounded-full bg-indigo-600/10 text-indigo-600 tracking-widest uppercase">
                            H1 REPORTING CYCLE
                        </span>
                    </div>

                    <div className="h-64 flex items-end justify-between gap-4 px-4">
                        {data?.trends?.map((item, i) => {
                            const max = Math.max(...data.trends.map(t => t.count), 1);
                            const height = (item.count / max) * 100;
                            return (
                                <div key={i} className="flex-1 flex flex-col items-center gap-4 group">
                                    <div className="w-full relative flex flex-col items-center justify-end h-full">
                                        <div
                                            className="w-full bg-indigo-100 dark:bg-white/5 rounded-2xl transition-all duration-500 group-hover:bg-indigo-600 group-hover:shadow-glow-indigo relative overflow-hidden"
                                            style={{ height: `${height}%` }}
                                        >
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                                            <div className="absolute -top-8 left-1/2 -translate-x-1/2 text-[10px] font-black text-indigo-600 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition-all group-hover:-top-10">
                                                {item.count}
                                            </div>
                                        </div>
                                    </div>
                                    <span className="text-[10px] font-black text-surface-400 uppercase tracking-tighter group-hover:text-indigo-600 transition-colors">{item.month}</span>
                                </div>
                            );
                        })}
                    </div>
                </div>

                <div className="space-y-8 animate-stagger" style={{ animationDelay: '600ms' }}>
                    <div className="card-premium p-8 lg:p-10 relative overflow-hidden group">
                        <div className="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                            <Mail className="w-32 h-32" />
                        </div>
                        <h3 className="font-display font-black text-xl mb-6 text-surface-900 dark:text-white flex items-center gap-3">
                            <ShieldCheck className="w-6 h-6 text-indigo-600" />
                            Email System
                        </h3>
                        <p className="text-[11px] font-bold text-surface-500 uppercase tracking-widest leading-relaxed mb-8">
                            Verify your SMTP uplink to ensure all verification and protocol emails are active.
                        </p>
                        
                        <button 
                            onClick={handleTestEmail}
                            disabled={testingEmail}
                            className="w-full btn-v2-primary py-4 text-[10px] font-black tracking-[0.2em] uppercase flex items-center justify-center gap-3 shadow-glow-indigo"
                        >
                            {testingEmail ? (
                                <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                            ) : (
                                <><Zap className="w-4 h-4" /> SEND DIAGNOSTIC</>
                            )}
                        </button>
                    </div>

                    <div className="card-premium p-8 lg:p-10 bg-indigo-600 text-white shadow-lifted shadow-indigo-500/40">
                         <p className="text-[10px] font-black uppercase tracking-[0.2em] opacity-80 mb-2 flex items-center gap-2">
                             <TrendingUp className="w-3 h-3" /> Growth Factor
                         </p>
                         <p className="text-4xl font-display font-black">15.4%</p>
                         <p className="text-[10px] font-black uppercase tracking-widest mt-4 opacity-60">Strategic momentum detected in H1 cycle.</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
