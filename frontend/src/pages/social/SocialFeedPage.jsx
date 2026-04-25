import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { UserAvatar } from '../../components/ui/StatusBadge';
import { Image as ImageIcon, Video, FileText, Smile, Loader2, Users } from 'lucide-react';
import PostCard from '../../components/social/PostCard';
import { socialApi } from '../../api/social';
import MediaUrlInput from '../../components/social/MediaUrlInput';

export default function SocialFeedPage() {
    const { user } = useAuth();
    const [isExpanded, setIsExpanded] = useState(false);
    const [posts, setPosts] = useState([]);
    const [myGroups, setMyGroups] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    
    // New Post State
    const [content, setContent] = useState('');
    const [selectedGroup, setSelectedGroup] = useState(null);
    const [media, setMedia] = useState(null);
    const [showMediaInput, setShowMediaInput] = useState(false);

    useEffect(() => {
        fetchData();
        
        const handlePostCreated = () => {
            fetchData();
        };
        window.addEventListener('postCreated', handlePostCreated);
        
        return () => {
            window.removeEventListener('postCreated', handlePostCreated);
        };
    }, []);

    const fetchData = async () => {
        setIsLoading(true);
        try {
            const [postsRes, groupsRes] = await Promise.all([
                socialApi.getGlobalFeed(),
                socialApi.getGroups()
            ]);
            setPosts(postsRes.data || []);
            // Only show groups where user is a member
            setMyGroups((groupsRes.data || []).filter(g => g.user_role));
            if (!selectedGroup && groupsRes.data?.length > 0) {
                const joined = groupsRes.data.find(g => g.user_role);
                if (joined) setSelectedGroup(joined);
            }
        } catch (err) {
            console.error('Failed to fetch data:', err);
        } finally {
            setIsLoading(false);
        }
    };

    const handleCreatePost = async () => {
        if (!content.trim() || !selectedGroup) return;
        
        setIsSubmitting(true);
        try {
            await socialApi.createPost({
                group_id: selectedGroup.id,
                content: content,
                media_url: media?.url || null
            });
            setContent('');
            setMedia(null);
            setShowMediaInput(false);
            setIsExpanded(false);
            fetchData(); // Refresh feed
        } catch (err) {
            alert(err.message || 'Failed to create post');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading && posts.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 className="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p className="text-slate-500 text-xs font-black uppercase tracking-widest">Loading Feed...</p>
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {/* Create Post Box */}
            <div className="card p-6 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] shadow-glow shadow-primary-500/5">
                <div className="flex gap-4">
                    <UserAvatar user={user} size="md" />
                    <div className="flex-1">
                        {!isExpanded ? (
                            <div 
                                onClick={() => setIsExpanded(true)}
                                className="w-full px-6 py-3 bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-500 cursor-text text-sm font-medium hover:border-primary-500/30 transition-all flex items-center"
                            >
                                Start a post in {selectedGroup ? selectedGroup.name : 'a group'}...
                            </div>
                        ) : (
                            <div className="space-y-4 animate-in fade-in duration-300">
                                {/* Group Selector */}
                                <div className="flex items-center gap-2 mb-2">
                                    <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">Posting to:</span>
                                    <select 
                                        value={selectedGroup?.id || ''} 
                                        onChange={(e) => setSelectedGroup(myGroups.find(g => g.id == e.target.value))}
                                        className="bg-slate-50 dark:bg-white/5 border-none text-[10px] font-black uppercase tracking-widest text-primary-500 focus:ring-0 rounded-lg py-1 px-2 cursor-pointer"
                                    >
                                        {myGroups.map(g => (
                                            <option key={g.id} value={g.id}>{g.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <textarea 
                                    autoFocus
                                    value={content}
                                    onChange={(e) => setContent(e.target.value)}
                                    className="w-full bg-transparent border-none p-0 focus:ring-0 text-sm dark:text-white placeholder:text-slate-500 resize-none min-h-[100px]"
                                    placeholder="What's on your mind?"
                                />

                                {showMediaInput && (
                                    <MediaUrlInput 
                                        onSelect={(m) => setMedia(m)}
                                        onClear={() => setMedia(null)}
                                        initialValue={media?.url || ''}
                                    />
                                )}

                                <div className="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <div className="flex items-center gap-2">
                                        <button 
                                            onClick={() => setShowMediaInput(!showMediaInput)}
                                            className={`p-2 rounded-xl transition-colors ${showMediaInput ? 'bg-primary-500 text-white' : 'text-primary-500 hover:bg-primary-500/10'}`}
                                        >
                                            <ImageIcon className="w-5 h-5" />
                                        </button>
                                        <button className="p-2 text-emerald-500 hover:bg-emerald-500/10 rounded-xl transition-colors opacity-50 cursor-not-allowed">
                                            <Video className="w-5 h-5" />
                                        </button>
                                        <button className="p-2 text-amber-500 hover:bg-amber-500/10 rounded-xl transition-colors opacity-50 cursor-not-allowed">
                                            <FileText className="w-5 h-5" />
                                        </button>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <button 
                                            onClick={() => {
                                                setIsExpanded(false);
                                                setShowMediaInput(false);
                                            }}
                                            className="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors"
                                        >
                                            Cancel
                                        </button>
                                        <button 
                                            onClick={handleCreatePost}
                                            disabled={isSubmitting || !content.trim()}
                                            className="btn-primary px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest shadow-glow shadow-primary-500/20 disabled:opacity-50 flex items-center gap-2"
                                        >
                                            {isSubmitting && <Loader2 className="w-3 h-3 animate-spin" />}
                                            Post
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
                
                {!isExpanded && (
                    <div className="flex items-center gap-6 mt-6 pt-6 border-t border-slate-50 dark:border-white/5">
                        <button onClick={() => { setIsExpanded(true); setShowMediaInput(true); }} className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-primary-500 transition-colors">
                            <ImageIcon className="w-4 h-4 text-primary-500" />
                            <span>Photo</span>
                        </button>
                        <button className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors opacity-50">
                            <Video className="w-4 h-4 text-emerald-500" />
                            <span>Video</span>
                        </button>
                        <button className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors opacity-50">
                            <FileText className="w-4 h-4 text-amber-500" />
                            <span>Event</span>
                        </button>
                    </div>
                )}
            </div>

            {/* Feed Filter */}
            <div className="flex items-center justify-between px-2">
                <div className="flex items-center gap-4">
                    <button className="text-[10px] font-black uppercase tracking-widest text-primary-500 border-b-2 border-primary-500 pb-1">Latest</button>
                    <button className="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 pb-1 transition-colors">Popular</button>
                </div>
            </div>

            {/* Empty State */}
            {posts.length === 0 && (
                <div className="text-center py-20 card border-dashed border-2 bg-transparent border-slate-200 dark:border-slate-800">
                    <div className="w-16 h-16 bg-slate-50 dark:bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                        <Users className="w-8 h-8 text-slate-400" />
                    </div>
                    <h3 className="text-lg font-black dark:text-white mb-2">No posts yet</h3>
                    <p className="text-sm text-slate-500 mb-6 max-w-xs mx-auto">Join some groups to see what's happening in your campus community!</p>
                    <Link to="/social/groups" className="btn-primary px-8 py-3 rounded-full text-xs font-black uppercase tracking-widest inline-flex items-center gap-2 shadow-glow shadow-primary-500/20">
                        Explore Groups
                    </Link>
                </div>
            )}

            {/* Posts Grid */}
            <div className="grid grid-cols-1 gap-6">
                {posts.map(post => (
                    <PostCard key={post.id} post={post} />
                ))}
            </div>

            {/* Load More */}
            {posts.length > 10 && (
                <div className="py-8 text-center">
                    <div className="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-[#0B1120] border border-slate-200 dark:border-slate-800 rounded-full text-xs font-black uppercase tracking-widest text-slate-500 hover:text-primary-500 hover:border-primary-500 transition-all cursor-pointer shadow-sm">
                        <ImageIcon className="w-4 h-4" />
                        <span>Load More Posts</span>
                    </div>
                </div>
            )}
        </div>
    );
}
