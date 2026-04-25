import { useState, useEffect } from 'react';
import { Search, Plus, Filter, Users as UsersIcon, MessageSquare, Loader2, Compass } from 'lucide-react';
import { Link } from 'react-router-dom';
import { socialApi } from '../../api/social';

export default function GroupsPage() {
    const [groups, setGroups] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        fetchGroups();
    }, []);

    const fetchGroups = async () => {
        setIsLoading(true);
        try {
            const res = await socialApi.getGroups();
            setGroups(res.data || []);
        } catch (err) {
            console.error('Failed to fetch groups:', err);
        } finally {
            setIsLoading(false);
        }
    };

    const myGroupsList = groups.filter(g => g.user_role);
    const discoveryList = groups.filter(g => !g.user_role && g.name.toLowerCase().includes(searchQuery.toLowerCase()));

    if (isLoading && groups.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 className="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p className="text-slate-500 text-xs font-black uppercase tracking-widest">Loading Communities...</p>
            </div>
        );
    }

    return (
        <div className="space-y-12 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-black tracking-tight dark:text-white">Social Groups</h1>
                    <p className="text-sm text-slate-500 font-medium">Connect with fellow developers in your interest areas</p>
                </div>
                <button 
                    onClick={() => window.alert('Group creation protocol initiated. Contact Admin for deployment.')}
                    className="btn-primary rounded-full px-6 h-12 text-xs font-black uppercase tracking-widest shadow-glow shadow-primary-500/20 active:scale-95 transition-transform"
                >
                    <Plus className="w-5 h-5" />
                    <span>Create Group</span>
                </button>
            </div>

            {/* My Groups */}
            {myGroupsList.length > 0 && (
                <section>
                    <h3 className="text-[10px] font-black tracking-[0.3em] text-slate-400 uppercase mb-6 px-2">My Groups</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {myGroupsList.map(group => (
                            <GroupCard key={group.id} group={group} onUpdate={fetchGroups} />
                        ))}
                    </div>
                </section>
            )}

            {/* Discover Groups */}
            <section>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <h3 className="text-[10px] font-black tracking-[0.3em] text-slate-400 uppercase px-2">Discover Communities</h3>
                    <div className="flex items-center gap-2">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input 
                                type="text" 
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Search groups..." 
                                className="pl-9 pr-4 py-2 bg-white dark:bg-[#0B1120] border border-slate-200 dark:border-slate-800 rounded-full text-xs font-medium dark:text-white focus:ring-1 focus:ring-primary-500 w-full sm:w-64 transition-all"
                            />
                        </div>
                        <button className="p-2 bg-white dark:bg-[#0B1120] border border-slate-200 dark:border-slate-800 rounded-full text-slate-400 hover:text-primary-500 transition-colors">
                            <Filter className="w-4 h-4" />
                        </button>
                    </div>
                </div>
                
                {discoveryList.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {discoveryList.map(group => (
                            <GroupCard key={group.id} group={group} onUpdate={fetchGroups} />
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-20 card border-dashed border-2 bg-transparent border-slate-200 dark:border-slate-800">
                        <div className="w-16 h-16 bg-slate-50 dark:bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                            <Compass className="w-8 h-8 text-slate-400" />
                        </div>
                        <h3 className="text-lg font-black dark:text-white mb-2 underline underline-offset-8 decoration-primary-500/30">No new groups found</h3>
                        <p className="text-sm text-slate-500 max-w-xs mx-auto">Try a different search term or check back later.</p>
                    </div>
                )}
            </section>
        </div>
    );
}

function GroupCard({ group, onUpdate }) {
    const [isJoining, setIsJoining] = useState(false);
    const isJoined = !!group.user_role;
    
    const handleJoin = async () => {
        setIsJoining(true);
        try {
            await socialApi.joinGroup(group.id);
            onUpdate();
        } catch (err) {
            alert(err.message || 'Failed to join group');
        } finally {
            setIsJoining(false);
        }
    };

    const coverGradient = `from-[${group.cover_color || '#6366f1'}] to-[${group.cover_color_end || '#8b5cf6'}]`;

    return (
        <div className="card-premium group overflow-hidden border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120]">
            <div className={`h-24 bg-gradient-to-br ${coverGradient} relative overflow-hidden`}>
                <div className="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700" />
            </div>
            
            <div className="p-6 pt-0 -mt-8 relative z-10 text-center">
                <div className={`w-16 h-16 rounded-[1.25rem] bg-gradient-to-br ${coverGradient} border-4 border-white dark:border-[#0B1120] flex items-center justify-center text-white text-xl font-black mx-auto mb-4 shadow-xl`}>
                    {group.icon_initials || group.name[0]}
                </div>
                
                <h4 className="text-lg font-black dark:text-white leading-tight mb-1 group-hover:text-primary-500 transition-colors">
                    {group.name}
                </h4>
                
                <div className="flex items-center justify-center gap-4 py-4 mb-6 border-b border-slate-50 dark:border-white/5">
                    <div className="text-center">
                        <p className="text-sm font-black dark:text-white">{group.member_count || 0}</p>
                        <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Members</p>
                    </div>
                    <div className="text-center">
                        <p className="text-sm font-black dark:text-white">{group.category || 'General'}</p>
                        <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Category</p>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <Link 
                        to={`/social/groups/${group.slug}`}
                        className="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10 transition-all text-center"
                    >
                        View Hub
                    </Link>
                    {!isJoined && (
                        <button 
                            onClick={handleJoin}
                            disabled={isJoining}
                            className="flex-1 px-4 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-glow shadow-primary-500/10 hover:-translate-y-0.5 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            {isJoining && <Loader2 className="w-3 h-3 animate-spin" />}
                            Join Hub
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
