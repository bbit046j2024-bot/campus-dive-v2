import { useState, useEffect } from 'react';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { 
    Send, Users, User, Mail, ShieldAlert, 
    CheckCircle2, Loader2, Sparkles, Megaphone
} from 'lucide-react';

export default function BroadcastPage() {
    const [target, setTarget] = useState('all');
    const [subject, setSubject] = useState('');
    const [message, setMessage] = useState('');
    const [loading, setLoading] = useState(false);
    const [students, setStudents] = useState([]);
    const [selectedStudents, setSelectedStudents] = useState([]);
    const [search, setSearch] = useState('');
    const toast = useToast();

    useEffect(() => {
        if (target === 'selected') {
            fetchStudents();
        }
    }, [target]);

    const fetchStudents = async () => {
        try {
            const res = await api.get('/admin/users');
            setStudents(res.data || []);
        } catch (err) {
            toast.error('Failed to load user directory');
        }
    };

    const handleSend = async (e) => {
        e.preventDefault();
        if (!subject || !message) return toast.error('Subject and message required');
        
        setLoading(true);
        try {
            const payload = {
                subject,
                message,
                target: target === 'selected' ? selectedStudents : target
            };
            
            const res = await api.post('/admin/system/broadcast', payload);
            toast.success(res.message || 'Broadcast successful!');
            
            setSubject('');
            setMessage('');
            setSelectedStudents([]);
        } catch (err) {
            toast.error(err.message || 'Transmission failure');
        } finally {
            setLoading(false);
        }
    };

    const toggleStudent = (id) => {
        setSelectedStudents(prev => 
            prev.includes(id) ? prev.filter(s => s !== id) : [...prev, id]
        );
    };

    const filteredStudents = students.filter(s => 
        `${s.firstname} ${s.lastname} ${s.email}`.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="max-w-6xl mx-auto space-y-10 animate-fade-in pb-20">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 className="text-4xl font-black tracking-tight text-indigo-950 dark:text-white flex items-center gap-4">
                        Broadcast <span className="text-indigo-600 font-display">Terminal</span>
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-medium uppercase text-[10px] tracking-[0.3em] opacity-70">Execute mass communication protocols across the network</p>
                </div>
                
                <div className="flex items-center gap-3 p-1 bg-surface-100 dark:bg-white/5 rounded-2xl border border-surface-200 dark:border-white/5">
                    {[
                        { id: 'all', label: 'Network Wide', icon: Users },
                        { id: 'students', label: 'Students Only', icon: Sparkles },
                        { id: 'selected', label: 'Targeted', icon: User }
                    ].map(opt => (
                        <button
                            key={opt.id}
                            type="button"
                            onClick={() => setTarget(opt.id)}
                            className={`flex items-center gap-2 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${
                                target === opt.id 
                                ? 'bg-indigo-600 text-white shadow-glow-indigo' 
                                : 'text-surface-500 hover:text-indigo-600'
                            }`}
                        >
                            <opt.icon className="w-3.5 h-3.5" />
                            {opt.label}
                        </button>
                    ))}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div className="lg:col-span-2 space-y-8">
                    <div className="card-premium p-8 lg:p-10">
                        <form onSubmit={handleSend} className="space-y-8">
                            <div className="space-y-4">
                                <label className="block text-[10px] font-black uppercase tracking-[0.2em] text-surface-400 ml-1">Transmission Subject</label>
                                <div className="relative group">
                                    <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-300 group-focus-within:text-indigo-600 transition-colors" />
                                    <input 
                                        type="text" 
                                        value={subject}
                                        onChange={(e) => setSubject(e.target.value)}
                                        placeholder="Enter the broadcast subject..."
                                        className="w-full bg-surface-50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-16 pr-8 py-5 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 transition-all outline-none"
                                    />
                                </div>
                            </div>

                            <div className="space-y-4">
                                <label className="block text-[10px] font-black uppercase tracking-[0.2em] text-surface-400 ml-1">Message Protocol (HTML Supported)</label>
                                <textarea 
                                    value={message}
                                    onChange={(e) => setMessage(e.target.value)}
                                    placeholder="Draft your high-priority transmission here..."
                                    rows={10}
                                    className="w-full bg-surface-50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-3xl p-8 text-sm font-medium leading-relaxed focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 transition-all outline-none custom-scrollbar"
                                />
                            </div>

                            <div className="flex items-center justify-between pt-4 border-t border-surface-100 dark:border-white/5">
                                <div className="flex items-center gap-3 text-amber-500">
                                    <ShieldAlert className="w-5 h-5" />
                                    <span className="text-[10px] font-black uppercase tracking-widest">Authorized use only</span>
                                </div>
                                
                                <button 
                                    type="submit"
                                    disabled={loading || (target === 'selected' && selectedStudents.length === 0)}
                                    className="btn-v2-primary px-12 py-5 text-xs font-black uppercase tracking-[0.2em] flex items-center gap-3 shadow-glow-indigo disabled:opacity-50 disabled:shadow-none"
                                >
                                    {loading ? (
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                    ) : (
                                        <><Send className="w-4 h-4" /> EXECUTE BROADCAST</>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div className="space-y-8">
                    {target === 'selected' ? (
                        <div className="card-premium h-[750px] flex flex-col p-0 overflow-hidden">
                            <div className="p-8 border-b border-surface-100 dark:border-white/5">
                                <h3 className="font-display font-black text-lg text-indigo-950 dark:text-white mb-4 uppercase tracking-tight">Select Recipients</h3>
                                <input 
                                    type="text" 
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Search directory..."
                                    className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-xl px-5 py-3 text-[11px] font-bold outline-none focus:border-indigo-600 transition-all"
                                />
                            </div>
                            <div className="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                                {filteredStudents.map(s => (
                                    <div 
                                        key={s.id}
                                        onClick={() => toggleStudent(s.id)}
                                        className={`p-4 rounded-2xl cursor-pointer flex items-center justify-between transition-all border ${
                                            selectedStudents.includes(s.id)
                                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-glow-indigo/20'
                                            : 'bg-surface-50 dark:bg-white/5 border-transparent hover:border-indigo-600/30'
                                        }`}
                                    >
                                        <div className="min-w-0">
                                            <p className="text-[11px] font-black uppercase tracking-tight truncate">{s.firstname} {s.lastname}</p>
                                            <p className={`text-[9px] font-medium opacity-60 truncate ${selectedStudents.includes(s.id) ? 'text-white' : 'text-surface-500'}`}>{s.email}</p>
                                        </div>
                                        {selectedStudents.includes(s.id) && <CheckCircle2 className="w-4 h-4 shrink-0" />}
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : (
                        <div className="card-premium p-10 bg-indigo-950 text-white relative overflow-hidden group">
                            <div className="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform">
                                <Megaphone className="w-40 h-40" />
                            </div>
                            <h3 className="font-display font-black text-2xl mb-6 relative z-10">Broadcast <br/>Protocol</h3>
                            <ul className="space-y-6 relative z-10">
                                {[
                                    'Transmissions are sent via premium SMTP logic.',
                                    'High-priority HTML templates are auto-applied.',
                                    'Recipients see professional corporate styling.',
                                    'All activity is logged for security audits.'
                                ].map((txt, i) => (
                                    <li key={i} className="flex gap-4 text-[11px] font-bold text-indigo-200/70 leading-relaxed uppercase tracking-wide">
                                        <div className="w-1.5 h-1.5 rounded-full bg-indigo-500 mt-1 flex-shrink-0" />
                                        {txt}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
",Description:
