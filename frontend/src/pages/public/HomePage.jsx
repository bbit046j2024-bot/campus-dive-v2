import { Link } from 'react-router-dom';
import { ArrowRight, CheckCircle, Users, BookOpen, MessageSquare, Star, ChevronRight, GraduationCap, FileText, BarChart3, Shield } from 'lucide-react';

const steps = [
    {
        number: '01',
        title: 'Create Your Profile',
        desc: 'Sign up and complete your student profile with your academic details, department, and personal information.',
        icon: GraduationCap,
        color: 'bg-indigo-500',
    },
    {
        number: '02',
        title: 'Submit Documents',
        desc: 'Upload your resume, transcripts, and certifications through our secure document management portal.',
        icon: FileText,
        color: 'bg-purple-500',
    },
    {
        number: '03',
        title: 'Track Your Progress',
        desc: 'Monitor your application in real-time — from submission through review, interview scheduling, and final decision.',
        icon: BarChart3,
        color: 'bg-emerald-500',
    },
    {
        number: '04',
        title: 'Join the Community',
        desc: 'Connect with fellow students, join groups, share ideas, and grow your network on the Social Hub.',
        icon: Users,
        color: 'bg-amber-500',
    },
];

const testimonials = [
    {
        quote: "Campus Dive made the entire recruitment process transparent and stress-free. I could track every step of my application without constantly emailing the admin team.",
        name: "Amara Osei",
        role: "Computer Science, Class of 2025",
        initials: "AO",
        color: "bg-indigo-500",
    },
    {
        quote: "The Social Hub is fantastic — I found my study group here, got mentorship from seniors, and landed my internship through connections I made on the platform.",
        name: "Brian Mutua",
        role: "Business IT, Class of 2025",
        initials: "BM",
        color: "bg-purple-500",
    },
    {
        quote: "As an admin, the dashboard saves us hours every week. Document tracking, interview scheduling, and bulk actions — all in one place.",
        name: "Dr. Faith Korir",
        role: "Recruitment Coordinator, TUM",
        initials: "FK",
        color: "bg-emerald-500",
    },
];

const features = [
    { icon: Shield, title: 'Secure & Verified', desc: 'All documents are securely stored and verified by our admin team before processing.' },
    { icon: BarChart3, title: 'Live Application Tracking', desc: 'Know exactly where your application stands at every stage of the process.' },
    { icon: MessageSquare, title: 'Direct Communication', desc: 'Chat directly with recruiters and support staff without leaving the platform.' },
    { icon: Users, title: 'Student Community', desc: 'Join groups, share posts, and build your campus network on the Social Hub.' },
    { icon: BookOpen, title: 'Resource Library', desc: 'Access guides, tips, and templates to help you put your best application forward.' },
    { icon: CheckCircle, title: 'Fast Decisions', desc: 'Automated workflows mean faster review cycles and quicker responses for students.' },
];

