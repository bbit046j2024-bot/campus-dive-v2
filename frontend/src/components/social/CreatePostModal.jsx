import { useState, useEffect } from 'react';
import { X, Image as ImageIcon, Globe, Users, Lock, ChevronDown, Loader2 } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { UserAvatar } from '../ui/StatusBadge';
import { socialApi } from '../../api/social';
import MediaUrlInput from './MediaUrlInput';

export default function CreatePostModal({ isOpen, onClose }) {
    const { user } = useAuth();
    const [myGroups, setMyGroups] = useState([]);
    const [selectedGroup, setSelectedGroup] = useState(null);
    const [content, setContent] = useState('');
    const [media, setMedia] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showMediaInput, setShowMediaInput] = useState(false);

    useEffect(() => {
        if (isOpen) {
            fetchGroups();
        } else {
            // Reset state when closed
            setContent('');
            setMedia(null);
            setShowMediaInput(false);
        }
    }, [isOpen]);

    const fetchGroups = async () => {
        try {
            const res = await socialApi.getGroups();
            const isAdmin = user?.role === 'admin' || user?.role === 'Admin';
            const allGroups = res.data || [];
            const visibleGroups = isAdmin ? allGroups : allGroups.filter(g => g.user_role);
            
            setMyGroups(visibleGroups);
            if (visibleGroups.length > 0 && !selectedGroup) {
                setSelectedGroup(visibleGroups[0]);
            }
        } catch (err) {
            console.error('Failed to fetch groups:', err);
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
            
            // Dispatch custom event to notify feed components
            window.dispatchEvent(new CustomEvent('postCreated'));
            
            onClose();
        } catch (err) {
            alert(err.message || 'Failed to create post');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4 animate-in fade-in duration-300">
            <div 
                className="bg-white dark:bg-[#0B1120] rounded-[2rem] w-full max-w-lg shadow-2xl border border-white/20 dark:border-slate-800 overflow-hidden animate-in slide-in-from-bottom-8 duration-500 flex flex-col max-h-[90vh]"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                    <div>
                        <h2 className="text-xl font-black tracking-tight dark:text-white">Create a Post</h2>
                        <p className="text-xs text-slate-500 font-medium">Share something with the community</p>
                    </div>
                    <button 
                        onClick={onClose}
                        className="p-2 hover:bg-slate-100 dark:hover:bg-white/5 rounded-full transition-colors text-slate-400"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Content */}
                <div className="p-8 overflow-y-auto">
                    <div className="flex items-center gap-4 mb-6">
                        <UserAvatar user={user} size="md" />
                        <div>
                            <p className="font-bold dark:text-white">{user?.firstname} {user?.lastname}</p>
                            <div className="relative mt-0.5">
                                <select 
                                    value={selectedGroup?.id || ''}
                                    onChange={(e) => setSelectedGroup(myGroups.find(g => g.id == e.target.value))}
                                    className="flex appearance-none items-center gap-1.5 pl-7 pr-8 py-1 bg-slate-100 dark:bg-white/5 rounded-lg border border-slate-200 dark:border-slate-800 text-[10px] font-black text-slate-500 uppercase tracking-widest hover:border-primary-500/50 transition-colors focus:ring-0 cursor-pointer"
                                >
                                    {myGroups.map(g => (
                                        <option key={g.id} value={g.id}>{g.name}</option>
                                    ))}
                                    {myGroups.length === 0 && (
                                        <option value="">No groups joined</option>
                                    )}
                                </select>
                                <Globe className="w-3 h-3 absolute left-2 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none" />
                                <ChevronDown className="w-3 h-3 absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none" />
                            </div>
                        </div>
                    </div>

                    <textarea 
                        value={content}
                        onChange={(e) => setContent(e.target.value)}
                        autoFocus
                        className="w-full bg-transparent border-none p-0 focus:ring-0 text-lg dark:text-white placeholder:text-slate-400 resize-none min-h-[120px]"
                        placeholder="What's on your mind?"
                    />
                    
                    {showMediaInput && (
                        <div className="mt-4">
                            <MediaUrlInput 
                                onSelect={(m) => setMedia(m)}
                                onClear={() => setMedia(null)}
                                initialValue={media?.url || ''}
                            />
                        </div>
                    )}

                    <div className="flex items-center gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            onClick={() => setShowMediaInput(!showMediaInput)}
                            className={`flex items-center gap-2 transition-colors text-sm font-bold ${showMediaInput ? 'text-primary-500' : 'text-slate-500 hover:text-primary-500'}`}
                        >
                            <div className={`p-2 rounded-xl ${showMediaInput ? 'bg-primary-500/10' : 'bg-slate-100 dark:bg-white/5'}`}>
                                <ImageIcon className="w-5 h-5 text-primary-500" />
                            </div>
                            <span>Add Photo/Video</span>
                        </button>
                    </div>
                </div>

                {/* Footer */}
                <div className="px-8 py-6 bg-slate-50/50 dark:bg-white/2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0">
                    <button 
                        onClick={onClose}
                        className="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        onClick={handleCreatePost}
                        disabled={isSubmitting || !content.trim() || !selectedGroup}
                        className="px-8 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:hover:translate-y-0 text-white text-sm font-black rounded-xl shadow-glow shadow-primary-500/20 hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center gap-2"
                    >
                        {isSubmitting && <Loader2 className="w-4 h-4 animate-spin" />}
                        Post Now
                    </button>
                </div>
            </div>
        </div>
    );
}
