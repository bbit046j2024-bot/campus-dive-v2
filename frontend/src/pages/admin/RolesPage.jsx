import { useState, useEffect } from 'react';
import api from '../../api/client';
import { useToast } from '../../context/ToastContext';
import { Shield, Save, Check, X } from 'lucide-react';

export default function RolesPage() {
    const [roles, setRoles] = useState([]);
    const [allPermissions, setAllPermissions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(null);
    const toast = useToast();

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const res = await api.get('/admin/roles');
            setRoles(res.data.roles);
            setAllPermissions(res.data.all_permissions);
        } catch (err) {
            toast.error('Failed to load roles: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleTogglePermission = (roleId, permissionId) => {
        setRoles(prev => prev.map(role => {
            if (role.id !== roleId) return role;

            const hasPermission = role.permissions.some(p => p.id === permissionId);
            const newPermissions = hasPermission
                ? role.permissions.filter(p => p.id !== permissionId)
                : [...role.permissions, allPermissions.find(p => p.id === permissionId)];

            return { ...role, permissions: newPermissions, isDirty: true };
        }));
    };

    const handleSaveRole = async (role) => {
        setSaving(role.id);
        try {
            const permissionIds = role.permissions.map(p => p.id);
            await api.put(`/admin/roles/${role.id}`, { permission_ids: permissionIds });
            toast.success(`${role.name} permissions updated!`);
            setRoles(prev => prev.map(r => r.id === role.id ? { ...r, isDirty: false } : r));
        } catch (err) {
            toast.error('Failed to update role: ' + err.message);
        } finally {
            setSaving(null);
        }
    };

    if (loading) return (
        <div className="flex items-center justify-center min-h-[400px]">
            <div className="w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full animate-spin" />
        </div>
    );

    return (
        <div className="animate-fade-in space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold flex items-center gap-2">
                        <Shield className="w-6 h-6 text-primary-500" />
                        Roles & Permissions
                    </h1>
                    <p className="text-surface-500 text-sm mt-1">Manage what different user types can see and do.</p>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6">
                {roles.map(role => (
                    <div key={role.id} className="card overflow-hidden">
                        <div className="p-4 bg-surface-50 dark:bg-white/5 border-b border-white/10 flex items-center justify-between">
                            <div>
                                <h3 className="font-bold text-lg">{role.name}</h3>
                                <p className="text-sm text-surface-500">{role.description}</p>
                            </div>
                            {role.isDirty && (
                                <button
                                    onClick={() => handleSaveRole(role)}
                                    disabled={saving === role.id}
                                    className="btn-primary py-1.5 px-4 text-xs flex items-center gap-2"
                                >
                                    {saving === role.id ? (
                                        <div className="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                    ) : (
                                        <Save className="w-3 h-3" />
                                    )}
                                    Save Changes
                                </button>
                            )}
                        </div>

                        <div className="p-6">
                            <h4 className="text-sm font-semibold text-surface-400 uppercase tracking-wider mb-4">Permissions</h4>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {allPermissions.map(permission => {
                                    const isGranted = role.permissions.some(p => p.id === permission.id);
                                    return (
                                        <button
                                            key={permission.id}
                                            onClick={() => handleTogglePermission(role.id, permission.id)}
                                            className={`flex items-center gap-3 p-3 rounded-xl border text-left transition-all
                                                ${isGranted
                                                    ? 'border-primary-500/50 bg-primary-50/50 dark:bg-primary-900/10 text-primary-700 dark:text-primary-400'
                                                    : 'border-surface-200 dark:border-surface-800 hover:border-surface-300 dark:hover:border-surface-700'
                                                }`}
                                        >
                                            <div className={`w-5 h-5 rounded-md flex items-center justify-center shrink-0 border transition-colors
                                                ${isGranted
                                                    ? 'bg-primary-500 border-primary-500 text-white'
                                                    : 'border-surface-300 dark:border-surface-700'
                                                }`}
                                            >
                                                {isGranted && <Check className="w-3 h-3" />}
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold leading-tight">{permission.name}</p>
                                                <p className="text-[10px] opacity-60 mt-0.5">{permission.description}</p>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
