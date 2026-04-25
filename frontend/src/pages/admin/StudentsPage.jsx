import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { StatusBadge, UserAvatar } from '../../components/ui/StatusBadge';
import { ConfirmModal } from '../../components/ui/Modal';
import { SkeletonTable } from '../../components/ui/Skeleton';
import EmptyState from '../../components/ui/EmptyState';
import { Search, Filter, ChevronLeft, ChevronRight, MoreVertical, Check, X, Trash2, Eye, Users, MessageSquare, Shield, UserCheck } from 'lucide-react';

export default function StudentsPage() {
    const [students, setStudents] = useState([]);
    const [pagination, setPagination] = useState({ page: 1, pages: 1, total: 0 });
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [selected, setSelected] = useState(new Set());
    const [actionMenu, setActionMenu] = useState(null);
    const [confirmModal, setConfirmModal] = useState(null);
    const toast = useToast();
    const navigate = useNavigate();

    const fetchStudents = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ page, limit: 20 });
            if (search) params.set('search', search);
            if (statusFilter) params.set('status', statusFilter);
            const res = await api.get(`/admin/students?${params}`);
            setStudents(res.data.data);
            setPagination(res.data.pagination);
        } catch (err) {
            toast.error(err.message || 'Failed to load students');
        } finally {
            setLoading(false);
        }
    }, [search, statusFilter]);

    useEffect(() => { fetchStudents(); }, [fetchStudents]);

    // Debounced search
    useEffect(() => {
        const timer = setTimeout(() => fetchStudents(), 300);
        return () => clearTimeout(timer);
    }, [search]);

    const updateStatus = async (id, status) => {
        try {
            await api.put(`/admin/students/${id}/status`, { status });
            toast.success('Status updated');
            fetchStudents(pagination.page);
        } catch (err) {
            toast.error(err.message);
        }
        setActionMenu(null);
    };

    const updateRole = async (id, roleId) => {
        try {
            await api.put(`/admin/users/${id}/role`, { role_id: roleId });
            toast.success('Role updated');
            fetchStudents(pagination.page);
        } catch (err) {
            toast.error(err.message);
        }
        setActionMenu(null);
    };

    const bulkAction = async (action) => {
        if (selected.size === 0) return;
        try {
            await api.post('/admin/students/bulk-action', {
                action,
                student_ids: Array.from(selected),
            });
            toast.success(`Bulk ${action} completed on ${selected.size} students`);
            setSelected(new Set());
            fetchStudents(pagination.page);
        } catch (err) {
            toast.error(err.message);
        }
    };

    const toggleAll = () => {
        if (selected.size === students.length) {
            setSelected(new Set());
        } else {
            setSelected(new Set(students.map(s => s.id)));
        }
    };

    const toggleOne = (id) => {
        const next = new Set(selected);
        next.has(id) ? next.delete(id) : next.add(id);
        setSelected(next);
    };

    const statuses = ['', 'submitted', 'pending', 'documents_uploaded', 'under_review', 'interview_scheduled', 'approved', 'rejected'];

    return (
        <div className="space-y-10 animate-fade-in pb-20">
            <div className="flex items-center justify-between animate-stagger" style={{ animationDelay: '0ms' }}>
                <div>
                    <h1 className="text-3xl md:text-4xl font-black tracking-tight text-indigo-950 dark:text-white">
                        Student <span className="text-indigo-600 font-display">Directory</span>
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-medium flex items-center gap-2">
                        <Users className="w-4 h-4 text-indigo-600" />
                        {pagination.total} high-potential candidates mapped
                    </p>
                </div>
            </div>

            {/* Filters */}
            <div className="card-premium p-6 animate-stagger" style={{ animationDelay: '100ms' }}>
                <div className="flex flex-col lg:flex-row gap-4">
                    <div className="relative flex-1 group">
                        <Search className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400 group-focus-within:text-indigo-600 transition-colors" />
                        <input
                            type="text"
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            className="w-full bg-surface-50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-14 pr-6 py-4 text-sm font-medium focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none"
                            placeholder="Identify by name, email or student ID..."
                        />
                    </div>
                    <div className="relative min-w-[220px] group">
                        <Filter className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400 group-focus-within:text-indigo-600 transition-colors" />
                        <select
                            value={statusFilter}
                            onChange={e => setStatusFilter(e.target.value)}
                            className="w-full bg-surface-50 dark:bg-white/5 border border-surface-200 dark:border-white/5 rounded-2xl pl-14 pr-10 py-4 text-sm font-black uppercase tracking-widest focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none appearance-none cursor-pointer"
                        >
                            <option value="">Status: All</option>
                            {statuses.filter(Boolean).map(s => (
                                <option key={s} value={s}>{s.replace(/_/g, ' ').toUpperCase()}</option>
                            ))}
                        </select>
                        <div className="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none opacity-40">
                             <ChevronRight className="w-4 h-4 rotate-90" />
                        </div>
                    </div>
                </div>
            </div>

            {/* Bulk Actions */}
            {selected.size > 0 && (
                <div className="card-premium p-4 flex items-center justify-between bg-indigo-50 dark:bg-indigo-500/10 border-indigo-200 dark:border-indigo-500/20 animate-slide-up shadow-glow-indigo">
                    <div className="flex items-center gap-3 ml-2">
                         <div className="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-black">
                            {selected.size}
                         </div>
                         <span className="text-sm font-black text-indigo-900 dark:text-indigo-100 uppercase tracking-widest">
                            Candidates Selected
                        </span>
                    </div>
                    <div className="flex gap-2">
                        <button onClick={() => bulkAction('approve')} className="btn-v2-primary !py-2 !px-4 text-[10px]">
                            <Check className="w-3.5 h-3.5" /> APPROVE
                        </button>
                        <button onClick={() => bulkAction('reject')} className="btn-v2-secondary !py-2 !px-4 text-[10px] !bg-white/50">
                            <X className="w-3.5 h-3.5" /> REJECT
                        </button>
                        <button onClick={() => setConfirmModal({ action: 'delete' })} className="px-4 py-2 rounded-xl text-[10px] font-black text-rose-500 hover:bg-rose-500/10 transition-all uppercase tracking-widest flex items-center gap-2">
                            <Trash2 className="w-3.5 h-3.5" /> DELETE
                        </button>
                    </div>
                </div>
            )}

            {/* Table */}
            {loading ? <SkeletonTable rows={8} cols={5} /> : students.length === 0 ? (
                <div className="card-premium p-20 animate-stagger" style={{ animationDelay: '200ms' }}>
                    <EmptyState icon={Users} title="No students found" description="Try adjusting your search or filters." />
                </div>
            ) : (
                <div className="card-premium overflow-hidden animate-stagger" style={{ animationDelay: '200ms' }}>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-surface-100 dark:border-white/5 bg-surface-50 dark:bg-white/5">
                                    <th className="p-6 w-12 text-center">
                                        <input type="checkbox" checked={selected.size === students.length && students.length > 0} onChange={toggleAll} className="w-5 h-5 rounded-lg border-surface-200 text-indigo-600 focus:ring-indigo-500/20 bg-white dark:bg-surface-900" />
                                    </th>
                                    <th className="p-6 text-left text-[10px] font-black text-surface-400 uppercase tracking-[0.2em]">Full Identity</th>
                                    <th className="p-6 text-left text-[10px] font-black text-surface-400 uppercase tracking-[0.2em]">Contact Node</th>
                                    <th className="p-6 text-left text-[10px] font-black text-surface-400 uppercase tracking-[0.2em]">Role</th>
                                    <th className="p-6 text-left text-[10px] font-black text-surface-400 uppercase tracking-[0.2em]">Status</th>
                                    <th className="p-6 text-left text-[10px] font-black text-surface-400 uppercase tracking-[0.2em]">Entry Date</th>
                                    <th className="p-6 w-12"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-surface-100 dark:divide-white/5">
                                {students.map(s => (
                                    <tr key={s.id} className={`group hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition-all duration-300 ${selected.has(s.id) ? 'bg-indigo-50/50 dark:bg-indigo-500/10' : ''}`}>
                                        <td className="p-6 text-center">
                                            <input type="checkbox" checked={selected.has(s.id)} onChange={() => toggleOne(s.id)} className="w-5 h-5 rounded-lg border-surface-200 text-indigo-600 focus:ring-indigo-500/20 bg-white dark:bg-surface-900 transition-transform group-hover:scale-110" />
                                        </td>
                                        <td className="p-6">
                                            <div className="flex items-center gap-4">
                                                <UserAvatar user={s} size="sm" className="group-hover:ring-2 ring-indigo-500/20 transition-all" />
                                                <div>
                                                    <p className="font-black text-sm text-surface-900 dark:text-white uppercase tracking-tight group-hover:text-indigo-600 transition-colors">{s.firstname} {s.lastname}</p>
                                                    <p className="text-[10px] text-surface-500 font-bold opacity-60">ID: {s.student_id || 'U-0000'}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="p-6 text-xs font-bold text-surface-600 dark:text-surface-300 lowercase">{s.email}</td>
                                        <td className="p-6">
                                            <span className={`px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest ${
                                                s.role_name === 'Admin' ? 'bg-rose-100 text-rose-600 dark:bg-rose-500/10' :
                                                s.role_name === 'Manager' ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/10' :
                                                s.role_name === 'Interviewer' ? 'bg-purple-100 text-purple-600 dark:bg-purple-500/10' :
                                                'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/10'
                                            }`}>
                                                {s.role_name || 'Student'}
                                            </span>
                                        </td>
                                        <td className="p-6"><StatusBadge status={s.status} /></td>
                                        <td className="p-6 text-xs font-black text-surface-500 opacity-60 uppercase">{new Date(s.created_at).toLocaleDateString()}</td>
                                        <td className="p-6 relative">
                                            <button onClick={() => setActionMenu(actionMenu === s.id ? null : s.id)} className="p-2 rounded-xl hover:bg-surface-100 dark:hover:bg-white/10 transition-colors text-surface-400 hover:text-indigo-600">
                                                <MoreVertical className="w-5 h-5" />
                                            </button>
                                            {actionMenu === s.id && (
                                                <div className="absolute right-4 top-16 z-20 w-56 card-premium p-2 shadow-premium animate-fade-in">
                                                    <button onClick={() => updateStatus(s.id, 'approved')} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 dark:hover:bg-indigo-500/10 text-left transition-colors">
                                                        <Check className="w-4 h-4 text-emerald-500" /> Approve Candidate
                                                    </button>
                                                    <button onClick={() => updateStatus(s.id, 'rejected')} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-50 dark:hover:bg-rose-500/10 text-left transition-colors text-rose-500">
                                                        <X className="w-4 h-4" /> Reject Candidate
                                                    </button>
                                                    <button onClick={() => updateStatus(s.id, 'under_review')} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-50 dark:hover:bg-blue-500/10 text-left transition-colors">
                                                        <Eye className="w-4 h-4 text-blue-500" /> Start Review
                                                    </button>
                                                    <hr className="my-2 border-surface-100 dark:border-white/5" />
                                                    <div className="px-4 py-2 text-[8px] font-black text-surface-400 uppercase tracking-[0.2em]">Assignment</div>
                                                    <button onClick={() => updateRole(s.id, 2)} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-50 dark:hover:bg-amber-500/10 text-left transition-colors">
                                                        <Shield className="w-4 h-4 text-amber-500" /> Make Manager
                                                    </button>
                                                    <button onClick={() => updateRole(s.id, 3)} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-50 dark:hover:bg-purple-500/10 text-left transition-colors">
                                                        <UserCheck className="w-4 h-4 text-purple-500" /> Make Interviewer
                                                    </button>
                                                    <button onClick={() => updateRole(s.id, 4)} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-surface-50 dark:hover:bg-white/5 text-left transition-colors">
                                                        <Users className="w-4 h-4 text-surface-400" /> Revoke to Student
                                                    </button>
                                                    <hr className="my-2 border-surface-100 dark:border-white/5" />
                                                    <button onClick={() => navigate('/messages', { state: { userId: s.id } })} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-surface-50 dark:hover:bg-white/5 text-left transition-colors">
                                                        <MessageSquare className="w-4 h-4 text-indigo-500" /> Open Channel
                                                    </button>
                                                    <button onClick={() => { setConfirmModal({ action: 'delete', ids: [s.id] }); setActionMenu(null); }} className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-500/10 text-left transition-colors text-rose-500 mt-1">
                                                        <Trash2 className="w-4 h-4" /> Purge Record
                                                    </button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pagination.pages > 1 && (
                        <div className="flex items-center justify-between p-8 border-t border-surface-100 dark:border-white/5 bg-surface-50/30 dark:bg-white/5">
                            <p className="text-[10px] font-black text-surface-400 uppercase tracking-widest">
                                Segment {pagination.page} of {pagination.pages} <span className="mx-2 opacity-30">|</span> {pagination.total} Records
                            </p>
                            <div className="flex gap-3">
                                <button
                                    onClick={() => fetchStudents(pagination.page - 1)}
                                    disabled={pagination.page <= 1}
                                    className="p-3 rounded-2xl bg-white dark:bg-surface-900 border border-surface-200 dark:border-white/5 text-surface-500 hover:text-indigo-600 hover:border-indigo-200 disabled:opacity-30 transition-all shadow-sm"
                                >
                                    <ChevronLeft className="w-5 h-5" />
                                </button>
                                <button
                                    onClick={() => fetchStudents(pagination.page + 1)}
                                    disabled={pagination.page >= pagination.pages}
                                    className="p-3 rounded-2xl bg-white dark:bg-surface-900 border border-surface-200 dark:border-white/5 text-surface-500 hover:text-indigo-600 hover:border-indigo-200 disabled:opacity-30 transition-all shadow-sm"
                                >
                                    <ChevronRight className="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Delete Confirm Modal */}
            <ConfirmModal
                isOpen={!!confirmModal}
                onClose={() => setConfirmModal(null)}
                onConfirm={() => bulkAction('delete')}
                title="Delete Students"
                message={`Are you sure you want to delete ${confirmModal?.ids ? confirmModal.ids.length : selected.size} student(s)? This cannot be undone.`}
                confirmText="Delete"
                danger
            />
        </div>
    );
}
