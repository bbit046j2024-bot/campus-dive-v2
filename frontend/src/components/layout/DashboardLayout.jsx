import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';

export default function DashboardLayout() {
    return (
        <div className="flex min-h-screen bg-surface-50 dark:bg-surface-950 font-sans selection:bg-indigo-500/30">
            <Sidebar />
            <main className="flex-1 lg:pl-0 min-w-0 transition-all duration-500">
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 lg:px-12 py-8 pt-20 lg:pt-10">
                    <Outlet />
                </div>
            </main>
        </div>
    );
}
