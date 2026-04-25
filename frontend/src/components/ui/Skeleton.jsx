export function Skeleton({ className = '', ...props }) {
    return <div className={`skeleton ${className}`} {...props} />;
}

export function SkeletonCard() {
    return (
        <div className="card p-6 space-y-4">
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-4 w-1/2" />
            <Skeleton className="h-8 w-full" />
        </div>
    );
}

export function SkeletonTable({ rows = 5, cols = 4 }) {
    return (
        <div className="card overflow-hidden">
            <div className="p-4 border-b border-surface-100 dark:border-surface-800">
                <Skeleton className="h-5 w-48" />
            </div>
            <div className="divide-y divide-surface-100 dark:divide-surface-800">
                {Array.from({ length: rows }).map((_, i) => (
                    <div key={i} className="flex items-center gap-4 p-4">
                        <Skeleton className="h-10 w-10 rounded-full shrink-0" />
                        {Array.from({ length: cols }).map((_, j) => (
                            <Skeleton key={j} className="h-4 flex-1" />
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

export function SkeletonStats({ count = 4 }) {
    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="card p-6 space-y-3">
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-8 w-16" />
                    <Skeleton className="h-2 w-full" />
                </div>
            ))}
        </div>
    );
}
