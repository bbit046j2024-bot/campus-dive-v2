import { useState, useEffect } from 'react';
import { 
    Plus, Search, Filter, MoreVertical, 
    Trash2, UserPlus, Settings, ExternalLink,
    Users, MessageSquare, ShieldCheck, Loader2, AlertCircle,
    Lock, Globe, X
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { socialApi } from '../../api/social';

export default function SocialGroupsAdminPage() {
    const [groups, setGroups] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [selectedGroup, setSelectedGroup] = useState(null);
    const [userSearch, setUserSearch] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [isSearchingUsers, setIsSearchingUsers] = useState(false);
    
    // Create Group Form
    const [newGroup, setNewGroup] = useState({
        name: '',
        description: '',
        category: 'Tech',
        is_private: false
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        fetchGroups();
    }, []);

    const fetchGroups = async () => {
        setIsLoading(true);
        try {
            const res = await socialApi.getAdminGroups();
            setGroups(res.data || []);
        } catch (err) {
            console.error('Failed to fetch admin groups:', err);
        } finally {
            setIsLoading(false);
        }
    };

    const handleCreateGroup = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        try {
            await socialApi.createGroup(newGroup);
            setShowCreateModal(false);
            setNewGroup({ name: '', description: '', category: 'Tech', is_private: false });
            fetchGroups();
        } catch (err) {
            alert(err.message || 'Failed to create group');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSearchUsers = async (query) => {
        setUserSearch(query);
        if (!query.trim()) {
            setSearchResults([]);
            return;
        }
        setIsSearchingUsers(true);
        try {
            const res = await socialApi.searchUsers(query);
            setSearchResults(res.data || []);
        } catch (err) {
            console.error('User search failed:', err);
        } finally {
            setIsSearchingUsers(false);
        }
    };

    const handleAssignManager = async (userId) => {
        setIsSubmitting(true);
        try {
            await socialApi.assignManager(selectedGroup.id, userId);
            setShowAssignModal(false);
            setUserSearch('');
            setSearchResults([]);
            fetchGroups();
        } catch (err) {
            alert(err.message || 'Assignment failed');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDeleteGroup = async (groupId) => {
        if (window.confirm('Are you sure you want to delete this group? This action is IRREVERSIBLE and will delete all posts, members, and messages.')) {
            try {
                await socialApi.deleteGroup(groupId);
                fetchGroups();
            } catch (err) {
                alert(err.message || 'Delete failed');
            }
        }
    };

    const filteredGroups = groups.filter(g => 
        g.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        g.category.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (isLoading) {
        return (
             <div className="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 className="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p className="text-slate-500 text-xs font-black uppercase tracking-widest">Loading Communities...</p>
            </div>
        );
    }

    return (
        <div className="space-y-8 animate-in fade-in duration-500">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-black dark:text-white tracking-tight leading-none mb-2">Social Hubs</h1>
                    <p className="text-xs text-slate-500 font-bold uppercase tracking-widest">Platform Community Management</p>
                </div>
                <button 
                    onClick={() => setShowCreateModal(true)}
                    className="btn-primary px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 shadow-xl shadow-primary-500/20"
                >
                    <Plus className="w-4 h-4" />
                    Create New Hub
                </button>
            </div>

            {/* Stats Overview */}
            <div className="grid grid-cols-1 sm:grid-cols-4 gap-6">
                {[
                    { label: 'Total Hubs', value: groups.length, icon: Globe, color: 'text-blue-500' },
                    { label: 'Total Members', value: groups.reduce((acc, g) => acc + (g.member_count || 0), 0), icon: Users, color: 'text-emerald-500' },
                    { label: 'Total Posts', value: '---', icon: MessageSquare, color: 'text-amber-500' },
                    { label: 'Active Today', value: '---', icon: ShieldCheck, color: 'text-primary-500' },
                ].map((stat, i) => (
                    <div key={i} className="card p-6 bg-white dark:bg-[#0B1120] border-slate-200 dark:border-slate-800">
                        <div className="flex items-center gap-4">
                            <div className={`p-3 bg-slate-50 dark:bg-white/5 rounded-2xl ${stat.color}`}>
                                <stat.icon className="w-5 h-5" />
                            </div>
                            <div>
                                <p className="text-xl font-black dark:text-white leading-none mb-1">{stat.value}</p>
                                <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest">{stat.label}</p>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Toolbar */}
            <div className="flex flex-col sm:flex-row items-center gap-4 bg-white dark:bg-[#0B1120] p-4 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div className="relative flex-1 w-full">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input 
                        type="text"
                        placeholder="Search hubs by name or category..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl pl-11 pr-4 py-3 text-sm focus:ring-2 focus:ring-primary-500 transition-all"
                    />
                </div>
                <div className="flex items-center gap-2 w-full sm:w-auto">
                    <button className="flex-1 sm:flex-none px-6 py-3 bg-slate-50 dark:bg-white/5 rounded-2xl text-xs font-black text-slate-500 uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-slate-100 dark:hover:bg-white/10 transition-all">
                        <Filter className="w-4 h-4" />
                        Filter
                    </button>
                </div>
            </div>

            {/* Groups Table */}
            <div className="card overflow-hidden border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120]">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="border-b border-slate-100 dark:border-slate-800">
                                <th className="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Hub Name</th>
                                <th className="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                                <th className="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Members</th>
                                <th className="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Privacy</th>
                                <th className="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50 dark:divide-white/5">
                            {filteredGroups.map(group => (
                                <tr key={group.id} className="group hover:bg-slate-50/50 dark:hover:bg-white/1 transition-all">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className={`w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center text-white font-black text-xs shadow-lg shadow-primary-500/10`}>
                                                {group.icon_initials || group.name[0]}
                                            </div>
                                            <div>
                                                <p className="text-sm font-black dark:text-white leading-none mb-1">{group.name}</p>
                                                <p className="text-[10px] text-slate-500 font-bold">/{group.slug}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className="px-2.5 py-1 bg-slate-100 dark:bg-white/5 text-[10px] font-black uppercase tracking-widest text-slate-500 rounded-lg">
                                            {group.category}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-2">
                                            <Users className="w-3.5 h-3.5 text-slate-400" />
                                            <span className="text-xs font-bold dark:text-slate-300">{group.member_count}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        {group.is_private ? (
                                            <span className="flex items-center gap-1.5 text-amber-500 text-[10px] font-black uppercase tracking-widest">
                                                <Lock className="w-3 h-3" /> Private
                                            </span>
                                        ) : (
                                            <span className="flex items-center gap-1.5 text-emerald-500 text-[10px] font-black uppercase tracking-widest">
                                                <Globe className="w-3 h-3" /> Public
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button 
                                                onClick={() => {
                                                    setSelectedGroup(group);
                                                    setShowAssignModal(true);
                                                }}
                                                className="p-2 text-slate-400 hover:text-primary-500 transition-colors"
                                                title="Assign Manager"
                                            >
                                                <UserPlus className="w-4 h-4" />
                                            </button>
                                            <Link to={`/social/groups/${group.slug}`} className="p-2 text-slate-400 hover:text-primary-500 transition-colors">
                                                <ExternalLink className="w-4 h-4" />
                                            </Link>
                                            <button onClick={() => handleDeleteGroup(group.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {filteredGroups.length === 0 && (
                    <div className="py-20 text-center">
                        <AlertCircle className="w-12 h-12 text-slate-300 mx-auto mb-4" />
                        <p className="text-slate-500 text-sm font-medium">No hubs found matching your search.</p>
                    </div>
                )}
            </div>

            {/* Create Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 backdrop-blur-md bg-slate-900/40 animate-in fade-in duration-300">
                    <div className="bg-white dark:bg-[#0B1120] w-full max-w-lg rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden animate-in zoom-in-95 duration-300">
                        <div className="p-8 border-b border-slate-50 dark:border-white/5 flex items-center justify-between">
                            <div>
                                <h3 className="text-xl font-black dark:text-white tracking-tight">Establish New Hub</h3>
                                <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Configure community foundation</p>
                            </div>
                            <button onClick={() => setShowCreateModal(false)} className="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                                <X className="w-6 h-6" />
                            </button>
                        </div>
                        
                        <form onSubmit={handleCreateGroup} className="p-8 space-y-6">
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Hub Name</label>
                                <input 
                                    required
                                    type="text"
                                    value={newGroup.name}
                                    onChange={(e) => setNewGroup({...newGroup, name: e.target.value})}
                                    className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl px-5 py-3 text-sm dark:text-white focus:ring-2 focus:ring-primary-500"
                                    placeholder="e.g. Web Developers TUM"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Category</label>
                                    <select 
                                        value={newGroup.category}
                                        onChange={(e) => setNewGroup({...newGroup, category: e.target.value})}
                                        className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl px-5 py-3 text-sm dark:text-white focus:ring-2 focus:ring-primary-500"
                                    >
                                        <option>Tech</option>
                                        <option>Sports</option>
                                        <option>Arts</option>
                                        <option>Career</option>
                                        <option>Campus Life</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Privacy</label>
                                    <select 
                                        value={newGroup.is_private ? '1' : '0'}
                                        onChange={(e) => setNewGroup({...newGroup, is_private: e.target.value === '1'})}
                                        className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl px-5 py-3 text-sm dark:text-white focus:ring-2 focus:ring-primary-500"
                                    >
                                        <option value="0">Public Hub</option>
                                        <option value="1">Private Hub</option>
                                    </select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Initial Description</label>
                                <textarea 
                                    value={newGroup.description}
                                    onChange={(e) => setNewGroup({...newGroup, description: e.target.value})}
                                    className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl p-5 text-sm dark:text-white focus:ring-2 focus:ring-primary-500 min-h-[100px]"
                                    placeholder="What is this hub about?"
                                />
                            </div>

                            <div className="flex gap-4 pt-4">
                                <button 
                                    type="button"
                                    onClick={() => setShowCreateModal(false)}
                                    className="flex-1 px-8 py-3 rounded-2xl text-xs font-black text-slate-500 uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-white/5 transition-all"
                                >
                                    Discard
                                </button>
                                <button 
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="flex-1 btn-primary px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest shadow-glow shadow-primary-500/20 disabled:opacity-50 flex items-center justify-center gap-2"
                                >
                                    {isSubmitting && <Loader2 className="w-4 h-4 animate-spin" />}
                                    Create Hub
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Assign Manager Modal */}
            {showAssignModal && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 backdrop-blur-md bg-slate-900/40 animate-in fade-in duration-300">
                    <div className="bg-white dark:bg-[#0B1120] w-full max-w-md rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden animate-in zoom-in-95 duration-300">
                        <div className="p-8 border-b border-slate-50 dark:border-white/5 flex items-center justify-between">
                            <div>
                                <h3 className="text-xl font-black dark:text-white tracking-tight">Assign Manager</h3>
                                <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Designate lead for {selectedGroup?.name}</p>
                            </div>
                            <button onClick={() => setShowAssignModal(false)} className="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                                <X className="w-6 h-6" />
                            </button>
                        </div>
                        
                        <div className="p-8 space-y-6">
                            <div className="space-y-2">
                                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1">Search Users</label>
                                <div className="relative">
                                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <input 
                                        type="text"
                                        value={userSearch}
                                        onChange={(e) => handleSearchUsers(e.target.value)}
                                        className="w-full bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-slate-800 rounded-2xl pl-11 pr-4 py-3 text-sm dark:text-white focus:ring-2 focus:ring-primary-500"
                                        placeholder="Name or email..."
                                    />
                                    {isSearchingUsers && (
                                        <div className="absolute right-4 top-1/2 -translate-y-1/2">
                                            <Loader2 className="w-4 h-4 text-primary-500 animate-spin" />
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2 max-h-[300px] overflow-y-auto pr-2 no-scrollbar">
                                {searchResults.map(u => (
                                    <button 
                                        key={u.id}
                                        onClick={() => handleAssignManager(u.id)}
                                        disabled={isSubmitting}
                                        className="w-full flex items-center justify-between p-4 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-primary-500/50 hover:bg-primary-500/5 transition-all text-left group"
                                    >
                                        <div>
                                            <p className="text-sm font-black dark:text-white leading-none mb-1 group-hover:text-primary-500 transition-colors">{u.firstname} {u.lastname}</p>
                                            <p className="text-[10px] text-slate-500 font-bold truncate">{u.email}</p>
                                        </div>
                                        <div className="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            {u.role}
                                        </div>
                                    </button>
                                ))}
                                {userSearch && !isSearchingUsers && searchResults.length === 0 && (
                                    <div className="text-center py-6">
                                        <p className="text-slate-500 text-[10px] font-black uppercase tracking-widest">No users found</p>
                                    </div>
                                )}
                                {!userSearch && (
                                    <div className="text-center py-6">
                                        <p className="text-slate-500 text-[10px] font-black uppercase tracking-widest">Type to search for users</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
