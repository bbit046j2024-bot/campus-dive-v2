import { useState } from 'react';
import { API_BASE } from '../../api/client';

const STATUS_CONFIG = {
    submitted: { label: 'Submitted', color: 'badge-neutral' },
    pending: { label: 'Pending', color: 'badge-warning' },
    documents_uploaded: { label: 'Docs Uploaded', color: 'badge-info' },
    under_review: { label: 'Under Review', color: 'badge-info' },
    interview_scheduled: { label: 'Interview', color: 'badge-warning' },
    approved: { label: 'Approved', color: 'badge-success' },
    rejected: { label: 'Rejected', color: 'badge-danger' },
};

export function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] || { label: status, color: 'badge-neutral' };
    return <span className={config.color}>{config.label}</span>;
}

export function UserAvatar({ user, size = 'md' }) {
    const [imgError, setImgError] = useState(false);

    const sizes = {
        xs: 'w-6 h-6 text-[8px]',
        sm: 'w-8 h-8 text-xs',
        md: 'w-10 h-10 text-sm',
        lg: 'w-14 h-14 text-lg',
        xl: 'w-20 h-20 text-2xl',
        full: 'w-full h-full text-4xl',
    };

    const hasImage = user?.avatar_image && !imgError;

    if (hasImage) {
        return (
            <img
                src={user.avatar_image.startsWith('http') ? user.avatar_image : `${API_BASE}/${user.avatar_image}`}
                alt={`${user.firstname} ${user.lastname}`}
                onError={() => setImgError(true)}
                className={`${sizes[size]} rounded-full object-cover ring-2 ring-surface-200 dark:ring-surface-700`}
            />
        );
    }

    // Format initials: Take max 2 characters from firstname/lastname or the avatar field
    let initials = '';
    if (user?.avatar && user.avatar.length <= 3) {
        initials = user.avatar;
    } else {
        initials = (user?.firstname?.[0] || '') + (user?.lastname?.[0] || '');
    }
    initials = initials.toUpperCase();

    return (
        <div className={`${sizes[size]} rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold ring-2 ring-surface-200 dark:ring-surface-700 overflow-hidden`}>
            {initials}
        </div>
    );
}
