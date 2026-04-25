import { useState, useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import api, { API_BASE } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { UserAvatar } from '../../components/ui/StatusBadge';
import { SkeletonCard } from '../../components/ui/Skeleton';
import EmptyState from '../../components/ui/EmptyState';
import { Send, Paperclip, Search, MessageSquare, CheckCheck, X, File, Plus, UserPlus, ArrowLeft, Trash2 } from 'lucide-react';

export default function MessagesPage() {
    const { user } = useAuth();
    const toast = useToast();
    const [conversations, setConversations] = useState([]);
    const [activeThread, setActiveThread] = useState(null);
    const [messages, setMessages] = useState([]);
    const [otherUser, setOtherUser] = useState(null);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [attachment, setAttachment] = useState(null);
    const [showNewMsgModal, setShowNewMsgModal] = useState(false);
    const [allUsers, setAllUsers] = useState([]);
    const [userSearch, setUserSearch] = useState('');
    const [deletingConv, setDeletingConv] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const messagesEndRef = useRef(null);
    const fileInputRef = useRef(null);
    const location = useLocation();

    useEffect(() => {
        loadConversations();

        // Check if we navigated here to start a specific chat
        if (location.state?.userId) {
            loadThread(location.state.userId);
        }

        const interval = setInterval(loadConversations, 10000); // Poll every 10s
        return () => clearInterval(interval);
    }, [location]);

    const loadConversations = async () => {
        try {
            const res = await api.get('/messages/conversations');
            setConversations(res.data);
        } catch (e) { } finally {
            setLoading(false);
        }
    };

    const loadThread = async (userId) => {
        setActiveThread(userId);
        setConfirmDelete(false);
        try {
            const res = await api.get(`/messages/thread/${userId}`);
            setMessages(res.data.messages);
            setOtherUser(res.data.other_user);
            setTimeout(() => messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' }), 100);
        } catch (err) {
            toast.error('Failed to load messages');
        }
    };

    // Fetch all users for new message modal (available to everyone)
    const fetchUsers = async () => {
        try {
            const res = await api.get('/messages/users');
            setAllUsers(res.data || []);
        } catch (e) {
            toast.error('Failed to load users');
        }
    };

    useEffect(() => {
        if (showNewMsgModal) fetchUsers();
    }, [showNewMsgModal]);

    useEffect(() => {
        if (!activeThread) return;
        const interval = setInterval(() => loadThread(activeThread), 5000);
        return () => clearInterval(interval);
    }, [activeThread]);

    const sendMessage = async (e) => {
        e.preventDefault();
        if ((!newMessage.trim() && !attachment) || !activeThread) return;
        setSending(true);
        try {
            if (attachment) {
                const formData = new FormData();
                formData.append('receiver_id', activeThread);
                formData.append('message', newMessage.trim());
                formData.append('attachment', attachment);
                await api.upload('/messages', formData);
            } else {
                await api.post('/messages', {
                    receiver_id: activeThread,
                    message: newMessage.trim(),
                });
            }
            setNewMessage('');
            setAttachment(null);
            loadThread(activeThread);
            loadConversations();
        } catch (err) {
            toast.error(err.message);
        } finally {
            setSending(false);
        }
    };

    const handleDeleteConversation = async () => {
        if (!activeThread) return;
        if (!confirmDelete) {
            setConfirmDelete(true);
            return;
        }
        setDeletingConv(true);
        try {
            await api.delete(`/messages/conversation/${activeThread}`);
            toast.success('Conversation deleted.');
            setActiveThread(null);
            setOtherUser(null);
            setMessages([]);
            setConfirmDelete(false);
            loadConversations();
        } catch (err) {
            toast.error(err.message || 'Failed to delete conversation');
        } finally {
            setDeletingConv(false);
        }
    };

    const filtered = conversations.filter(c =>
        !searchTerm || `${c.firstname} ${c.lastname}`.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const filteredUsers = allUsers.filter(s =>
        !userSearch ||
        `${s.firstname} ${s.lastname}`.toLowerCase().includes(userSearch.toLowerCase()) ||
        s.email.toLowerCase().includes(userSearch.toLowerCase())
    );

    if (loading) {
        return (
            <div className="space-y-4">
                <SkeletonCard />
                <SkeletonCard />
            </div>
        );
    }

    return (
        <div className="animate-fade-in">
            <h1 className="text-2xl font-bold mb-6">Messages</h1>

            <div className="card overflow-hidden" style={{ height: 'calc(100vh - 160px)' }}>
                <div className="flex h-full">
                    {/* Conversation List */}
                    <div className={`w-full sm:w-80 border-r border-surface-100 dark:border-surface-800 flex flex-col ${activeThread ? 'hidden sm:flex' : 'flex'}`}>
                        <div className="p-4 border-b border-surface-100 dark:border-surface-800 space-y-3">
                            <div className="flex items-center justify-between gap-2">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={e => setSearchTerm(e.target.value)}
                                        className="input-field pl-10 py-2 text-sm"
                                        placeholder="Search..."
                                    />
                                </div>
                                {/* New Message button — visible to ALL users */}
                                <button
                                    onClick={() => setShowNewMsgModal(true)}
                                    className="btn-primary p-2 rounded-xl shrink-0"
                                    title="New Message"
                                >
                                    <Plus className="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <div className="flex-1 overflow-y-auto">
                            {filtered.length === 0 ? (
                                <div className="p-8 text-center text-surface-400">
                                    <MessageSquare className="w-8 h-8 mx-auto mb-2 opacity-50" />
                                    <p className="text-sm">No conversations yet</p>
                                    <button
                                        onClick={() => setShowNewMsgModal(true)}
                                        className="text-primary-500 font-semibold text-xs mt-4 hover:underline"
                                    >
                                        + Start a new chat
                                    </button>
                                </div>
                            ) : filtered.map(conv => (
                                <button
                                    key={conv.other_user_id}
                                    onClick={() => loadThread(conv.other_user_id)}
                                    className={`w-full flex items-center gap-3 p-4 text-left hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors border-b border-surface-50 dark:border-surface-800/50
                    ${activeThread === conv.other_user_id ? 'bg-primary-50 dark:bg-primary-900/10' : ''}
                  `}
                                >
                                    <div className="relative">
                                        <UserAvatar user={conv} size="md" />
                                        {conv.unread_count > 0 && (
                                            <span className="absolute -top-1 -right-1 w-5 h-5 bg-primary-600 rounded-full text-white text-xs flex items-center justify-center">{conv.unread_count}</span>
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between">
                                            <p className={`text-sm truncate ${conv.unread_count > 0 ? 'font-semibold' : 'font-medium'}`}>
                                                {conv.firstname} {conv.lastname}
                                            </p>
                                            <span className="text-xs text-surface-400">{new Date(conv.last_message_at).toLocaleDateString()}</span>
                                        </div>
                                        <p className="text-xs text-surface-500 truncate mt-0.5">{conv.last_message}</p>
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Chat Thread */}
                    <div className={`flex-1 flex flex-col ${activeThread ? 'flex' : 'hidden sm:flex'}`}>
                        {activeThread && otherUser ? (
                            <>
                                {/* Chat Header */}
                                <div className="flex items-center gap-3 p-4 border-b border-surface-100 dark:border-surface-800">
                                    <button onClick={() => { setActiveThread(null); setConfirmDelete(false); }} className="sm:hidden btn-icon w-8 h-8 flex items-center justify-center">
                                        <ArrowLeft className="w-4 h-4" />
                                    </button>
                                    <UserAvatar user={otherUser} size="sm" />
                                    <div className="flex-1">
                                        <p className="font-medium text-sm">{otherUser.firstname} {otherUser.lastname}</p>
                                        <p className="text-xs text-surface-500">{otherUser.role_name || otherUser.role}</p>
                                    </div>
                                    {/* Delete Conversation */}
                                    {confirmDelete ? (
                                        <div className="flex items-center gap-2 animate-fade-in">
                                            <span className="text-xs text-red-500 font-medium">Delete conversation?</span>
                                            <button
                                                onClick={handleDeleteConversation}
                                                disabled={deletingConv}
                                                className="text-xs px-3 py-1.5 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors font-semibold disabled:opacity-50"
                                            >
                                                {deletingConv ? 'Deleting…' : 'Yes, Delete'}
                                            </button>
                                            <button
                                                onClick={() => setConfirmDelete(false)}
                                                className="text-xs px-3 py-1.5 rounded-lg bg-surface-100 dark:bg-surface-800 hover:bg-surface-200 dark:hover:bg-surface-700 transition-colors"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    ) : (
                                        <button
                                            onClick={() => setConfirmDelete(true)}
                                            className="btn-icon w-9 h-9 text-surface-400 hover:text-red-500 transition-colors"
                                            title="Delete conversation"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    )}
                                </div>

                                {/* Messages */}
                                <div className="flex-1 overflow-y-auto p-4 space-y-3">
                                    {messages.length === 0 ? (
                                        <div className="flex flex-col items-center justify-center h-full text-surface-400">
                                            <MessageSquare className="w-10 h-10 mb-3 opacity-30" />
                                            <p className="text-sm">No messages yet — say hello!</p>
                                        </div>
                                    ) : messages.map(msg => {
                                        const isMine = msg.sender_id == user.id;
                                        return (
                                            <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                                                <div className={`max-w-[70%] rounded-2xl px-4 py-2.5 ${isMine
                                                    ? 'bg-primary-600 text-white rounded-br-md'
                                                    : 'bg-surface-100 dark:bg-surface-800 rounded-bl-md'
                                                    }`}>
                                                    <p className="text-sm whitespace-pre-wrap">{msg.message}</p>
                                                    {msg.attachment_path && (
                                                        <a href={msg.attachment_path.startsWith('http') ? msg.attachment_path : `${API_BASE}/${msg.attachment_path}`} target="_blank" rel="noopener" className="flex items-center gap-1 mt-1 text-xs underline opacity-75">
                                                            <Paperclip className="w-3 h-3" /> Attachment
                                                        </a>
                                                    )}
                                                    <div className={`flex items-center gap-1 mt-1 ${isMine ? 'justify-end' : ''}`}>
                                                        <span className={`text-xs ${isMine ? 'text-white/70' : 'text-surface-400'}`}>
                                                            {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                        </span>
                                                        {isMine && msg.is_read && <CheckCheck className="w-3 h-3 text-white/70" />}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                    <div ref={messagesEndRef} />
                                </div>

                                {/* Message Input */}
                                <div className="p-4 border-t border-surface-100 dark:border-surface-800">
                                    {attachment && (
                                        <div className="flex items-center gap-2 mb-3 p-2 rounded-xl bg-surface-50 dark:bg-white/5 border border-white/10 animate-fade-in">
                                            <div className="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center text-primary-500">
                                                <File className="w-4 h-4" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-xs font-semibold truncate">{attachment.name}</p>
                                                <p className="text-[10px] opacity-60">{(attachment.size / 1024).toFixed(1)} KB</p>
                                            </div>
                                            <button onClick={() => setAttachment(null)} className="btn-icon w-7 h-7">
                                                <X className="w-4 h-4" />
                                            </button>
                                        </div>
                                    )}
                                    <form onSubmit={sendMessage} className="flex items-center gap-3">
                                        <input
                                            type="file"
                                            ref={fileInputRef}
                                            onChange={e => setAttachment(e.target.files[0])}
                                            className="hidden"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => fileInputRef.current?.click()}
                                            className={`btn-icon w-12 h-12 ${attachment ? 'text-primary-500 bg-primary-500/10' : ''}`}
                                            disabled={sending}
                                        >
                                            <Paperclip className="w-5 h-5" />
                                        </button>
                                        <input
                                            type="text"
                                            value={newMessage}
                                            onChange={e => setNewMessage(e.target.value)}
                                            className="input-field flex-1"
                                            placeholder="Type a message..."
                                            disabled={sending}
                                        />
                                        <button type="submit" disabled={sending || (!newMessage.trim() && !attachment)} className="btn-primary px-4 py-3">
                                            <Send className="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </>
                        ) : (
                            <EmptyState
                                icon={MessageSquare}
                                title="Select a conversation"
                                description="Choose a conversation from the list or start a new one."
                                action={
                                    <button
                                        onClick={() => setShowNewMsgModal(true)}
                                        className="btn-primary mt-4 flex items-center gap-2"
                                    >
                                        <Plus className="w-4 h-4" /> New Message
                                    </button>
                                }
                            />
                        )}
                    </div>
                </div>
            </div>

            {/* New Message Modal — available to ALL users */}
            {showNewMsgModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setShowNewMsgModal(false)} />
                    <div className="card w-full max-w-md relative animate-slide-up shadow-2xl overflow-hidden">
                        <div className="p-4 border-b border-white/10 flex items-center justify-between">
                            <h3 className="font-bold flex items-center gap-2">
                                <UserPlus className="w-5 h-5 text-primary-500" />
                                Start New Conversation
                            </h3>
                            <button onClick={() => setShowNewMsgModal(false)} className="btn-icon">
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <div className="p-4 bg-surface-50 dark:bg-white/5 border-b border-white/10">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                                <input
                                    type="text"
                                    value={userSearch}
                                    onChange={e => setUserSearch(e.target.value)}
                                    className="input-field pl-10 text-sm"
                                    placeholder="Search by name or email..."
                                    autoFocus
                                />
                            </div>
                        </div>
                        <div className="max-h-80 overflow-y-auto">
                            {filteredUsers.length === 0 ? (
                                <div className="p-8 text-center text-surface-400 text-sm">
                                    {allUsers.length === 0 ? 'Loading users…' : 'No users found.'}
                                </div>
                            ) : filteredUsers.map(u => (
                                <button
                                    key={u.id}
                                    onClick={() => {
                                        loadThread(u.id);
                                        setShowNewMsgModal(false);
                                        setUserSearch('');
                                    }}
                                    className="w-full flex items-center gap-3 p-4 text-left hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors border-b border-surface-50 dark:border-surface-800/50"
                                >
                                    <UserAvatar user={u} size="sm" />
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-semibold truncate">{u.firstname} {u.lastname}</p>
                                        <p className="text-xs text-surface-500 truncate">{u.email}</p>
                                    </div>
                                    <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${u.role === 'admin' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                            : u.role === 'manager' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                : 'bg-surface-100 text-surface-600 dark:bg-surface-800 dark:text-surface-400'
                                        }`}>
                                        {u.role}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
