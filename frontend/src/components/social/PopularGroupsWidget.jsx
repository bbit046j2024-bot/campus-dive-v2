import { useState } from 'react';

const mockGroups = [
    { id: 1, name: 'Web Devs TUM', initials: 'WD', color: 'bg-blue-500', joined: true },
    { id: 2, name: 'AI/ML Research', initials: 'AI', color: 'bg-emerald-500', joined: false },
    { id: 3, name: 'Design Guild', initials: 'DG', color: 'bg-rose-500', joined: true },
    { id: 4, name: 'Cyber Security', initials: 'CS', color: 'bg-amber-500', joined: false },
    { id: 5, name: 'Open Source Hub', initials: 'OS', color: 'bg-indigo-500', joined: false },
    { id: 6, name: 'Game Dev Society', initials: 'GD', color: 'bg-purple-500', joined: false },
];

export default function PopularGroupsWidget() {
    return (
        <div className="card p-6 border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120]">
            <h3 className="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase mb-6">
                Popular Groups
            </h3>
            
            <div className="space-y-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                {mockGroups.map(group => (
                    <div key={group.id} className="flex items-center justify-between gap-3 group">
                        <div className="flex items-center gap-3 min-w-0">
                            <div className={`w-10 h-10 rounded-xl ${group.color} flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-sm transition-transform group-hover:scale-105`}>
                                {group.initials}
                            </div>
                            <div className="min-w-0">
                                <p className="text-sm font-bold truncate dark:text-white leading-tight">
                                    {group.name}
                                </p>
                                <p className="text-[10px] text-slate-500 font-medium">1.2k members</p>
                            </div>
                        </div>
                        
                        <button 
                            className={`shrink-0 text-[10px] px-3 py-1.5 rounded-lg font-bold transition-all ${
                                group.joined 
                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-default' 
                                : 'bg-primary-500/10 text-primary-500 hover:bg-primary-500 hover:text-white'
                            }`}
                        >
                            {group.joined ? 'Joined' : 'Join'}
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
}