export default function HomePage() {
    return (
        <div className="overflow-hidden">

            {/* ── HERO ─────────────────────────────────────────────────────── */}
            <section className="relative min-h-[90vh] flex items-center">
                {/* Background gradient */}
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-950 via-indigo-900 to-purple-900 -z-10" />
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(99,102,241,0.3)_0%,transparent_60%)] -z-10" />
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(168,85,247,0.2)_0%,transparent_60%)] -z-10" />

                {/* Floating dots pattern */}
                <div className="absolute inset-0 opacity-10 -z-10"
                    style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '40px 40px' }}
                />

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center w-full">
                    {/* Left — Text */}
                    <div className="animate-fade-in">
                        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/20 text-indigo-200 text-xs font-bold uppercase tracking-widest mb-8">
                            <GraduationCap className="w-4 h-4" />
                            <span>Campus Recruitment & Management</span>
                        </div>

                        <h1 className="text-5xl md:text-6xl lg:text-7xl font-black text-white leading-tight tracking-tight mb-6">
                            Find where <br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-purple-300">you belong.</span>
                        </h1>

                        <p className="text-lg text-indigo-200 leading-relaxed mb-10 max-w-lg font-medium">
                            Campus Dive connects talented students with opportunities — through a seamless recruitment process, live application tracking, and a vibrant campus community.
                        </p>

                        <div className="flex flex-col sm:flex-row gap-4">
                            <Link
                                to="/register"
                                className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-indigo-900 font-black text-sm uppercase tracking-widest rounded-2xl hover:bg-indigo-50 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5"
                            >
                                Apply Now <ArrowRight className="w-4 h-4" />
                            </Link>
                            <Link
                                to="/about"
                                className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 border border-white/20 text-white font-bold text-sm rounded-2xl hover:bg-white/20 transition-all"
                            >
                                Learn More
                            </Link>
                        </div>

                        {/* Social proof */}
                        <div className="flex items-center gap-8 mt-12 pt-8 border-t border-white/10">
                            <div>
                                <p className="text-3xl font-black text-white">500+</p>
                                <p className="text-xs text-indigo-300 font-bold uppercase tracking-widest">Students Enrolled</p>
                            </div>
                            <div className="w-px h-10 bg-white/10" />
                            <div>
                                <p className="text-3xl font-black text-white">95%</p>
                                <p className="text-xs text-indigo-300 font-bold uppercase tracking-widest">Placement Rate</p>
                            </div>
                            <div className="w-px h-10 bg-white/10" />
                            <div>
                                <p className="text-3xl font-black text-white">48h</p>
                                <p className="text-xs text-indigo-300 font-bold uppercase tracking-widest">Avg. Review Time</p>
                            </div>
                        </div>
                    </div>

                    {/* Right — Image */}
                    <div className="relative animate-fade-in delay-200 hidden lg:block">
                        <div className="relative rounded-3xl overflow-hidden shadow-2xl shadow-indigo-900/50 border border-white/10">
                            <img
                                src="/campus.png"
                                alt="Students at campus"
                                className="w-full h-[520px] object-cover object-center"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-indigo-950/60 via-transparent to-transparent" />

                            {/* Floating card */}
                            <div className="absolute bottom-6 left-6 right-6 bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-5">
                                <div className="flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center shrink-0">
                                        <CheckCircle className="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p className="text-white font-black text-sm">Application Approved</p>
                                        <p className="text-indigo-200 text-xs font-medium">Interview scheduled for next week</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Decorative element */}
                        <div className="absolute -top-6 -right-6 w-32 h-32 bg-purple-500/20 rounded-full blur-3xl" />
                        <div className="absolute -bottom-6 -left-6 w-32 h-32 bg-indigo-500/20 rounded-full blur-3xl" />
                    </div>
                </div>
            </section>

            {/* ── HOW IT WORKS ─────────────────────────────────────────────── */}
            <section className="py-24 bg-white dark:bg-surface-950">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <p className="text-xs font-black uppercase tracking-[0.3em] text-primary-500 mb-3">Simple Process</p>
                        <h2 className="text-4xl md:text-5xl font-black text-surface-900 dark:text-white tracking-tight mb-4">
                            How it works
                        </h2>
                        <p className="text-surface-500 dark:text-surface-400 text-lg max-w-xl mx-auto font-medium leading-relaxed">
                            From first click to final decision — here's your journey on Campus Dive.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        {steps.map((step, i) => (
                            <div key={step.number} className="relative group">
                                {i < steps.length - 1 && (
                                    <div className="hidden lg:block absolute top-8 left-full w-full h-px bg-gradient-to-r from-surface-200 to-transparent dark:from-surface-800 z-0 -translate-y-px" />
                                )}
                                <div className="relative z-10 p-6 rounded-3xl border border-surface-100 dark:border-surface-800 bg-surface-50 dark:bg-surface-900 hover:border-primary-500/30 hover:shadow-lg hover:shadow-primary-500/5 transition-all group-hover:-translate-y-1">
                                    <div className="flex items-center justify-between mb-6">
                                        <div className={`w-14 h-14 rounded-2xl ${step.color} flex items-center justify-center shadow-lg`}>
                                            <step.icon className="w-7 h-7 text-white" />
                                        </div>
                                        <span className="text-4xl font-black text-surface-100 dark:text-surface-800">{step.number}</span>
                                    </div>
                                    <h3 className="text-lg font-black text-surface-900 dark:text-white mb-2 tracking-tight">{step.title}</h3>
                                    <p className="text-sm text-surface-500 dark:text-surface-400 leading-relaxed font-medium">{step.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── FEATURES ─────────────────────────────────────────────────── */}
            <section className="py-24 bg-surface-50 dark:bg-surface-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                        {/* Left text */}
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.3em] text-primary-500 mb-3">Platform Features</p>
                            <h2 className="text-4xl md:text-5xl font-black text-surface-900 dark:text-white tracking-tight mb-6">
                                Everything you need, <br />
                                <span className="text-primary-600">in one place.</span>
                            </h2>
                            <p className="text-surface-500 dark:text-surface-400 text-lg leading-relaxed font-medium mb-10">
                                Campus Dive is built specifically for student recruitment — no generic tools, no complicated setups. Just a clean, powerful platform designed around your needs.
                            </p>
                            <Link
                                to="/register"
                                className="inline-flex items-center gap-2 px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-black text-sm uppercase tracking-widest rounded-2xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5"
                            >
                                Get Started Free <ChevronRight className="w-4 h-4" />
                            </Link>
                        </div>

                        {/* Right feature grid */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {features.map((f) => (
                                <div key={f.title} className="p-5 rounded-2xl bg-white dark:bg-surface-800 border border-surface-100 dark:border-surface-700 hover:border-primary-500/30 transition-all hover:shadow-md">
                                    <div className="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center mb-3">
                                        <f.icon className="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <h4 className="font-black text-surface-900 dark:text-white text-sm mb-1">{f.title}</h4>
                                    <p className="text-xs text-surface-500 dark:text-surface-400 leading-relaxed font-medium">{f.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* ── TESTIMONIALS ─────────────────────────────────────────────── */}
            <section className="py-24 bg-white dark:bg-surface-950">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <p className="text-xs font-black uppercase tracking-[0.3em] text-primary-500 mb-3">Testimonials</p>
                        <h2 className="text-4xl md:text-5xl font-black text-surface-900 dark:text-white tracking-tight">
                            We help students and <br className="hidden md:block" />
                            <span className="text-primary-600">counselors thrive.</span>
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {testimonials.map((t) => (
                            <div key={t.name} className="p-8 rounded-3xl bg-surface-50 dark:bg-surface-900 border border-surface-100 dark:border-surface-800 hover:border-primary-500/20 hover:shadow-lg transition-all flex flex-col">
                                <div className="flex items-center gap-1 mb-6">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} className="w-4 h-4 fill-amber-400 text-amber-400" />
                                    ))}
                                </div>
                                <p className="text-surface-600 dark:text-surface-300 text-sm leading-relaxed font-medium flex-1 mb-8">
                                    "{t.quote}"
                                </p>
                                <div className="flex items-center gap-3">
                                    <div className={`w-10 h-10 rounded-full ${t.color} flex items-center justify-center text-white font-black text-xs shrink-0`}>
                                        {t.initials}
                                    </div>
                                    <div>
                                        <p className="font-black text-surface-900 dark:text-white text-sm">{t.name}</p>
                                        <p className="text-xs text-surface-500 dark:text-surface-400 font-medium">{t.role}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── CTA ──────────────────────────────────────────────────────── */}
            <section className="py-24 bg-gradient-to-br from-indigo-600 to-purple-700 relative overflow-hidden">
                <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '32px 32px' }} />
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    <h2 className="text-4xl md:text-5xl font-black text-white tracking-tight mb-6">
                        Ready to dive in?
                    </h2>
                    <p className="text-indigo-200 text-lg font-medium mb-10 max-w-xl mx-auto leading-relaxed">
                        Join thousands of students at the Technical University of Mombasa who have simplified their campus recruitment journey.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            to="/register"
                            className="inline-flex items-center justify-center gap-2 px-10 py-4 bg-white text-indigo-900 font-black text-sm uppercase tracking-widest rounded-2xl hover:bg-indigo-50 transition-all shadow-xl hover:-translate-y-0.5"
                        >
                            Create Free Account <ArrowRight className="w-4 h-4" />
                        </Link>
                        <Link
                            to="/login"
                            className="inline-flex items-center justify-center gap-2 px-10 py-4 bg-white/10 border border-white/20 text-white font-bold text-sm rounded-2xl hover:bg-white/20 transition-all"
                        >
                            Sign In
                        </Link>
                    </div>
                </div>
            </section>

        </div>
    );
}
