import { Trophy, Calendar, Rocket } from 'lucide-react';

const announcements = [
    { 
        id: 1,
        emoji: <Trophy className="w-4 h-4" />, 
        title: 'Hackathon 2025', 
        color: 'text-primary-500',
        bgColor: 'bg-primary-500/10',
        desc: 'Register now for the annual Campus Hack — 48hrs, prize pool.', 
        time: '3 days ago' 
    },
    { 
        id: 2,
        emoji: <Calendar className="w-4 h-4" />, 
        title: 'Workshop Series', 
        color: 'text-emerald-500',
        bgColor: 'bg-emerald-500/10',
        desc: 'DevOps bootcamp starting Jan 15. Free for all TUM students.', 
        time: '1 week ago' 
    },
    { 
        id: 3,
        emoji: <Rocket className="w-4 h-4" />, 
        title: 'Platform Update', 
        color: 'text-amber-500',
        bgColor: 'bg-amber-500/10',
        desc: 'Campus Dive v2.0 is live. Explore new collaboration features.', 
        time: '2 weeks ago' 
    }
];

export default function AnnouncementsWidget() {
    return (
        <div className="card p-6 border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B1120]">
            <h3 className="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase mb-6">
                Special Announcements
            </h3>
            
            <div className="space-y-6">
                {announcements.map(item => (
                    <div key={item.id} className="relative pl-4 border-l-2 border-slate-100 dark:border-slate-800">
                        <div className="flex items-center gap-2 mb-2">
                            <div className={`${item.color} ${item.bgColor} p-1.5 rounded-lg`}>
                                {item.emoji}
                            </div>
                            <h4 className="text-xs font-black tracking-tight dark:text-white uppercase leading-none">
                                {item.title}
                            </h4>
                        </div>
                        <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-2 line-clamp-2">
                            {item.desc}
                        </p>
                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            {item.time}
                        </span>
                    </div>
                ))}
            </div>
            
            <button className="w-full mt-6 py-2.5 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 hover:text-primary-500 transition-colors uppercase">
                View All Announcements
            </button>
        </div>
    );
}
