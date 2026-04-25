import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { StatusBadge, UserAvatar } from '../../components/ui/StatusBadge';
import { SkeletonStats, SkeletonCard } from '../../components/ui/Skeleton';
import { FileText, MessageSquare, Bell, Clock, CheckCircle, ArrowRight, Upload, X, Shield } from 'lucide-react';
import NotificationDropdown from '../../components/social/NotificationDropdown';

const STEPS = [
    { key: 'submitted', label: 'Submitted', icon: CheckCircle },
    { key: 'documents_uploaded', label: 'Documents', icon: Upload },
    { key: 'under_review', label: 'Review', icon: Clock },
    { key: 'interview_scheduled', label: 'Interview', icon: MessageSquare },
    { key: 'approved', label: 'Approved', icon: CheckCircle },
];

function ProgressTracker({ status }) {
    const currentIdx = STEPS.findIndex(s => s.key === status);
    const rejected = status === 'rejected';

    return (
        <div className="card-premium p-8 lg:p-10">
            <h3 className="font-display font-black text-xl mb-10 text-surface-900 dark:text-white flex items-center gap-3">
                <div className="w-2 h-8 bg-indigo-600 rounded-full" />
                Application Pipeline
            </h3>
            {rejected ? (
                <div className="flex items-center gap-5 p-6 rounded-3xl bg-rose-50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/30 animate-shake">
                    <div className="w-14 h-14 rounded-2xl bg-rose-500 text-white flex items-center justify-center shadow-lg shadow-rose-500/20">
                        <X className="w-8 h-8" />
                    </div>
                    <div>
                        <p className="font-black text-rose-700 dark:text-rose-400 text-lg">Application Withdrawn / Rejected</p>
                        <p className="text-sm text-rose-600/70 dark:text-rose-400/70 font-medium">Please review the feedback in your messages or contact support.</p>
                    </div>
                </div>
            ) : (
                <div className="relative flex items-center justify-between px-2 overflow-x-auto pb-4">
                    {/* Background Line */}
                    <div className="absolute top-7 left-10 right-10 h-1 bg-surface-100 dark:bg-white/5 rounded-full" />
                    
                    {STEPS.map((step, i) => {
                        const done = i <= currentIdx;
                        const active = i === currentIdx;
                        return (
                            <div key={step.key} className="relative z-10 flex flex-col items-center min-w-[80px]">
                                <div className={`w-14 h-14 rounded-2xl flex items-center justify-center transition-all duration-500 
                                    ${done ? 'bg-indigo-600 text-white shadow-lifted shadow-indigo-500/30' : 'bg-white dark:bg-surface-900 text-surface-300 border border-surface-100 dark:border-white/5'}
                                    ${active ? 'ring-4 ring-indigo-500/20 scale-110' : ''}
                                `}>
                                    <step.icon className={`w-6 h-6 ${active ? 'animate-pulse' : ''}`} />
                                </div>
                                <div className="mt-4 text-center">
                                    <span className={`text-[10px] font-black uppercase tracking-tighter transition-colors duration-300 ${done ? 'text-indigo-600 dark:text-indigo-400' : 'text-surface-400'}`}>
                                        {step.label}
                                    </span>
                                </div>
                                
                                {/* Connecting Line Highlight */}
                                {i < currentIdx && (
                                    <div className="absolute top-7 left-[3.5rem] w-[calc(100%+1rem)] h-1 bg-indigo-600 rounded-full z-0" />
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

export default function StudentDashboard() {
    const { user } = useAuth();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [showNotifications, setShowNotifications] = useState(false);

    useEffect(() => {
        api.get('/student/dashboard').then(res => {
            setData(res.data);
            setLoading(false);
        }).catch(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="space-y-6 animate-fade-in">
                <SkeletonStats count={3} />
                <SkeletonCard />
                <SkeletonCard />
            </div>
        );
    }

    return (
        <div className="space-y-10 animate-fade-in pb-20">
            {/* Welcome Header */}
            <div className="flex items-center justify-between animate-stagger relative z-[100]" style={{ animationDelay: '0ms' }}>
                <div>
                    <h1 className="text-3xl md:text-4xl font-black tracking-tight text-indigo-950 dark:text-white">
                        Welcome back, <span className="text-indigo-600 font-display">{user?.firstname}!</span> 👋
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-medium">Here's a curated look at your recruitment progress</p>
                </div>
                <div className="relative z-50">
                    <button 
                        onClick={() => setShowNotifications(true)}
                        className="w-14 h-14 rounded-3xl bg-white dark:bg-surface-900 border border-surface-200 dark:border-surface-800 flex items-center justify-center text-surface-400 hover:text-indigo-600 hover:border-indigo-200 hover:shadow-glow transition-all relative shadow-soft"
                    >
                        <Bell className="w-6 h-6" />
                        {data?.unread_notifications > 0 && (
                            <span className="absolute top-4 right-4 w-3 h-3 bg-red-500 border-2 border-white dark:border-surface-900 rounded-full" />
                        )}
                    </button>
                    {showNotifications && (
                        <NotificationDropdown 
                            notifications={data?.notifications || []} 
                            unreadCount={data?.unread_notifications || 0}
                            onClose={() => setShowNotifications(false)} 
                        />
                    )}
                </div>
            </div>

            {/* Progress Tracker Layer */}
            <div className="animate-stagger" style={{ animationDelay: '100ms' }}>
                <ProgressTracker status={data?.application_status || 'submitted'} />
            </div>

            {/* Content Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Main Content (Left) */}
                <div className="lg:col-span-2 space-y-8">
                    <div className="card-premium animate-stagger" style={{ animationDelay: '200ms' }}>
                        <div className="flex items-center justify-between p-8 border-b border-surface-100 dark:border-white/5">
                            <h3 className="font-display font-black text-xl text-surface-900 dark:text-white flex items-center gap-2">
                                <FileText className="w-6 h-6 text-indigo-600" />
                                Recent Documents
                            </h3>
                            <Link to="/documents" className="btn-v2-secondary !px-4 !py-2 text-xs font-bold group">
                                VIEW ALL <ArrowRight className="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" />
                            </Link>
                        </div>
                        <div className="divide-y divide-surface-100 dark:divide-white/5">
                            {data?.documents?.length > 0 ? data.documents.slice(0, 5).map(doc => (
                                <div key={doc.id} className="flex items-center gap-5 p-6 hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition-all group">
                                    <div className="w-12 h-12 rounded-2xl bg-surface-100 dark:bg-white/5 flex items-center justify-center group-hover:bg-indigo-100 dark:group-hover:bg-indigo-500/20 transition-colors">
                                        <FileText className="w-6 h-6 text-surface-400 group-hover:text-indigo-600 transition-colors" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-base font-bold truncate text-surface-800 dark:text-surface-100">{doc.document_name || doc.original_name}</p>
                                        <p className="text-xs text-surface-500 font-medium">Uploaded {new Date(doc.uploaded_at).toLocaleDateString()}</p>
                                    </div>
                                    <StatusBadge status={doc.status || 'pending'} />
                                </div>
                            )) : (
                                <div className="p-16 text-center">
                                    <div className="w-20 h-20 bg-surface-50 dark:bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 border border-dashed border-surface-200 dark:border-surface-800">
                                        <Upload className="w-8 h-8 text-surface-300" />
                                    </div>
                                    <p className="text-surface-500 font-medium font-display">No documents uploaded yet</p>
                                    <Link to="/documents" className="btn-v2-primary mt-6 !py-3">UPLOAD RESUME</Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Sidebar (Right) - Quick Stats/Actions */}
                <div className="space-y-8 animate-stagger" style={{ animationDelay: '300ms' }}>
                    <div className="card-premium p-8 bg-indigo-600 text-white border-0 shadow-lifted shadow-indigo-500/40 relative overflow-hidden group">
                        <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                            <Shield className="w-32 h-32" />
                        </div>
                        <h4 className="text-sm font-black uppercase tracking-widest opacity-80 decoration-indigo-300 decoration-2 underline-offset-4">Active Application</h4>
                        <p className="text-3xl font-black mt-4 font-display">Verified Student</p>
                        <div className="mt-8 flex items-center gap-2 text-sm font-medium bg-white/10 p-3 rounded-2xl backdrop-blur-sm">
                            <CheckCircle className="w-4 h-4" />
                            Identity successfully verified
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
