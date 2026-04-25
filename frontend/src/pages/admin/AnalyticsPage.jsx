import { useState, useEffect } from 'react';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { BarChart3, TrendingUp, Users, CheckCircle, XCircle, Clock } from 'lucide-react';

export default function AnalyticsPage() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
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

    if (loading) return (
        <div className="flex items-center justify-center min-h-[400px]">
            <div className="w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full animate-spin" />
        </div>
    );

    const stats = [
        { label: 'Total Students', value: data?.stats?.total_students || 0, icon: Users, color: 'text-primary-500', bg: 'bg-primary-50 dark:bg-primary-900/10' },
        { label: 'Approved', value: data?.stats?.approved || 0, icon: CheckCircle, color: 'text-green-500', bg: 'bg-green-50 dark:bg-green-900/10' },
        { label: 'Pending', value: data?.stats?.pending || 0, icon: Clock, color: 'text-yellow-500', bg: 'bg-yellow-50 dark:bg-yellow-900/10' },
        { label: 'Rejected', value: data?.stats?.rejected || 0, icon: XCircle, color: 'text-red-500', bg: 'bg-red-50 dark:bg-red-900/10' },
    ];

    return (
        <div className="animate-fade-in space-y-10 pb-20">
            <div className="flex items-center justify-between animate-stagger" style={{ animationDelay: '0ms' }}>
                <div>
                    <h1 className="text-3xl md:text-4xl font-black tracking-tight text-indigo-950 dark:text-white flex items-center gap-4">
                        Intelligence <span className="text-indigo-600 font-display">& Assets</span>
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-medium">Real-time performance metrics and predictive deployment trends</p>
                </div>
            </div>

            {/* Stats Grid */}
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

            {/* Trends Chart (Simplified Visualization) */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 card-premium p-8 lg:p-10 animate-stagger" style={{ animationDelay: '500ms' }}>
                    <div className="flex items-center justify-between mb-12">
                        <h3 className="font-display font-black text-xl text-surface-900 dark:text-white flex items-center gap-3">
                            <div className="w-2 h-6 bg-indigo-600 rounded-full" />
                            Engagement Velocity
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

                <div className="card-premium p-8 lg:p-10 animate-stagger" style={{ animationDelay: '600ms' }}>
                    <h3 className="font-display font-black text-xl mb-8 text-surface-900 dark:text-white">Quick Breakdown</h3>
                    <div className="space-y-8">
                        {data?.stats && Object.entries(data.stats).filter(([key]) => key !== 'total_students').map(([key, value], i) => {
                            const percentage = (value / data.stats.total_students) * 100 || 0;
                            const colors = {
                                approved: 'bg-emerald-500 shadow-emerald-500/20',
                                rejected: 'bg-rose-500 shadow-rose-500/20',
                                default: 'bg-indigo-500 shadow-indigo-500/20'
                            };
                            const color = colors[key] || colors.default;

                            return (
                                <div key={i} className="space-y-3">
                                    <div className="flex justify-between text-[10px] font-black uppercase tracking-widest text-surface-500">
                                        <span className="opacity-60">{key.replace('_', ' ')}</span>
                                        <span className={key === 'approved' ? 'text-emerald-600' : key === 'rejected' ? 'text-rose-600' : 'text-indigo-600'}>
                                            {Math.round(percentage)}%
                                        </span>
                                    </div>
                                    <div className="h-2.5 w-full bg-surface-100 dark:bg-white/5 rounded-full overflow-hidden p-0.5">
                                        <div
                                            className={`h-full rounded-full transition-all duration-1000 ${color}`}
                                            style={{ width: `${percentage}%` }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <div className="mt-12 p-6 rounded-3xl bg-indigo-600 text-white shadow-lifted shadow-indigo-500/40 relative overflow-hidden group">
                        <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                             <TrendingUp className="w-20 h-20" />
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Primary Deployment</p>
                        <p className="text-xl font-display font-black mt-1">Mombasa Campus</p>
                        <div className="mt-6 flex items-center gap-2 text-[10px] font-black bg-white/10 p-2.5 rounded-xl backdrop-blur-sm w-fit">
                            <TrendingUp className="w-3 h-3 text-emerald-400" />
                            <span className="uppercase tracking-widest">15% Monthly Growth</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
