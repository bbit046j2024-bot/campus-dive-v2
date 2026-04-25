import { useState, useEffect, useCallback, useRef } from 'react';
import api from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { SkeletonCard } from '../../components/ui/Skeleton';
import EmptyState from '../../components/ui/EmptyState';
import { ConfirmModal } from '../../components/ui/Modal';
import { Upload, FileText, Trash2, Eye, CloudUpload, X, File } from 'lucide-react';

export default function DocumentsPage() {
    const { user } = useAuth();
    const toast = useToast();
    const [documents, setDocuments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [dragActive, setDragActive] = useState(false);
    const [deleteId, setDeleteId] = useState(null);
    const [docName, setDocName] = useState('');
    const fileInputRef = useRef(null);

    const loadDocs = useCallback(async () => {
        try {
            const res = await api.get('/student/documents');
            setDocuments(res.data);
        } catch (e) { } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { loadDocs(); }, [loadDocs]);

    const handleUpload = async (files) => {
        if (!files?.length) return;
        setUploading(true);
        try {
            for (const file of files) {
                const formData = new FormData();
                formData.append('document', file);
                formData.append('document_name', docName || file.name);
                await api.upload('/student/documents', formData);
            }
            toast.success('Document uploaded!');
            setDocName('');
            loadDocs();
        } catch (err) {
            toast.error(err.message);
        } finally {
            setUploading(false);
        }
    };

    const handleDelete = async () => {
        try {
            await api.delete(`/student/documents/${deleteId}`);
            toast.success('Document deleted');
            setDeleteId(null);
            loadDocs();
        } catch (err) {
            toast.error(err.message);
        }
    };

    const onDrop = (e) => {
        e.preventDefault();
        setDragActive(false);
        handleUpload(e.dataTransfer.files);
    };

    if (loading) return <div className="space-y-4"><SkeletonCard /><SkeletonCard /></div>;

    return (
        <div className="space-y-10 pb-20 animate-fade-in">
            <div className="flex items-center justify-between mb-8 animate-stagger" style={{ animationDelay: '0ms' }}>
                <div>
                    <h1 className="text-3xl md:text-4xl font-black tracking-tight text-indigo-950 dark:text-white flex items-center gap-4 uppercase">
                        Document <span className="text-indigo-600 font-display">Vault</span>
                    </h1>
                    <p className="text-surface-500 dark:text-surface-400 mt-2 font-bold uppercase tracking-widest text-[10px] opacity-60">Secure repository for your professional military-grade assets</p>
                </div>
            </div>

            {/* Upload Zone */}
            <div
                className={`card-premium p-12 border-2 border-dashed transition-all duration-500 cursor-pointer text-center group animate-stagger
                    ${dragActive ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/10' : 'border-surface-200 dark:border-white/10 hover:border-indigo-400'}
                `}
                style={{ animationDelay: '200ms' }}
                onDragOver={(e) => { e.preventDefault(); setDragActive(true); }}
                onDragLeave={() => setDragActive(false)}
                onDrop={onDrop}
                onClick={() => fileInputRef.current?.click()}
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    onChange={(e) => handleUpload(e.target.files)}
                    className="hidden"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                />
                
                <div className="relative inline-block mb-6">
                    <div className="absolute inset-0 bg-indigo-500 blur-2xl opacity-0 group-hover:opacity-20 transition-opacity" />
                    <div className={`w-20 h-20 rounded-3xl mx-auto flex items-center justify-center transition-all duration-500 ${dragActive ? 'bg-indigo-600 text-white scale-110 shadow-glow-indigo' : 'bg-surface-100 dark:bg-white/5 text-surface-400 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/20 group-hover:text-indigo-600'}`}>
                        <CloudUpload className="w-10 h-10" />
                    </div>
                </div>

                <h3 className="text-xl font-black text-indigo-950 dark:text-white uppercase tracking-tight mb-2">
                    {uploading ? 'Synchronizing Assets...' : 'Drop Intelligence Here'}
                </h3>
                <p className="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em] mb-8">
                    or click to browse local filesystem • <span className="text-indigo-600">PDF, DOC, JPG, PNG</span> • MAX 5MB
                </p>

                {/* Document Name Input */}
                <div className="max-w-xs mx-auto relative px-4" onClick={(e) => e.stopPropagation()}>
                    <div className="absolute left-8 top-1/2 -translate-y-1/2 w-2 h-2 bg-indigo-600 rounded-full" />
                    <input
                        type="text"
                        value={docName}
                        onChange={e => setDocName(e.target.value)}
                        className="w-full bg-white dark:bg-white/5 border border-surface-200 dark:border-white/10 rounded-2xl pl-10 pr-6 py-4 text-xs font-black uppercase tracking-widest focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none"
                        placeholder="Label this asset..."
                    />
                </div>
            </div>

            {/* Document List */}
            <div className="space-y-6">
                <div className="flex items-center gap-4 mb-4">
                    <div className="w-2 h-6 bg-indigo-600 rounded-full" />
                    <h3 className="text-[10px] font-black uppercase tracking-[0.3em] text-surface-400">Deployed Assets ({documents.length})</h3>
                </div>

                {documents.length === 0 ? (
                    <div className="animate-stagger" style={{ animationDelay: '400ms' }}>
                        <EmptyState
                            icon={FileText}
                            title="Registry Empty"
                            description="No professional assets detected in your current profile. Upload your CV or Transcript to start the deployment process."
                            action={<button onClick={() => fileInputRef.current?.click()} className="btn-v2-primary px-8 py-4 text-[10px] font-black uppercase tracking-widest"><Upload className="w-4 h-4" /> UPLOAD NOW</button>}
                        />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {documents.map((doc, i) => (
                            <div 
                                key={doc.id} 
                                className="card-premium p-6 group hover:translate-y-[-4px] transition-all duration-500 animate-stagger"
                                style={{ animationDelay: `${400 + (i * 100)}ms` }}
                            >
                                <div className="flex items-start gap-4">
                                    <div className="w-14 h-14 rounded-2xl bg-surface-100 dark:bg-white/5 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-500 shadow-sm group-hover:shadow-glow-indigo">
                                        <File className="w-7 h-7" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="font-black text-sm text-indigo-950 dark:text-white truncate uppercase tracking-tight group-hover:text-indigo-600 transition-colors">
                                            {doc.document_name || doc.original_name}
                                        </p>
                                        <p className="text-[9px] font-bold text-surface-400 uppercase tracking-widest mt-1 opacity-60">
                                            {(doc.file_size / 1024).toFixed(1)} KB • {new Date(doc.uploaded_at).toLocaleDateString()}
                                        </p>
                                        <div className="mt-4">
                                            <StatusBadge status={doc.status || 'pending'} />
                                        </div>
                                    </div>
                                </div>
                                <div className="flex gap-3 mt-8 pt-4 border-t border-surface-100 dark:border-white/5">
                                    <a href={`/uploads/${doc.filename}`} target="_blank" rel="noopener" className="flex-1 py-3 bg-surface-100 dark:bg-white/5 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] text-surface-600 dark:text-surface-400 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center gap-2">
                                        <Eye className="w-3 h-3" /> VERIFY
                                    </a>
                                    <button onClick={() => setDeleteId(doc.id)} className="flex-1 py-3 bg-surface-100 dark:bg-white/5 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center gap-2">
                                        <Trash2 className="w-3 h-3" /> PURGE
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <ConfirmModal
                isOpen={!!deleteId}
                onClose={() => setDeleteId(null)}
                onConfirm={handleDelete}
                title="PURGE ASSET"
                message="This synchronization data will be permanently removed from the recruitment mainframe. This action is irreversible."
                confirmText="PURGE"
                danger
            />
        </div>
    );
}
