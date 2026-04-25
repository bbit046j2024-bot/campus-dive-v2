import { useState, useEffect, useCallback } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { 
    Users, MessageSquare, Info, ShieldCheck, Plus, 
    Link as LinkIcon, Calendar, MoreHorizontal, UserPlus, Loader2, AlertCircle
} from 'lucide-react';
import PostCard from '../../components/social/PostCard';
import { socialApi } from '../../api/social';
import { useAuth } from '../../context/AuthContext';
import MediaUrlInput from '../../components/social/MediaUrlInput';

export default function GroupProfilePage() {
    const { slug } = useParams();
    const { user } = useAuth();
    const navigate = useNavigate();
    
    const [group, setGroup] = useState(null);
    const [posts, setPosts] = useState([]);
    const [members, setMembers] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('posts');
    const [isJoining, setIsJoining] = useState(false);
    
    // New Post State
    const [showPostBox, setShowPostBox] = useState(false);
    const [postContent, setPostContent] = useState('');
    const [postMedia, setPostMedia] = useState(null);
    const [showMediaInput, setShowMediaInput] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const fetchGroupData = useCallback(async () => {
        setIsLoading(true);
        try {
            const res = await socialApi.getGroupDetail(slug);
            setGroup(res.data);
            
            // If group found, fetch posts and members
            if (res.data) {
                const [postsRes, membersRes] = await Promise.all([
                    socialApi.getGroupPosts(res.data.id),
                    socialApi.getGroupMembers(res.data.id)
                ]);
                setPosts(postsRes.data || []);
                setMembers(membersRes.data || []);
            }
        } catch (err) {
            console.error('Failed to fetch group:', err);
        } finally {
            setIsLoading(false);
        }
    }, [slug]);

    useEffect(() => {
        fetchGroupData();
    }, [fetchGroupData]);

    const handleJoinLeave = async () => {
        setIsJoining(true);
        try {
            if (group.user_role) {
                if (window.confirm('Are you sure you want to leave this group?')) {
                    await socialApi.leaveGroup(group.id);
                    fetchGroupData();
                }
            } else {
                await socialApi.joinGroup(group.id);
                fetchGroupData();
            }
        } catch (err) {
            alert(err.message || 'Action failed');
        } finally {
            setIsJoining(false);
        }
    };

    const handleCreatePost = async () => {
        if (!postContent.trim()) return;
        setIsSubmitting(true);
        try {
            await socialApi.createPost({
                group_id: group.id,
                content: postContent,
                media_url: postMedia?.url || null
            });
            setPostContent('');
            setPostMedia(null);
            setShowMediaInput(false);
            setShowPostBox(false);
            fetchGroupData(); // Refresh posts
        } catch (err) {
            alert(err.message || 'Failed to create post');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 className="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p className="text-slate-500 text-xs font-black uppercase tracking-widest">Loading Hub...</p>
            </div>
        );
    }

    if (!group) {
        return (
            <div className="text-center py-20">
                <AlertCircle className="w-16 h-16 text-slate-300 mx-auto mb-4" />
                <h1 className="text-2xl font-black dark:text-white mb-2">Group Not Found</h1>
                <p className="text-slate-500 mb-8 font-medium">This community might have been deleted or moved.</p>
                <Link to="/social/groups" className="btn-primary px-8 py-3 rounded-full text-xs font-black uppercase tracking-widest">
                    Back to Groups
                </Link>
            </div>
        );
    }

    const isMember = !!group.user_role;
    const coverGradient = `from-[${group.cover_color || '#6366f1'}] to-[${group.cover_color_end || '#8b5cf6'}]`;

    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {/* Cover Banner */}
            <div className="relative rounded-3xl overflow-hidden shadow-2xl">
                <div className={`h-48 sm:h-64 bg-gradient-to-br ${coverGradient} relative`}>
                    <div className="absolute inset-0 bg-mesh-primary opacity-30" />
                    <div className="absolute top-4 right-4 flex gap-2">
                        <button className="p-2 bg-white/10 backdrop-blur-md rounded-full text-white hover:bg-white/20 transition-all">
                            <LinkIcon className="w-5 h-5" />
                        </button>
                        <button className="p-2 bg-white/10 backdrop-blur-md rounded-full text-white hover:bg-white/20 transition-all">
                            <MoreHorizontal className="w-5 h-5" />
                        </button>
                    </div>
                </div>
                
                <div className="bg-white dark:bg-[#0B1120] px-8 pb-8 border-x border-b border-slate-200 dark:border-slate-800">
                    <div className="flex flex-col sm:flex-row items-end gap-6 -mt-12 relative z-10 px-0 sm:px-4">
                        <div className={`w-24 h-24 sm:w-32 sm:h-32 rounded-[2.5rem] bg-gradient-to-br ${coverGradient} border-8 border-white dark:border-[#0B1120] flex items-center justify-center text-white text-3xl sm:text-5xl font-black shadow-2xl overflow-hidden`}>
                            {group.avatar_url ? (
                                <img src={group.avatar_url} alt={group.name} className="w-full h-full object-cover" />
                            ) : (
                                group.icon_initials || group.name[0]
                            )}
                        </div>
                        
                        <div className="flex-1 pb-4 text-center sm:text-left">
                            <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-1">
                                <h1 className="text-3xl font-black dark:text-white tracking-tight leading-tight">{group.name}</h1>
                                <span className={`inline-flex items-center px-3 py-1 bg-emerald-500/10 text-emerald-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-500/20 w-fit mx-auto sm:mx-0`}>
                                    {group.category || 'Official'}
                                </span>
                                {group.user_role === 'manager' && (
                                    <span className="inline-flex items-center px-3 py-1 bg-primary-500/10 text-primary-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-primary-500/20 w-fit mx-auto sm:mx-0">
                                        Manager
                                    </span>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center justify-center sm:justify-start gap-4 text-slate-500 text-xs font-bold uppercase tracking-widest">
                                <span className="flex items-center gap-1.5"><Users className="w-4 h-4 text-primary-500" /> {group.member_count} Members</span>
                                <span className="flex items-center gap-1.5"><Calendar className="w-4 h-4 text-amber-500" /> Since {new Date(group.created_at).getFullYear()}</span>
                                {group.user_role && (
                                    <span className="flex items-center gap-1.5 text-emerald-500 bg-emerald-500/5 px-2 py-0.5 rounded-lg border border-emerald-500/20">
                                        <ShieldCheck className="w-3.5 h-3.5" /> {group.user_role}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="pb-4 w-full sm:w-auto">
                            <button 
                                onClick={handleJoinLeave}
                                disabled={isJoining}
                                className={`w-full sm:w-auto px-8 py-3 rounded-2xl text-sm font-black uppercase tracking-widest transition-all shadow-xl flex items-center justify-center gap-2 ${
                                    isMember
                                    ? 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-red-500/5 hover:text-red-500 hover:border-red-500/30'
                                    : 'bg-primary-500 text-white hover:bg-primary-600 shadow-primary-500/20 hover:-translate-y-1'
                                }`}
                            >
                                {isJoining && <Loader2 className="w-4 h-4 animate-spin" />}
                                {isMember ? 'Joined' : 'Join Hub'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content Tabs */}
            <div className="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8">
                
                <div className="space-y-8">
                    {/* Tabs Nav */}
                    <div className="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-px overflow-x-auto no-scrollbar">
                        {[
                            { id: 'posts', label: 'Recent Posts', icon: MessageSquare },
                            { id: 'about', label: 'About Info', icon: Info },
                        ].map(tab => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`flex items-center gap-2 px-6 py-4 text-xs font-black uppercase tracking-widest transition-all relative ${
                                    activeTab === tab.id 
                                    ? 'text-primary-500' 
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'
                                }`}
                            >
                                <tab.icon className="w-4 h-4" />
                                <span>{tab.label}</span>
                                {activeTab === tab.id && (
                                    <div className="absolute bottom-0 left-0 right-0 h-1 bg-primary-500 rounded-t-full shadow-glow" />
                                )}
                            </button>
                        ))}
                    </div>

                    {/* Tab Content */}
                    <div className="pt-2">
                        {activeTab === 'posts' && (
                            <div className="space-y-6">
                                {isMember && (
                                    <div className="space-y-4">
                                        {!showPostBox ? (
                                            <div 
                                                onClick={() => setShowPostBox(true)}
                                                className="p-6 bg-primary-500/5 dark:bg-primary-100/1 border border-dashed border-primary-500/30 rounded-3xl flex items-center justify-between gap-4 group cursor-pointer hover:bg-primary-500/10 transition-all"
                                            >
                                                <div className="flex items-center gap-4">
                                                    <div className="w-12 h-12 bg-primary-500 text-white rounded-2xl flex items-center justify-center shadow-glow shadow-primary-500/20">
                                                        <Plus className="w-6 h-6" />
                                                    </div>
                                                    <div>
                                                        <h4 className="text-sm font-black dark:text-white uppercase tracking-tight">Post into {group.icon_initials || group.name[0]}Hub</h4>
                                                        <p className="text-xs text-slate-500 font-medium tracking-tight">Share an update, question, or resource with members.</p>
                                                    </div>
                                                </div>
                                                <button className="px-6 py-2.5 bg-white dark:bg-[#0B1120] border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-black text-slate-600 dark:text-slate-400 group-hover:text-primary-500 transition-colors uppercase tracking-widest">
                                                    New Post
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="card p-6 bg-white dark:bg-[#0B1120] border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-300">
                                                <textarea 
                                                    autoFocus
                                                    value={postContent}
                                                    onChange={(e) => setPostContent(e.target.value)}
                                                    className="w-full bg-transparent border-none p-0 focus:ring-0 text-sm dark:text-white placeholder:text-slate-500 resize-none min-h-[120px]"
                                                    placeholder={`What's on your mind, ${user?.firstname || 'Geek'}?`}
                                                />

                                                {showMediaInput && (
                                                    <div className="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                                        <MediaUrlInput 
                                                            onSelect={(m) => setPostMedia(m)}
                                                            onClear={() => setPostMedia(null)}
                                                            initialValue={postMedia?.url || ''}
                                                        />
                                                    </div>
                                                )}

                                                <div className="flex items-center justify-between mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                                    <div className="flex items-center gap-2">
                                                        <button 
                                                            onClick={() => setShowMediaInput(!showMediaInput)}
                                                            className={`p-2 rounded-xl transition-colors ${showMediaInput ? 'bg-primary-500 text-white' : 'text-primary-500 hover:bg-primary-500/10'}`}
                                                        >
                                                            <ImageIcon className="w-5 h-5" />
                                                        </button>
                                                    </div>
                                                    <div className="flex items-center gap-3">
                                                        <button 
                                                            onClick={() => {
                                                                setShowPostBox(false);
                                                                setShowMediaInput(false);
                                                            }}
                                                            className="px-4 py-2 text-xs font-black text-slate-500 hover:text-slate-800 dark:hover:text-white uppercase tracking-widest"
                                                        >
                                                            Cancel
                                                        </button>
                                                        <button 
                                                            onClick={handleCreatePost}
                                                            disabled={isSubmitting || !postContent.trim()}
                                                            className="btn-primary px-8 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-glow shadow-primary-500/20 disabled:opacity-50 flex items-center gap-2"
                                                        >
                                                            {isSubmitting && <Loader2 className="w-3 h-3 animate-spin" />}
                                                            Post Now
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                                
                                <div className="space-y-6">
                                    {posts.length > 0 ? (
                                        posts.map(post => <PostCard key={post.id} post={post} />)
                                    ) : (
                                        <div className="text-center py-20 card border-dashed border-2 bg-transparent border-slate-200 dark:border-slate-800">
                                            <p className="text-slate-500 text-sm font-medium">No posts here yet. Be the first to share something!</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {activeTab === 'about' && (
                            <div className="card p-8 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120]">
                                <h3 className="text-lg font-black dark:text-white tracking-tight mb-4">About the Community</h3>
                                <p className="text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-semibold mb-8 whitespace-pre-wrap">
                                    {group.description || "No description provided for this group yet."}
                                </p>
                                
                                <div className="space-y-6 pt-6 border-t border-slate-50 dark:border-white/5">
                                    <div>
                                        <h4 className="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">
                                            <ShieldCheck className="w-4 h-4 text-emerald-500" />
                                            Hub Rules & Meta
                                        </h4>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div className="p-4 bg-slate-50 dark:bg-white/5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                                <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Privacy</p>
                                                <p className="text-xs font-black text-slate-700 dark:text-slate-200 uppercase">{group.is_private ? 'Private Community' : 'Public Hub'}</p>
                                            </div>
                                            <div className="p-4 bg-slate-50 dark:bg-white/5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                                <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Moderation</p>
                                                <p className="text-xs font-black text-slate-700 dark:text-slate-200 uppercase">{group.post_approval_required ? 'Approval Required' : 'Open Posting'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Sidebar */}
                <aside className="space-y-8">
                    {group.user_role === 'manager' && (
                        <Link 
                            to={`/social/manager/${group.slug}`}
                            className="w-full flex items-center justify-center gap-3 p-6 bg-gradient-to-br from-primary-500 via-primary-600 to-indigo-600 rounded-3xl text-white shadow-xl shadow-primary-500/30 group hover:-translate-y-1 transition-all border border-white/10"
                        >
                            <ShieldCheck className="w-7 h-7 group-hover:rotate-12 transition-transform" />
                            <div className="text-left">
                                <p className="text-[10px] font-black uppercase tracking-widest opacity-80 leading-none mb-1">Hub Management</p>
                                <p className="text-sm font-black uppercase tracking-tight">Open Customizer</p>
                            </div>
                        </Link>
                    )}

                    <div className="card p-6 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120]">
                        <h3 className="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase mb-6 px-2">Recent Members</h3>
                        <div className="flex flex-wrap gap-2">
                             {members.length > 0 ? (
                                members.slice(0, 12).map((member) => (
                                    <Link 
                                        key={member.id} 
                                        to={`/social/profile?id=${member.id}`}
                                        title={`${member.firstname} ${member.lastname}`}
                                        className="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-[10px] font-black text-slate-400 border border-slate-200 dark:border-slate-800 overflow-hidden hover:border-primary-500 transition-colors"
                                    >
                                        {member.avatar_image ? (
                                            <img src={member.avatar_image} alt={member.firstname} className="w-full h-full object-cover" />
                                        ) : (
                                            `${member.firstname[0]}${member.lastname[0]}`
                                        )}
                                    </Link>
                                ))
                             ) : (
                                <p className="text-[10px] text-slate-500 font-bold uppercase px-2">No members yet</p>
                             )}
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
}

function ImageIcon({ className }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className={className}>
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
        </svg>
    );
}

