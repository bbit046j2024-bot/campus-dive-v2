import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { 
    Calendar, MapPin, Link as LinkIcon, 
    MessageSquare, Users, Edit3, Grid, List, Plus,
    Loader2
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { UserAvatar } from '../../components/ui/StatusBadge';
import PostCard from '../../components/social/PostCard';
import { socialApi } from '../../api/social';

export default function SocialProfilePage() {
    const { id } = useParams();
    const { user: authUser } = useAuth();
    const [profile, setProfile] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [viewMode, setViewMode] = useState('grid');

    const effectiveId = id || authUser?.id;
    const isOwnProfile = !id || id == authUser?.id;

    useEffect(() => {
        if (effectiveId) {
            fetchProfileData();
        }
    }, [id, authUser?.id]);

    const fetchProfileData = async () => {
        setIsLoading(true);
        try {
            const res = await socialApi.getProfile(effectiveId);
            setProfile(res.data);
        } catch (err) {
            console.error('Failed to fetch profile:', err);
        } finally {
            setIsLoading(false);
        }
    };

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 className="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p className="text-slate-500 text-xs font-black uppercase tracking-widest">Loading Profile...</p>
            </div>
        );
    }

    if (!profile) {
        return (
            <div className="text-center py-20">
                <h2 className="text-xl font-bold dark:text-white">Profile not found</h2>
                <Link to="/social" className="text-primary-500 hover:underline mt-4 block">Back to Feed</Link>
            </div>
        );
    }

    const fullName = `${profile.firstname} ${profile.lastname}`;
    const joinedYear = new Date(profile.created_at).getFullYear();

    return (
        <div className="max-w-4xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pt-4">
            {/* Profile Header */}
            <div className="card border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] p-8 overflow-hidden relative">
                <div className="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-primary-500 to-purple-600" />
                
                <div className="flex flex-col md:flex-row items-center md:items-start gap-8 relative z-10">
                    <div className="relative group">
                        <div className="w-32 h-32 rounded-3xl overflow-hidden border-4 border-white dark:border-slate-900 shadow-2xl relative">
                            <UserAvatar user={profile} size="full" />
                        </div>
                        {isOwnProfile && (
                            <button className="absolute -bottom-2 -right-2 p-2.5 bg-primary-500 text-white rounded-xl shadow-glow hover:scale-110 transition-all">
                                <Plus className="w-5 h-5" />
                            </button>
                        )}
                    </div>
                    
                    <div className="flex-1 text-center md:text-left pt-2">
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                            <div>
                                <h1 className="text-3xl font-black dark:text-white tracking-tight leading-tight">{fullName}</h1>
                                <p className="text-xs font-black text-primary-500 uppercase tracking-[0.2em] mt-1">{profile.role || 'Student'}</p>
                            </div>
                            <div className="flex items-center gap-2 justify-center">
                                {isOwnProfile ? (
                                    <button className="px-6 py-2.5 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 hover:text-primary-500 transition-all flex items-center gap-2">
                                        <Edit3 className="w-4 h-4" />
                                        Edit Profile
                                    </button>
                                ) : (
                                    <button className="px-8 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-glow shadow-primary-500/20 transition-all hover:-translate-y-1">
                                        Follow
                                    </button>
                                )}
                            </div>
                        </div>

                        <p className="text-sm font-semibold text-slate-600 dark:text-slate-400 leading-relaxed mb-6 max-w-2xl">
                            {profile.bio || 'This user hasn\'t added a bio yet.'}
                        </p>

                        <div className="flex flex-wrap items-center justify-center md:justify-start gap-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            <div className="flex items-center gap-2">
                                <MapPin className="w-3.5 h-3.5 text-primary-500" /> 
                                {profile.location || 'Mombasa, KE'}
                            </div>
                            <div className="flex items-center gap-2">
                                <Calendar className="w-3.5 h-3.5 text-emerald-500" /> 
                                Joined {joinedYear}
                            </div>
                            <div className="flex items-center gap-2">
                                <LinkIcon className="w-3.5 h-3.5 text-indigo-500" /> 
                                campus-dive.io
                            </div>
                        </div>
                    </div>
                </div>

                {/* Stats Bar */}
                <div className="grid grid-cols-3 gap-4 mt-10 pt-10 border-t border-slate-50 dark:border-white/5">
                    <div className="text-center">
                        <p className="text-2xl font-black dark:text-white">{profile.post_count}</p>
                        <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Total Posts</p>
                    </div>
                    <div className="text-center border-x border-slate-50 dark:border-white/5 px-4">
                        <p className="text-2xl font-black dark:text-white">{profile.group_count}</p>
                        <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Group Hubs</p>
                    </div>
                    <div className="text-center">
                        <p className="text-2xl font-black text-emerald-500">
                            {profile.post_count > 10 ? 'Pro' : profile.post_count > 0 ? 'Member' : 'Newcomer'}
                        </p>
                        <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Contributor</p>
                    </div>
                </div>
            </div>

            {/* Content Filters */}
            <div className="flex items-center justify-between px-2">
                <div className="flex items-center gap-6">
                    <button className="text-[10px] font-black uppercase tracking-[0.2em] text-primary-500 border-b-2 border-primary-500 pb-2">Feed Posts</button>
                    <button className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 pb-2 transition-colors">Shared Media</button>
                    <button className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 pb-2 transition-colors">Joined Groups</button>
                </div>
                <div className="flex items-center gap-2 bg-slate-100 dark:bg-white/5 p-1 rounded-lg border border-slate-200 dark:border-slate-800">
                    <button 
                        onClick={() => setViewMode('grid')}
                        className={`p-1.5 rounded-md transition-all ${viewMode === 'grid' ? 'bg-white dark:bg-surface-800 text-primary-500 shadow-sm' : 'text-slate-400'}`}
                    >
                        <Grid className="w-4 h-4" />
                    </button>
                    <button 
                        onClick={() => setViewMode('list')}
                        className={`p-1.5 rounded-md transition-all ${viewMode === 'list' ? 'bg-white dark:bg-surface-800 text-primary-500 shadow-sm' : 'text-slate-400'}`}
                    >
                        <List className="w-4 h-4" />
                    </button>
                </div>
            </div>

            {/* Content View */}
            <div className={viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-6'}>
                {profile.posts && profile.posts.length > 0 ? (
                    profile.posts.map(post => <PostCard key={post.id} post={post} />)
                ) : (
                    <div className="md:col-span-2 py-20 text-center bg-slate-50 dark:bg-white/5 rounded-3xl border border-dashed border-slate-200 dark:border-white/10">
                        <MessageSquare className="w-12 h-12 text-slate-300 mx-auto mb-4" />
                        <p className="text-slate-500 text-xs font-black uppercase tracking-widest">No posts yet</p>
                    </div>
                )}
            </div>
        </div>
    );
}
