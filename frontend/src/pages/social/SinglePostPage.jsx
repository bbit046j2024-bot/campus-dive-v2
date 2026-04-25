import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { 
    ChevronLeft, Heart, MessageSquare, 
    MoreHorizontal, Send, Smile, Loader2, AlertCircle
} from 'lucide-react';
import PostCard from '../../components/social/PostCard';
import { socialApi } from '../../api/social';
import { UserAvatar } from '../../components/ui/StatusBadge';
import { useAuth } from '../../context/AuthContext';

export default function SinglePostPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();

    const [post, setPost] = useState(null);
    const [comments, setComments] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [commentText, setCommentText] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        fetchPostData();
    }, [id]);

    const fetchPostData = async () => {
        setIsLoading(true);
        try {
            const [postRes, commentsRes] = await Promise.all([
                socialApi.getPostDetail(id),
                socialApi.getComments(id)
            ]);
            setPost(postRes.data);
            setComments(commentsRes.data || []);
        } catch (err) {
            console.error('Failed to fetch post:', err);
        } finally {
            setIsLoading(false);
        }
    };

    const handleAddComment = async () => {
        if (!commentText.trim() || isSubmitting) return;
        setIsSubmitting(true);
        try {
            await socialApi.addComment({
                post_id: id,
                content: commentText
            });
            setCommentText('');
            // Refresh comments
            const commentsRes = await socialApi.getComments(id);
            setComments(commentsRes.data || []);
            // Also refresh post to update comment count
            const postRes = await socialApi.getPostDetail(id);
            setPost(postRes.data);
        } catch (err) {
            alert(err.message || 'Failed to add comment');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 className="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p className="text-slate-500 text-xs font-black uppercase tracking-widest">Loading Post...</p>
            </div>
        );
    }

    if (!post) {
        return (
            <div className="text-center py-20">
                <AlertCircle className="w-16 h-16 text-slate-300 mx-auto mb-4" />
                <h1 className="text-2xl font-black dark:text-white mb-2">Post Not Found</h1>
                <p className="text-slate-500 mb-8 font-medium">This post might have been deleted or moved.</p>
                <button 
                    onClick={() => navigate(-1)}
                    className="btn-primary px-8 py-3 rounded-full text-xs font-black uppercase tracking-widest"
                >
                    Go Back
                </button>
            </div>
        );
    }

    return (
        <div className="max-w-3xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500 pt-4">
            {/* Back Nav */}
            <button 
                onClick={() => navigate(-1)}
                className="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-primary-500 transition-colors px-2 mb-4"
            >
                <ChevronLeft className="w-5 h-5" />
                <span>Back to Hub</span>
            </button>

            {/* Post View */}
            <PostCard post={post} />

            {/* Comments Section */}
            <section className="space-y-6 pb-20">
                <div className="flex items-center justify-between px-2">
                    <h3 className="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase">Comments ({post.comments_count})</h3>
                </div>

                {/* Comment Input */}
                <div className="card p-6 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] shadow-glow shadow-primary-500/5">
                    <div className="flex gap-4">
                        <UserAvatar user={user} size="sm" />
                        <div className="flex-1 relative">
                            <textarea 
                                value={commentText}
                                onChange={(e) => setCommentText(e.target.value)}
                                className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 pr-12 text-sm dark:text-white placeholder:text-slate-500 resize-none focus:ring-1 focus:ring-primary-500/50 transition-all min-h-[80px]"
                                placeholder="Write a comment..."
                            />
                            <div className="absolute right-3 bottom-3 flex items-center gap-2 text-slate-400">
                                <button 
                                    onClick={handleAddComment}
                                    disabled={!commentText.trim() || isSubmitting}
                                    className={`p-2 rounded-xl transition-all ${commentText.trim() ? 'bg-primary-500 text-white shadow-glow' : 'hover:text-primary-500'}`}
                                >
                                    {isSubmitting ? <Loader2 className="w-5 h-5 animate-spin" /> : <Send className="w-5 h-5" />}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Comments List */}
                <div className="space-y-4">
                    {comments.length > 0 ? (
                        comments.map(comment => (
                            <div key={comment.id} className="card p-6 border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120] hover:border-slate-300 dark:hover:border-slate-700 transition-all group">
                                <div className="flex gap-4">
                                    <UserAvatar user={comment} size="sm" />
                                    <div className="flex-1">
                                        <div className="flex items-center justify-between mb-2">
                                            <div>
                                                <span className="text-sm font-black dark:text-white leading-none whitespace-nowrap">
                                                    {comment.firstname} {comment.lastname}
                                                </span>
                                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-3">
                                                    {new Date(comment.created_at).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                                </span>
                                            </div>
                                            <button className="p-1 hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <MoreHorizontal className="w-4 h-4" />
                                            </button>
                                        </div>
                                        <p className="text-sm dark:text-slate-300 leading-relaxed">
                                            {comment.content}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-10 card border-dashed border-2 bg-transparent border-slate-200 dark:border-slate-800">
                             <p className="text-slate-500 text-[10px] font-black uppercase tracking-widest">No comments yet. Start the conversation!</p>
                        </div>
                    )}
                </div>
            </section>
        </div>
    );
}

