export default function EmptyState({ icon: Icon, title, description, action }) {
    return (
        <div className="flex flex-col items-center justify-center py-16 px-6 text-center">
            {Icon && (
                <div className="w-20 h-20 rounded-2xl bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center mb-6">
                    <Icon className="w-10 h-10 text-primary-400" />
                </div>
            )}
            <h3 className="text-lg font-semibold text-surface-700 dark:text-surface-300 mb-2">{title}</h3>
            <p className="text-surface-500 dark:text-surface-400 max-w-md mb-6">{description}</p>
            {action && action}
        </div>
    );
}
