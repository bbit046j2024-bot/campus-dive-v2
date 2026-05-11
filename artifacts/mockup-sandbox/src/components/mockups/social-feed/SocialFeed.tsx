import { useState } from "react";
import {
  Heart, MessageSquare, Share2, MoreHorizontal,
  Image as ImageIcon, Video, Calendar, Flame,
  Clock, Lightbulb, Megaphone, Bookmark,
  ChevronDown, Users, PlusCircle, Send,
  Pin, Award, X, TrendingUp
} from "lucide-react";

type PostType = "student" | "project" | "announcement";
type FilterType = "latest" | "popular" | "projects" | "announcements";

interface Post {
  id: number;
  type: PostType;
  author: string;
  authorInitials: string;
  authorAvatarColor: string;
  group: string;
  groupColor: string;
  time: string;
  content: string;
  image?: string;
  likes: number;
  comments: number;
  liked: boolean;
  pinned?: boolean;
  projectTags?: string[];
  announcementLabel?: string;
}

const POSTS: Post[] = [
  {
    id: 1,
    type: "announcement",
    author: "Campus Dive Admin",
    authorInitials: "CD",
    authorAvatarColor: "from-indigo-500 to-purple-600",
    group: "Official",
    groupColor: "bg-indigo-500/15 text-indigo-400 border border-indigo-500/30",
    time: "2h ago",
    content: "📣 Internship Fair 2025 is happening this Friday, 10am–4pm at the Main Hall. Over 40 companies will be present including Safaricom, KCB, Microsoft, and Deloitte. Bring your updated CV and dress professionally. Register at the link below.",
    likes: 128,
    comments: 34,
    liked: false,
    pinned: true,
    announcementLabel: "Campus Event",
  },
  {
    id: 2,
    type: "project",
    author: "Kevin Mwangi",
    authorInitials: "KM",
    authorAvatarColor: "from-emerald-500 to-teal-600",
    group: "Computer Science Hub",
    groupColor: "bg-emerald-500/15 text-emerald-400 border border-emerald-500/30",
    time: "4h ago",
    content: "Just shipped my final year project — a real-time crop disease detection system using computer vision. Trained on 50,000+ images of maize, tomato, and sorghum. Achieved 94.2% accuracy on the test set. Full write-up and demo in the links below!",
    image: "https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=800&q=80",
    likes: 87,
    comments: 22,
    liked: true,
    projectTags: ["Python", "TensorFlow", "OpenCV", "FastAPI"],
  },
  {
    id: 3,
    type: "student",
    author: "Amina Osei",
    authorInitials: "AO",
    authorAvatarColor: "from-rose-500 to-pink-600",
    group: "Business & Commerce Hub",
    groupColor: "bg-rose-500/15 text-rose-400 border border-rose-500/30",
    time: "6h ago",
    content: "Just got off a call with my mentor from the KCB internship programme — she gave me the most useful advice about navigating corporate culture as a first-year analyst. The biggest takeaway: show up curious, not just competent. 🧠\n\nHappy to share more if anyone's preparing for interviews this season.",
    likes: 54,
    comments: 18,
    liked: false,
  },
  {
    id: 4,
    type: "project",
    author: "Brian Otieno",
    authorInitials: "BO",
    authorAvatarColor: "from-amber-500 to-orange-500",
    group: "Software Engineering Hub",
    groupColor: "bg-amber-500/15 text-amber-400 border border-amber-500/30",
    time: "1d ago",
    content: "Built a campus matatu tracker — pulls live GPS data and shows real-time ETAs for the routes that serve our campus. Currently covers Route 33, 46, and 58. Try it out at the link in my profile.",
    image: "https://images.unsplash.com/photo-1586276393635-5ecd8a851acc?w=800&q=80",
    likes: 203,
    comments: 41,
    liked: false,
    projectTags: ["React Native", "Node.js", "Google Maps API"],
  },
];

