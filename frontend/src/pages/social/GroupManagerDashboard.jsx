import { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { 
    Home, LayoutDashboard, Settings, Users, ShieldAlert, 
    ArrowLeft, Loader2, Save, Trash2, Check, X,
    Image as ImageIcon, Palette, Globe, Lock, ShieldCheck
} from 'lucide-react';
import { socialApi } from '../../api/social';
import { useAuth } from '../../context/AuthContext';
import MediaUrlInput from '../../components/social/MediaUrlInput';

export default function GroupManagerDashboard() {
    const { slug } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    
    const [group, setGroup] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('overview');
    const [isSaving, setIsSaving] = useState(false);
    
    // Form States
    const [formData, setFormData] = useState({
        description: '',
        category: '',
        cover_color: '#6366f1',
        is_private: false,
        post_approval_required: false,
        avatar_url: ''
    });

    useEffect(() => {
        fetchGroupDetail();
    }, [slug]);

    const fetchGroupDetail = async () => {
        setIsLoading(true);
        try {
            const res = await socialApi.getGroupDetail(slug);
            if (res.data?.user_role !== 'manager' && res.data?.user_role !== 'admin') {
                navigate('/social/groups');
                return;
            }
            setGroup(res.data);
            setFormData({
                description: res.data.description || '',
                category: res.data.category || '',
                cover_color: res.data.cover_color || '#6366f1',
                is_private: !!res.data.is_private,
                post_approval_required: !!res.data.post_approval_required,
                avatar_url: res.data.avatar_url || ''
            });
        } catch (err) {
            console.error('Failed to fetch group:', err);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSaveSettings = async () => {
        setIsSaving(true);
        try {
            await socialApi.updateGroupSettings(group.id, formData);
            alert('Settings updated successfully!');
            fetchGroupDetail();
        } catch (err) {
            alert(err.message || 'Update failed');
        } finally {
            setIsSaving(false);
        }
    };

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 className="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p className="text-slate-500 text-xs font-black uppercase tracking-widest">Loading Manager...</p>
            </div>
        );
    }

    return (
        <div className="space-y-12 animate-in fade-in duration-500">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div className="flex items-center gap-5">
                    <Link to={`/social/groups/${slug}`} className="p-3 bg-slate-100 dark:bg-white/5 rounded-2xl text-slate-500 hover:text-primary-500 transition-all hover:scale-110 active:scale-95">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <div className="flex items-center gap-3 mb-1">
                            <h1 className="text-3xl font-black dark:text-white tracking-tight leading-none">
                                {group.name}
                            </h1>
                            <span className="text-[10px] font-black text-primary-500 uppercase tracking-widest bg-primary-500/10 px-3 py-1 rounded-full border border-primary-500/20 shadow-sm">
                                Manager
                            </span>
                        </div>
                        <p className="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em]">Hub Control Center</p>
                    </div>
                </div>
                <div className="flex gap-3">
                     <button 
                        onClick={handleSaveSettings}
                        disabled={isSaving}
                        className="btn-primary px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-3 shadow-glow shadow-primary-500/20 disabled:opacity-50 transition-all hover:-translate-y-0.5"
                     >
                        {isSaving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                        Save Changes
                     </button>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-12">
                {/* Sidebar Nav */}
                <aside className="space-y-2">
                    {[
                        { id: 'overview', label: 'Overview', icon: LayoutDashboard },
                        { id: 'settings', label: 'Customizer', icon: Settings },
                        { id: 'moderation', label: 'Moderation', icon: ShieldAlert },
                    ].map(tab => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`w-full flex items-center gap-4 px-6 py-4 rounded-3xl text-[11px] font-black uppercase tracking-widest transition-all ${
                                activeTab === tab.id 
                                ? 'bg-primary-500 text-white shadow-xl shadow-primary-500/30 translate-x-2' 
                                : 'text-slate-500 hover:bg-white dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white border border-transparent hover:border-slate-200 dark:hover:border-white/5'
                            }`}
                        >
                            <tab.icon className="w-5 h-5" />
                            <span>{tab.label}</span>
                        </button>
                    ))}
                </aside>

                {/* Main Content */}
                <main>
                    {activeTab === 'overview' && (
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                            {[
                                { label: 'Total Members', value: group.member_count, color: 'primary' },
                                { label: 'Pending Posts', value: 0, color: 'amber' },
                                { label: 'New This Week', value: 0, color: 'emerald' }
                            ].map(stat => (
                                <div key={stat.label} className="card p-6 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] text-center group hover:border-primary-500/30 transition-all hover:shadow-2xl hover:shadow-primary-500/5">
                                    <p className={`text-4xl font-black dark:text-white mb-2 tracking-tighter transition-transform group-hover:scale-110`}>{stat.value}</p>
                                    <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">{stat.label}</p>
                                </div>
                            ))}
                        </div>
                    )}

                    {activeTab === 'settings' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-right-8 duration-500">
                            {/* Visual Branding */}
                            <div className="card p-6 md:p-8 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] space-y-8">
                                <div>
                                    <h3 className="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest mb-1">Visual Branding</h3>
                                    <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Customize how your hub looks to others</p>
                                </div>
                                
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
                                    <div className="space-y-4">
                                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Theme Color</label>
                                        <div className="flex items-center gap-6 p-4 bg-slate-50 dark:bg-white/5 rounded-3xl border border-slate-100 dark:border-slate-800">
                                            <input 
                                                type="color"
                                                value={formData.cover_color}
                                                onChange={(e) => setFormData({...formData, cover_color: e.target.value})}
                                                className="w-14 h-14 rounded-2xl p-0 border-none bg-transparent cursor-pointer overflow-hidden shadow-lg shadow-black/10"
                                            />
                                            <div>
                                                <p className="text-xs font-black dark:text-white uppercase font-mono">{formData.cover_color}</p>
                                                <p className="text-[10px] text-slate-500 font-bold">Primary accent color</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Hub Category</label>
                                        <input 
                                            type="text"
                                            value={formData.category}
                                            onChange={(e) => setFormData({...formData, category: e.target.value})}
                                            className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl px-6 py-4 text-sm font-bold dark:text-white focus:ring-primary-500 focus:bg-white dark:focus:bg-white/10 transition-all shadow-sm"
                                            placeholder="e.g. Engineering, Arts, Sports..."
                                        />
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Hub Avatar / Icon</label>
                                    <div className="flex items-center gap-8 p-6 bg-slate-50 dark:bg-white/5 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                                        <div className={`w-24 h-24 rounded-3xl flex items-center justify-center overflow-hidden border-4 border-white dark:border-slate-800 shadow-xl bg-gradient-to-br from-primary-500 to-indigo-600`}>
                                            {formData.avatar_url ? (
                                                <img src={formData.avatar_url} alt="Avatar Preview" className="w-full h-full object-cover" />
                                            ) : (
                                                <span className="text-3xl font-black text-white">{group.icon_initials || group.name[0]}</span>
                                            )}
                                        </div>
                                        <div className="flex-1 space-y-2">
                                            <p className="text-xs font-black dark:text-white uppercase tracking-tight">Image URL</p>
                                            <input 
                                                type="text"
                                                value={formData.avatar_url}
                                                onChange={(e) => setFormData({...formData, avatar_url: e.target.value})}
                                                className="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-slate-800 rounded-2xl px-6 py-3 text-sm dark:text-white focus:ring-primary-500 transition-all shadow-sm"
                                                placeholder="Paste an image link (e.g. from Google or Unsplash)..."
                                            />
                                            <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Supports PNG, JPG, WEBP</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Info & Content */}
                            <div className="card p-6 md:p-8 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] space-y-6">
                                <div>
                                    <h3 className="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest mb-1">Hub Description</h3>
                                    <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Tell the world what this hub is about</p>
                                </div>
                                <textarea 
                                    value={formData.description}
                                    onChange={(e) => setFormData({...formData, description: e.target.value})}
                                    className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-[2rem] p-8 text-sm font-medium dark:text-white focus:ring-primary-500 focus:bg-white dark:focus:bg-white/10 transition-all min-h-[160px] leading-relaxed shadow-sm"
                                    placeholder="Describe your hub's mission, rules, and vibe..."
                                />
                            </div>

                            {/* Privacy & Permissions */}
                            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                                <div className="card p-6 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] flex items-center justify-between gap-4 group hover:border-primary-500/20 transition-all">
                                    <div className="flex items-center gap-4 min-w-0">
                                        <div className="p-3 bg-primary-500/10 rounded-2xl text-primary-500 group-hover:scale-110 transition-transform shadow-sm shrink-0">
                                            <Lock className="w-5 h-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-black dark:text-white uppercase tracking-tight truncate">Private Community</p>
                                            <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest truncate">Members-only content</p>
                                        </div>
                                    </div>
                                    <button 
                                        onClick={() => setFormData({...formData, is_private: !formData.is_private})}
                                        className={`w-14 h-7 rounded-full transition-all relative ${formData.is_private ? 'bg-primary-500 shadow-glow shadow-primary-500/20' : 'bg-slate-200 dark:bg-slate-700'}`}
                                    >
                                        <div className={`absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-all shadow-md ${formData.is_private ? 'translate-x-7' : ''}`} />
                                    </button>
                                </div>

                                <div className="card p-6 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] flex items-center justify-between gap-4 group hover:border-amber-500/20 transition-all">
                                    <div className="flex items-center gap-4 min-w-0">
                                        <div className="p-3 bg-amber-500/10 rounded-2xl text-amber-500 group-hover:scale-110 transition-transform shadow-sm shrink-0">
                                            <ShieldCheck className="w-5 h-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-black dark:text-white uppercase tracking-tight truncate">Post Approval</p>
                                            <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest truncate">Moderate all new posts</p>
                                        </div>
                                    </div>
                                    <button 
                                        onClick={() => setFormData({...formData, post_approval_required: !formData.post_approval_required})}
                                        className={`w-14 h-7 rounded-full transition-all relative ${formData.post_approval_required ? 'bg-amber-500 shadow-glow shadow-amber-500/20' : 'bg-slate-200 dark:bg-slate-700'}`}
                                    >
                                        <div className={`absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-all shadow-md ${formData.post_approval_required ? 'translate-x-7' : ''}`} />
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'moderation' && (
                        <div className="text-center py-32 card border-dashed border-4 bg-slate-50/50 dark:bg-white/5 border-slate-200 dark:border-slate-800 rounded-[3rem] animate-in zoom-in duration-500">
                            <ShieldAlert className="w-20 h-20 text-slate-300 dark:text-slate-700 mx-auto mb-6" />
                            <h3 className="text-2xl font-black dark:text-white mb-2 tracking-tight">Moderation Queue</h3>
                            <p className="text-xs text-slate-500 font-bold uppercase tracking-widest">You're all caught up! No pending items.</p>
                        </div>
                    )}
                </main>
            </div>
        </div>
    );
}
