import { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import api from '../../api/client';
import { UserAvatar } from '../../components/ui/StatusBadge';
import { User, Lock, Camera, Bell, Trash2, Save } from 'lucide-react';

export default function SettingsPage() {
    const { user, updateUser } = useAuth();
    const toast = useToast();
    const [activeTab, setActiveTab] = useState('profile');
    const [loading, setLoading] = useState(false);

    const [profile, setProfile] = useState({
        firstname: user?.firstname || '',
        lastname: user?.lastname || '',
        phone: user?.phone || '',
        bio: user?.bio || '',
        location: user?.location || '',
    });
    const [passwords, setPasswords] = useState({
        current_password: '', new_password: '', confirm_password: '',
    });

    const tabs = [
        { key: 'profile', label: 'Profile', icon: User },
        { key: 'security', label: 'Security', icon: Lock },
    ];

    const handleProfileUpdate = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            const res = await api.put('/student/profile', profile);
            updateUser(res.data);
            toast.success('Profile updated!');
        } catch (err) {
            toast.error(err.errors ? Object.values(err.errors)[0] : err.message);
        } finally {
            setLoading(false);
        }
    };

    const handlePasswordChange = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            await api.put('/student/password', passwords);
            toast.success('Password changed!');
            setPasswords({ current_password: '', new_password: '', confirm_password: '' });
        } catch (err) {
            toast.error(err.errors ? Object.values(err.errors)[0] : err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleAvatarUpload = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('avatar', file);
        try {
            const res = await api.upload('/student/avatar', formData);
            updateUser({ avatar_image: res.data.avatar_path });
            toast.success('Avatar updated!');
        } catch (err) {
            toast.error(err.message);
        }
    };

    return (
        <div className="space-y-10 pb-20 animate-fade-in">
            <div className="flex items-center justify-between mb-8 animate-stagger" style={{ animationDelay: '0ms' }}>
                <div>
                    <h1 className="text-3xl md:text-4xl font-black tracking-tight text-indigo-950 dark:text-white flex items-center gap-4 uppercase">
                        Asset <span className="text-indigo-600 font-display">Control</span>
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-bold uppercase tracking-widest text-[10px] opacity-60">Manage your digital identity and security protocols</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                {/* Sidebar Tabs */}
                <div className="lg:col-span-1 animate-stagger" style={{ animationDelay: '200ms' }}>
                    <div className="card-premium p-3 space-y-2">
                        {tabs.map(tab => (
                            <button
                                key={tab.key}
                                onClick={() => setActiveTab(tab.key)}
                                className={`w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all
                                    ${activeTab === tab.key
                                        ? 'bg-indigo-600 text-white shadow-glow-indigo'
                                        : 'text-surface-600 dark:text-surface-400 hover:bg-surface-50 dark:hover:bg-white/5'
                                    }`}
                            >
                                <tab.icon className="w-4 h-4" /> {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Content */}
                <div className="lg:col-span-3 animate-stagger" style={{ animationDelay: '400ms' }}>
                    {activeTab === 'profile' && (
                        <div className="card-premium p-8 lg:p-10 space-y-10">
                            <div className="flex items-center gap-4 mb-2">
                                <div className="w-2 h-6 bg-indigo-600 rounded-full" />
                                <h3 className="text-xl font-black text-indigo-950 dark:text-white uppercase tracking-tight">Identity Details</h3>
                            </div>

                            {/* Avatar Section */}
                            <div className="flex flex-col sm:flex-row items-center gap-8 p-8 rounded-3xl bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/5">
                                <div className="relative group">
                                    <div className="absolute inset-0 bg-indigo-500 blur-2xl opacity-0 group-hover:opacity-20 transition-opacity" />
                                    <div className="relative w-28 h-28 rounded-3xl overflow-hidden border-4 border-white dark:border-indigo-900 shadow-xl group-hover:scale-105 transition-transform duration-500">
                                        <UserAvatar user={user} size="full" />
                                    </div>
                                    <label className="absolute -bottom-2 -right-2 w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center cursor-pointer hover:bg-indigo-700 transition-all shadow-glow-indigo active:scale-95">
                                        <Camera className="w-5 h-5" />
                                        <input type="file" accept="image/*" onChange={handleAvatarUpload} className="hidden" />
                                    </label>
                                </div>
                                <div className="text-center sm:text-left">
                                    <p className="text-2xl font-black text-indigo-950 dark:text-white uppercase tracking-tight">{user?.firstname} {user?.lastname}</p>
                                    <p className="text-sm font-bold text-surface-500 mt-1 dark:text-surface-400 uppercase tracking-widest">{user?.email}</p>
                                    <div className="inline-block mt-4 px-4 py-1.5 rounded-full bg-indigo-600/10 text-indigo-600 text-[9px] font-black uppercase tracking-[0.2em] border border-indigo-600/20">
                                        {user?.role_name || user?.role || 'Authorized Asset'}
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={handleProfileUpdate} className="space-y-8">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                    <div className="group">
                                        <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">First Reference</label>
                                        <input type="text" value={profile.firstname} onChange={e => setProfile(p => ({ ...p, firstname: e.target.value }))} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required />
                                    </div>
                                    <div className="group">
                                        <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Family Reference</label>
                                        <input type="text" value={profile.lastname} onChange={e => setProfile(p => ({ ...p, lastname: e.target.value }))} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                    <div className="group">
                                        <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Telecom Identifier</label>
                                        <input type="tel" value={profile.phone} onChange={e => setProfile(p => ({ ...p, phone: e.target.value }))} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required />
                                    </div>
                                    <div className="group">
                                        <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Deployment Location</label>
                                        <input type="text" value={profile.location} onChange={e => setProfile(p => ({ ...p, location: e.target.value }))} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" placeholder="e.g. Mombasa, KE" />
                                    </div>
                                </div>
                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Professional Narrative (Bio)</label>
                                    <textarea 
                                        value={profile.bio} 
                                        onChange={e => setProfile(p => ({ ...p, bio: e.target.value }))} 
                                        className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none min-h-[150px] resize-none" 
                                        placeholder="Summary of skillsets and industrial objectives..."
                                    />
                                </div>
                                <div className="flex justify-end pt-4">
                                    <button type="submit" disabled={loading} className="btn-v2-primary py-5 px-10 text-[10px] font-black uppercase tracking-[0.2em] shadow-glow-indigo flex items-center justify-center gap-3">
                                        {loading ? <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> : <Save className="w-4 h-4" />}
                                        SYNCHRONIZE PROFILE
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {activeTab === 'security' && (
                        <div className="card-premium p-8 lg:p-10 space-y-10">
                             <div className="flex items-center gap-4 mb-2">
                                <div className="w-2 h-6 bg-indigo-600 rounded-full" />
                                <h3 className="text-xl font-black text-indigo-950 dark:text-white uppercase tracking-tight">Security Protocol</h3>
                            </div>

                            <form onSubmit={handlePasswordChange} className="space-y-8 max-w-xl">
                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Current Passcode</label>
                                    <input type="password" value={passwords.current_password} onChange={e => setPasswords(p => ({ ...p, current_password: e.target.value }))} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required />
                                </div>
                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Target Passcode</label>
                                    <input type="password" value={passwords.new_password} onChange={e => setPasswords(p => ({ ...p, new_password: e.target.value }))} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required minLength={6} />
                                </div>
                                <div className="group">
                                    <label className="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2.5 ml-1 transition-colors group-focus-within:text-indigo-600">Verify Target Passcode</label>
                                    <input type="password" value={passwords.confirm_password} onChange={e => setPasswords(p => ({ ...p, confirm_password: e.target.value }))} className="w-full bg-surface-100/50 dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none" required />
                                </div>
                                <div className="flex justify-start pt-4">
                                    <button type="submit" disabled={loading} className="btn-v2-primary py-5 px-10 text-[10px] font-black uppercase tracking-[0.2em] shadow-glow-indigo flex items-center justify-center gap-3">
                                        {loading ? <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> : <Lock className="w-4 h-4" />}
                                        UPDATE SECURITY
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