const GROUPS = [
  { id: 1, name: "Computer Science Hub", color: "text-emerald-400" },
  { id: 2, name: "Business & Commerce Hub", color: "text-rose-400" },
  { id: 3, name: "Software Engineering Hub", color: "text-amber-400" },
  { id: 4, name: "Career & Internships", color: "text-indigo-400" },
];

const FILTERS: { key: FilterType; label: string; icon: React.ReactNode }[] = [
  { key: "latest", label: "Latest", icon: <Clock className="w-3.5 h-3.5" /> },
  { key: "popular", label: "Popular", icon: <Flame className="w-3.5 h-3.5" /> },
  { key: "projects", label: "Projects", icon: <Lightbulb className="w-3.5 h-3.5" /> },
  { key: "announcements", label: "Announcements", icon: <Megaphone className="w-3.5 h-3.5" /> },
];

function Avatar({ initials, colorClass, size = "md" }: { initials: string; colorClass: string; size?: "sm" | "md" | "lg" }) {
  const s = size === "sm" ? "w-8 h-8 text-xs" : size === "lg" ? "w-12 h-12 text-base" : "w-10 h-10 text-sm";
  return (
    <div className={`${s} rounded-2xl bg-gradient-to-br ${colorClass} flex items-center justify-center text-white font-black flex-shrink-0 shadow-lg`}>
      {initials}
    </div>
  );
}

function ProjectBadges({ tags }: { tags: string[] }) {
  return (
    <div className="flex flex-wrap gap-1.5 mt-3">
      {tags.map(tag => (
        <span key={tag} className="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
          {tag}
        </span>
      ))}
    </div>
  );
}

