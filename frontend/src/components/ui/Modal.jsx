import { useState } from 'react';
import { X } from 'lucide-react';

export default function Modal({ isOpen, onClose, title, children, size = 'md' }) {
    if (!isOpen) return null;

    const sizes = {
        sm: 'max-w-md',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onClose} />
            <div className={`relative w-full ${sizes[size]} card p-0 animate-slide-up`}>
                <div className="flex items-center justify-between p-6 border-b border-surface-100 dark:border-surface-800">
                    <h3 className="text-lg font-semibold">{title}</h3>
                    <button onClick={onClose} className="btn-icon">
                        <X className="w-5 h-5" />
                    </button>
                </div>
                <div className="p-6">{children}</div>
            </div>
        </div>
    );
}

export function ConfirmModal({ isOpen, onClose, onConfirm, title, message, confirmText = 'Confirm', danger = false }) {
    const [loading, setLoading] = useState(false);

    const handleConfirm = async () => {
        setLoading(true);
        try {
            await onConfirm();
            onClose();
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} title={title} size="sm">
            <p className="text-surface-600 dark:text-surface-400 mb-6">{message}</p>
            <div className="flex justify-end gap-3">
                <button onClick={onClose} className="btn-secondary" disabled={loading}>Cancel</button>
                <button
                    onClick={handleConfirm}
                    className={danger ? 'btn-danger' : 'btn-primary'}
                    disabled={loading}
                >
                    {loading ? 'Processing...' : confirmText}
                </button>
            </div>
        </Modal>
    );
}
