import { useState } from 'react';
import { Heart, MessageSquare, Share2, MoreHorizontal, Link as LinkIcon, Play, Image as ImageIcon } from 'lucide-react';
import { Link } from 'react-router-dom';
import { socialApi } from '../../api/social';
import { UserAvatar } from '../ui/StatusBadge';

export default function PostCard({ post }) {
    const [isLiked, setIsLiked] = useState(!!post.is_liked);
    const [likesCount, setLikesCount] = useState(parseInt(post.likes_count || post.likes || 0));
    const [isLiking, setIsLiking] = useState(false);

    const handleLike = async () => {
        if (isLiking) return;
        setIsLiking(true);
        try {
            const res = await socialApi.likePost(post.id);
            setIsLiked(res.data.is_liked);
            setLikesCount(prev => res.data.is_liked ? prev + 1 : prev - 1);
        } catch (err) {
            console.error('Failed to like post:', err);
        } finally {
            setIsLiking(false);
        }
    };

    const authorName = post.author || `${post.firstname} ${post.lastname}`;
    const initials = post.initials || (post.firstname && post.lastname ? `${post.firstname[0]}${post.lastname[0]}` : (post.firstname ? post.firstname[0] : '??'));
    const timeStr = post.time || (post.created_at ? new Date(post.created_at.replace(' ', 'T') + 'Z').toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Recently');
    const groupName = post.group_name || post.group || 'General';

    return (
        <article className="post-card card overflow-hidden border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] transition-all hover:shadow-lg hover:shadow-primary-500/5">
            {/* Header */}
            <div className="p-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <UserAvatar 
                        user={{
                            firstname: post.firstname, 
                            lastname: post.lastname, 
                            avatar_image: post.avatar_image,
                            initials: initials
                        }} 
                        size="md" 
                    />
                    <div className="min-w-0">
                        <Link to={`/social/profile/${post.user_id || post.id}`} className="text-sm font-black dark:text-white hover:text-primary-500 transition-colors block leading-tight truncate">
                            {authorName}
                        </Link>
                        <div className="flex items-center gap-1.5 mt-0.5">
                            {post.group_slug ? (
                                <Link to={`/social/groups/${post.group_slug}`} className="text-[10px] font-black text-primary-500 uppercase tracking-widest hover:underline">
                                    {groupName}
                                </Link>
                            ) : (
                                <span className="text-[10px] font-bold text-primary-500 uppercase tracking-widest">{groupName}</span>
                            )}
                            <span className="text-[10px] text-slate-400 font-bold">•</span>
                            <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{timeStr}</span>
                        </div>
                    </div>
                </div>
                <button className="p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-full transition-colors">
                    <MoreHorizontal className="w-5 h-5" />
                </button>
            </div>

            {/* Content */}
            <div className="px-4 pb-4">
                <p className="text-sm dark:text-slate-200 leading-relaxed mb-4 whitespace-pre-wrap">
                    {post.content}
                </p>

                {post.media_url && (
                    <div className="rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 group cursor-pointer relative">
                        {post.media_type === 'video' ? (
                            <div className="aspect-video bg-black flex items-center justify-center relative">
                                <iframe 
                                    src={post.media_url.replace('watch?v=', 'embed/')} 
                                    className="absolute inset-0 w-full h-full"
                                    allowFullScreen
                                    title="Video content"
                                />
                            </div>
                        ) : (
                            <img 
                                src={post.media_url} 
                                alt="Post visual" 
                                className="w-full h-auto max-h-[500px] object-contain bg-black/5"
                                onError={(e) => {
                                    e.target.style.display = 'none';
                                }}
                            />
                        )}
                    </div>
                )}
            </div>

            {/* Actions */}
            <div className="px-2 py-2 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1">
                <button 
                    onClick={handleLike}
                    disabled={isLiking}
                    className={`action-btn flex-1 group ${isLiked ? 'liked text-red-500 bg-red-500/5' : ''}`}
                >
                    <div className={`like-btn transition-transform ${isLiked ? 'scale-110' : ''}`}>
                        <Heart className={`w-5 h-5 ${isLiked ? 'fill-current' : ''}`} />
                    </div>
                    <span className="text-[10px] font-black uppercase tracking-widest">{likesCount} Likes</span>
                </button>

                <Link to={`/social/posts/${post.id}`} className="action-btn flex-1">
                    <MessageSquare className="w-5 h-5" />
                    <span className="text-[10px] font-black uppercase tracking-widest">{post.comments_count || post.comments || 0} Comments</span>
                </Link>

                <button 
                    onClick={() => {
                        if (navigator.share) {
                            navigator.share({
                                title: post.group_name || 'Campus Dive Post',
                                text: post.content,
                                url: window.location.href,
                            });
                        } else {
                            navigator.clipboard.writeText(window.location.href);
                            window.alert('Link copied to clipboard!');
                        }
                    }}
                    className="action-btn flex-1 active:scale-95 transition-transform"
                >
                    <Heart className={`w-5 h-5 ${isLiked ? 'fill-current' : ''}`} style={{ display: 'none' }} />
                    <Share2 className="w-5 h-5" />
                    <span className="text-[10px] font-black uppercase tracking-widest">Share</span>
                </button>
            </div>
        </article>
    );
}