function PostCard({ post }: { post: Post }) {
  const [liked, setLiked] = useState(post.liked);
  const [likeCount, setLikeCount] = useState(post.likes);
  const [saved, setSaved] = useState(false);

  const handleLike = () => {
    setLiked(l => !l);
    setLikeCount(c => liked ? c - 1 : c + 1);
  };

  const isAnnouncement = post.type === "announcement";
  const isProject = post.type === "project";

  return (
    <article className={`rounded-3xl overflow-hidden transition-all duration-200 hover:translate-y-[-1px] ${
      isAnnouncement
        ? "bg-gradient-to-br from-indigo-950/80 to-slate-900 border border-indigo-500/30 shadow-lg shadow-indigo-500/10"
        : "bg-[#0d1526] border border-white/5 hover:border-white/10 shadow-sm"
    }`}>

      {/* Announcement Banner */}
      {isAnnouncement && (
        <div className="flex items-center gap-2 px-4 pt-3 pb-0">
          <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-500/20 border border-indigo-500/30">
            <Megaphone className="w-3 h-3 text-indigo-400" />
            <span className="text-[9px] font-black uppercase tracking-[0.15em] text-indigo-300">{post.announcementLabel}</span>
          </div>
          {post.pinned && (
            <div className="flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-slate-500">
              <Pin className="w-2.5 h-2.5" />
              Pinned
            </div>
          )}
        </div>
      )}

      {/* Project Banner */}
      {isProject && (
        <div className="flex items-center gap-2 px-4 pt-3 pb-0">
          <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20">
            <Award className="w-3 h-3 text-emerald-400" />
            <span className="text-[9px] font-black uppercase tracking-[0.15em] text-emerald-400">Student Project</span>
          </div>
        </div>
      )}

      {/* Header */}
      <div className="p-4 flex items-start justify-between gap-3">
        <div className="flex items-center gap-3 min-w-0">
          <Avatar initials={post.authorInitials} colorClass={post.authorAvatarColor} />
          <div className="min-w-0">
            <p className="text-sm font-black text-white leading-tight">{post.author}</p>
            <div className="flex items-center gap-1.5 mt-0.5 flex-wrap">
              <span className={`text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full ${post.groupColor}`}>
                {post.group}
              </span>
              <span className="text-[9px] text-slate-600">•</span>
              <span className="text-[9px] text-slate-500 font-bold uppercase tracking-wider">{post.time}</span>
            </div>
          </div>
        </div>
        <button className="p-1.5 text-slate-600 hover:text-slate-400 hover:bg-white/5 rounded-xl transition-colors flex-shrink-0">
          <MoreHorizontal className="w-4 h-4" />
        </button>
      </div>

      {/* Content */}
      <div className="px-4 pb-3">
        <p className={`text-sm leading-relaxed whitespace-pre-wrap ${isAnnouncement ? "text-slate-200" : "text-slate-300"}`}>
          {post.content}
        </p>
        {post.projectTags && <ProjectBadges tags={post.projectTags} />}
      </div>

      {/* Image */}
      {post.image && (
        <div className="mx-4 mb-3 rounded-2xl overflow-hidden border border-white/5 bg-slate-900">
          <img
            src={post.image}
            alt="Post visual"
            className="w-full h-48 object-cover"
          />
        </div>
      )}

      {/* Actions */}
      <div className="px-3 py-2.5 border-t border-white/5 flex items-center gap-1">
        <button
          onClick={handleLike}
          className={`flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex-1 justify-center ${
            liked
              ? "text-rose-400 bg-rose-500/10"
              : "text-slate-500 hover:text-rose-400 hover:bg-rose-500/5"
          }`}
        >
          <Heart className={`w-4 h-4 transition-transform ${liked ? "fill-current scale-110" : ""}`} />
          <span>{likeCount}</span>
        </button>

        <button className="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/5 transition-all flex-1 justify-center">
          <MessageSquare className="w-4 h-4" />
          <span>{post.comments}</span>
        </button>

        <button
          onClick={() => setSaved(s => !s)}
          className={`flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex-1 justify-center ${
            saved ? "text-amber-400 bg-amber-500/10" : "text-slate-500 hover:text-amber-400 hover:bg-amber-500/5"
          }`}
        >
          <Bookmark className={`w-4 h-4 ${saved ? "fill-current" : ""}`} />
          <span className="hidden sm:inline">Save</span>
        </button>

        <button className="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-slate-500 hover:text-emerald-400 hover:bg-emerald-500/5 transition-all flex-1 justify-center">
          <Share2 className="w-4 h-4" />
          <span className="hidden sm:inline">Share</span>
        </button>
      </div>
    </article>
  );
}

