import { useState, useEffect } from 'react';
import { Link as LinkIcon, X, Check, AlertCircle, Loader2 } from 'lucide-react';
import { socialApi } from '../../api/social';

export default function MediaUrlInput({ onSelect, onClear, initialValue = '' }) {
    const [url, setUrl] = useState(initialValue);
    const [isValidating, setIsValidating] = useState(false);
    const [error, setError] = useState(null);
    const [preview, setPreview] = useState(null);

    const validateUrl = async (inputUrl) => {
        if (!inputUrl) {
            setError(null);
            setPreview(null);
            return;
        }

        setIsValidating(true);
        setError(null);

        try {
            const res = await socialApi.validateMediaUrl(inputUrl);
            if (res.success) {
                setPreview(res.data);
                onSelect(res.data);
            } else {
                setError(res.message);
                setPreview(null);
            }
        } catch (err) {
            setError(err.message || 'Invalid media URL');
            setPreview(null);
        } finally {
            setIsValidating(false);
        }
    };

    const handleClear = () => {
        setUrl('');
        setPreview(null);
        setError(null);
        onClear();
    };

    return (
        <div className="space-y-3 animate-in fade-in duration-300">
            <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <LinkIcon className="h-4 w-4 text-slate-400" />
                </div>
                <input
                    type="text"
                    value={url}
                    onChange={(e) => setUrl(e.target.value)}
                    onBlur={() => validateUrl(url)}
                    placeholder="Paste image or video URL (YouTube, Imgur, etc.)"
                    className={`block w-full pl-10 pr-10 py-2 bg-slate-50 dark:bg-white/5 border ${error ? 'border-red-500' : 'border-slate-200 dark:border-slate-800'} rounded-xl text-sm dark:text-white placeholder:text-slate-500 focus:ring-primary-500 focus:border-primary-500 transition-all`}
                />
                <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                    {isValidating ? (
                        <Loader2 className="h-4 w-4 text-primary-500 animate-spin" />
                    ) : url && (
                        <button onClick={handleClear} className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <X className="h-4 w-4" />
                        </button>
                    )}
                </div>
            </div>

            {error && (
                <div className="flex items-center gap-2 text-red-500 text-[10px] font-bold uppercase tracking-widest pl-1">
                    <AlertCircle className="w-3 h-3" />
                    <span>{error}</span>
                </div>
            )}

            {preview && (
                <div className="relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-900 group aspect-video max-h-[200px]">
                    <div className="absolute top-2 right-2 z-10">
                        <div className="bg-emerald-500 text-white p-1 rounded-full shadow-lg">
                            <Check className="w-3 h-3" />
                        </div>
                    </div>
                    {preview.type === 'video' ? (
                        <div className="w-full h-full flex items-center justify-center bg-slate-800">
                             <img src={`https://img.youtube.com/vi/${preview.url.match(/(?:v=|be\/|embed\/)([^&?]+)/)?.[1] || ''}/0.jpg`} className="w-full h-full object-cover opacity-50" />
                             <div className="absolute inset-0 flex items-center justify-center">
                                <div className="p-3 bg-white/10 backdrop-blur-md rounded-full text-white">
                                    <LinkIcon className="w-6 h-6" />
                                </div>
                             </div>
                        </div>
                    ) : (
                        <img src={preview.url} alt="Preview" className="w-full h-full object-cover" />
                    )}
                </div>
            )}
        </div>
    );
}