function PostComposer({ groups }: { groups: typeof GROUPS }) {
  const [expanded, setExpanded] = useState(false);
  const [content, setContent] = useState("");
  const [selectedGroup, setSelectedGroup] = useState(groups[0]);
  const [charCount, setCharCount] = useState(0);
  const MAX_CHARS = 500;

  const handleContentChange = (v: string) => {
    if (v.length <= MAX_CHARS) {
      setContent(v);
      setCharCount(v.length);
    }
  };

  return (
    <div className="bg-[#0d1526] border border-white/5 rounded-3xl overflow-hidden">
      {/* Collapsed State */}
      {!expanded && (
        <div
          onClick={() => setExpanded(true)}
          className="flex items-center gap-3 p-4 cursor-text group"
        >
          <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-sm flex-shrink-0 shadow-lg">
            JD
          </div>
          <div className="flex-1 px-4 py-2.5 bg-white/5 border border-white/5 group-hover:border-indigo-500/30 rounded-2xl text-slate-500 text-sm font-medium transition-all">
            What's happening on campus today?
          </div>
        </div>
      )}

      {/* Expanded State */}
      {expanded && (
        <div className="p-4">
          <div className="flex items-start gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-sm flex-shrink-0 shadow-lg mt-1">
              JD
            </div>
            <div className="flex-1 space-y-3">
              {/* Group Selector */}
              <div className="flex items-center gap-2">
                <span className="text-[9px] font-black uppercase tracking-widest text-slate-600">Posting to</span>
                <div className="relative">
                  <select
                    value={selectedGroup.id}
                    onChange={e => setSelectedGroup(groups.find(g => g.id === Number(e.target.value)) || groups[0])}
                    className="appearance-none pl-2.5 pr-6 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-black uppercase tracking-widest rounded-full focus:outline-none cursor-pointer"
                  >
                    {groups.map(g => (
                      <option key={g.id} value={g.id} className="bg-slate-900 text-white normal-case">{g.name}</option>
                    ))}
                  </select>
                  <ChevronDown className="absolute right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 text-indigo-400 pointer-events-none" />
                </div>
              </div>

              <textarea
                autoFocus
                value={content}
                onChange={e => handleContentChange(e.target.value)}
                placeholder="Share a project, ask a question, or post an update…"
                className="w-full bg-transparent border-none p-0 focus:ring-0 text-sm text-white placeholder:text-slate-600 resize-none min-h-[80px] outline-none leading-relaxed"
              />

              {/* Char count */}
              <div className="flex justify-end">
                <span className={`text-[9px] font-black uppercase tracking-wider ${charCount > MAX_CHARS * 0.9 ? "text-amber-400" : "text-slate-600"}`}>
                  {charCount}/{MAX_CHARS}
                </span>
              </div>

              {/* Toolbar */}
              <div className="flex items-center justify-between pt-2 border-t border-white/5">
                <div className="flex items-center gap-1">
                  <button className="p-2 rounded-xl text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/10 transition-colors" title="Add photo">
                    <ImageIcon className="w-4 h-4" />
                  </button>
                  <button className="p-2 rounded-xl text-slate-500 hover:text-emerald-400 hover:bg-emerald-500/10 transition-colors" title="Add video">
                    <Video className="w-4 h-4" />
                  </button>
                  <button className="p-2 rounded-xl text-slate-500 hover:text-amber-400 hover:bg-amber-500/10 transition-colors" title="Create event">
                    <Calendar className="w-4 h-4" />
                  </button>
                </div>

                <div className="flex items-center gap-2">
                  <button
                    onClick={() => { setExpanded(false); setContent(""); setCharCount(0); }}
                    className="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    disabled={!content.trim()}
                    className={`flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${
                      content.trim()
                        ? "bg-indigo-600 text-white hover:bg-indigo-500 shadow-lg shadow-indigo-500/20"
                        : "bg-white/5 text-slate-600 cursor-not-allowed"
                    }`}
                  >
                    <Send className="w-3 h-3" />
                    Post
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Quick Actions Bar */}
      {!expanded && (
        <div className="flex items-center gap-0 border-t border-white/5 px-2 py-1.5">
          <button onClick={() => setExpanded(true)} className="flex items-center gap-2 px-3 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/5 transition-colors">
            <ImageIcon className="w-4 h-4 text-indigo-400" />
            Photo
          </button>
          <button onClick={() => setExpanded(true)} className="flex items-center gap-2 px-3 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest text-slate-500 hover:text-emerald-400 hover:bg-emerald-500/5 transition-colors">
            <Video className="w-4 h-4 text-emerald-400" />
            Video
          </button>
          <button onClick={() => setExpanded(true)} className="flex items-center gap-2 px-3 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest text-slate-500 hover:text-amber-400 hover:bg-amber-500/5 transition-colors">
            <Calendar className="w-4 h-4 text-amber-400" />
            Event
          </button>
          <button onClick={() => setExpanded(true)} className="flex items-center gap-2 px-3 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest text-slate-500 hover:text-rose-400 hover:bg-rose-500/5 transition-colors">
            <Award className="w-4 h-4 text-rose-400" />
            Project
          </button>
        </div>
      )}
    </div>
  );
}

function FeedFilters({ active, onChange }: { active: FilterType; onChange: (f: FilterType) => void }) {
  return (
    <div className="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
      {FILTERS.map(f => (
        <button
          key={f.key}
          onClick={() => onChange(f.key)}
          className={`flex items-center gap-1.5 px-3.5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest whitespace-nowrap transition-all ${
            active === f.key
              ? "bg-indigo-600 text-white shadow-lg shadow-indigo-500/20"
              : "bg-white/5 text-slate-500 hover:bg-white/10 hover:text-slate-300 border border-white/5"
          }`}
        >
          {f.icon}
          {f.label}
        </button>
      ))}
    </div>
  );
}

function StatsStrip() {
  return (
    <div className="flex items-center gap-4 px-1">
      <div className="flex items-center gap-1.5">
        <TrendingUp className="w-3.5 h-3.5 text-emerald-400" />
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">
          <span className="text-emerald-400">24</span> posts today
        </span>
      </div>
      <div className="flex items-center gap-1.5">
        <Users className="w-3.5 h-3.5 text-indigo-400" />
        <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">
          <span className="text-indigo-400">142</span> active now
        </span>
      </div>
    </div>
  );
}

export function SocialFeed() {
  const [filter, setFilter] = useState<FilterType>("latest");
  const [page, setPage] = useState(1);
  const POSTS_PER_PAGE = 3;

  const filtered = POSTS.filter(p => {
    if (filter === "projects") return p.type === "project";
    if (filter === "announcements") return p.type === "announcement";
    if (filter === "popular") return [...POSTS].sort((a, b) => b.likes - a.likes);
    return true;
  });

  const visible = filtered.slice(0, page * POSTS_PER_PAGE);
  const hasMore = visible.length < filtered.length;

  return (
    <div className="min-h-screen bg-[#060D1A]">
      {/* Top Nav */}
      <nav className="sticky top-0 z-10 bg-[#060D1A]/90 backdrop-blur-xl border-b border-white/5 px-6 py-3 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
            <span className="text-white font-black text-[10px]">CD</span>
          </div>
          <span className="font-black text-white text-sm tracking-tight">Campus Dive</span>
        </div>
        <div className="flex items-center gap-3">
          <button className="relative p-2 text-slate-500 hover:text-white transition-colors">
            <MessageSquare className="w-4 h-4" />
            <span className="absolute -top-0.5 -right-0.5 w-3.5 h-3.5 bg-indigo-500 rounded-full text-[7px] font-black text-white flex items-center justify-center">3</span>
          </button>
          <div className="w-7 h-7 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-[9px]">JD</div>
        </div>
      </nav>

      {/* Main Layout */}
      <div className="max-w-2xl mx-auto px-4 py-6 space-y-4">

        {/* Composer */}
        <PostComposer groups={GROUPS} />

        {/* Stats */}
        <StatsStrip />

        {/* Filters */}
        <FeedFilters active={filter} onChange={f => { setFilter(f); setPage(1); }} />

        {/* Posts */}
        <div className="space-y-4">
          {visible.map(post => (
            <PostCard key={post.id} post={post} />
          ))}
        </div>

        {/* Load More */}
        {hasMore ? (
          <button
            onClick={() => setPage(p => p + 1)}
            className="w-full flex items-center justify-center gap-2 py-4 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/8 hover:border-indigo-500/30 text-slate-400 hover:text-indigo-400 text-[10px] font-black uppercase tracking-widest transition-all"
          >
            <PlusCircle className="w-4 h-4" />
            Load more posts
            <span className="text-slate-600">({filtered.length - visible.length} remaining)</span>
          </button>
        ) : (
          <div className="text-center py-8">
            <div className="inline-flex flex-col items-center gap-2">
              <div className="w-10 h-10 rounded-2xl bg-white/5 flex items-center justify-center">
                <X className="w-5 h-5 text-slate-600" />
              </div>
              <p className="text-[10px] font-black uppercase tracking-widest text-slate-600">You're all caught up</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
