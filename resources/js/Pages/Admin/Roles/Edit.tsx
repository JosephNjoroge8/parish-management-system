import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Save, 
    ArrowLeft, 
    Shield, 
    Settings, 
    Users, 
    Eye, 
    Edit, 
    Trash2,
    Check,
    X,
    AlertCircle,
    Info,
    Lock,
    Unlock,
    Database,
    FileText,
    DollarSign,
    Activity,
    UserCheck,
    BarChart3,
    Home,
    Crown,
    Award,
    Star,
    User
} from 'lucide-react';

interface Permission {
    id: number;
    name: string;
    display_name?: string;
}

interface PermissionGroup {
    [key: string]: Permission[];
}

interface Role {
    id: number;
    name: string;
    display_name: string;
    permissions: string[];
    users_count: number;
    description?: string;
}

interface EditRoleProps {
    role: Role;
    permissions: PermissionGroup;
}

const getModuleIcon = (module: string) => {
    switch (module.toLowerCase()) {
        case 'members': return Users;
        case 'families': return Home;
        case 'users': return UserCheck;
        case 'sacraments': return Shield;
        case 'tithes': return DollarSign;
        case 'activities': return Activity;
        case 'groups': return Users;
        case 'reports': return BarChart3;
        case 'settings': return Settings;
        case 'dashboard': return Database;
        default: return FileText;
    }
};

const getModuleColor = (module: string) => {
    switch (module.toLowerCase()) {
        case 'members': return 'bg-blue-50 border-blue-200 text-blue-800';
        case 'families': return 'bg-green-50 border-green-200 text-green-800';
        case 'users': return 'bg-purple-50 border-purple-200 text-purple-800';
        case 'sacraments': return 'bg-yellow-50 border-yellow-200 text-yellow-800';
        case 'tithes': return 'bg-emerald-50 border-emerald-200 text-emerald-800';
        case 'activities': return 'bg-orange-50 border-orange-200 text-orange-800';
        case 'groups': return 'bg-pink-50 border-pink-200 text-pink-800';
        case 'reports': return 'bg-indigo-50 border-indigo-200 text-indigo-800';
        case 'settings': return 'bg-gray-50 border-gray-200 text-gray-800';
        case 'dashboard': return 'bg-teal-50 border-teal-200 text-teal-800';
        default: return 'bg-gray-50 border-gray-200 text-gray-800';
    }
};

const getRoleIcon = (roleName: string) => {
    const name = roleName.toLowerCase();
    if (name.includes('super') || name.includes('admin')) return Crown;
    if (name.includes('manager') || name.includes('admin')) return Award;
    if (name.includes('staff') || name.includes('secretary')) return Star;
    if (name.includes('treasurer')) return Settings;
    return User;
};

const getClearanceLevel = (roleName: string): number => {
    const name = roleName.toLowerCase();
    if (name.includes('super')) return 5;
    if (name.includes('admin')) return 4;
    if (name.includes('manager')) return 3;
    if (name.includes('staff') || name.includes('secretary') || name.includes('treasurer')) return 2;
    return 1; // Viewer level
};

const getPermissionType = (permissionName: string): string => {
    if (permissionName.startsWith('manage') || permissionName.startsWith('create') || 
        permissionName.startsWith('edit') || permissionName.startsWith('delete')) {
        return 'write';
    }
    if (permissionName.startsWith('view') || permissionName.startsWith('access')) {
        return 'read';
    }
    if (permissionName.startsWith('export')) {
        return 'export';
    }
    return 'other';
};

const getPermissionTypeColor = (type: string): string => {
    switch (type) {
        case 'write': return 'bg-red-100 text-red-800 border-red-200';
        case 'read': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'export': return 'bg-green-100 text-green-800 border-green-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

export default function EditRole({ role, permissions }: EditRoleProps) {
    const { data, setData, put, processing, errors, reset } = useForm({
        name: role.name || '',
        display_name: role.display_name || '',
        description: role.description || '',
        permissions: role.permissions || [],
        clearance_level: getClearanceLevel(role.name)
    });

    const [expandedModules, setExpandedModules] = useState<string[]>(['members', 'dashboard']);
    const [selectAllModules, setSelectAllModules] = useState<{[key: string]: boolean}>({});
    const [hasChanges, setHasChanges] = useState(false);

    const IconComponent = getRoleIcon(role.name);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.roles.update', role.id), {
            onSuccess: () => {
                setHasChanges(false);
            }
        });
    };

    const getAllPermissions = (): string[] => {
        return Object.values(permissions).flat().map(p => p.name);
    };

    const toggleModule = (module: string) => {
        if (expandedModules.includes(module)) {
            setExpandedModules(expandedModules.filter(m => m !== module));
        } else {
            setExpandedModules([...expandedModules, module]);
        }
    };

    const toggleModulePermissions = (module: string, modulePermissions: Permission[]) => {
        const permissionNames = modulePermissions.map(p => p.name);
        const currentPermissions = data.permissions || [];
        const hasAll = permissionNames.every(p => currentPermissions.includes(p));
        
        if (hasAll) {
            // Remove all module permissions
            setData('permissions', currentPermissions.filter(p => !permissionNames.includes(p)));
            setSelectAllModules({ ...selectAllModules, [module]: false });
        } else {
            // Add all module permissions
            const newPermissions = [...new Set([...currentPermissions, ...permissionNames])];
            setData('permissions', newPermissions);
            setSelectAllModules({ ...selectAllModules, [module]: true });
        }
        setHasChanges(true);
    };

    const togglePermission = (permission: string) => {
        const currentPermissions = data.permissions || [];
        if (currentPermissions.includes(permission)) {
            setData('permissions', currentPermissions.filter(p => p !== permission));
        } else {
            setData('permissions', [...currentPermissions, permission]);
        }
        setHasChanges(true);
    };

    const handleInputChange = (field: string, value: any) => {
        setData(field as any, value);
        setHasChanges(true);
    };

    const permissionStats = {
        total: Object.values(permissions).flat().length,
        selected: data.permissions?.length || 0,
        readOnly: (data.permissions || []).filter(p => getPermissionType(p) === 'read').length,
        writeAccess: (data.permissions || []).filter(p => getPermissionType(p) === 'write').length,
        exportAccess: (data.permissions || []).filter(p => getPermissionType(p) === 'export').length
    };

    const resetChanges = () => {
        setData({
            name: role.name,
            display_name: role.display_name,
            description: role.description || '',
            permissions: role.permissions,
            clearance_level: getClearanceLevel(role.name)
        });
        setHasChanges(false);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link 
                            href={route('admin.roles.show', role.id)} 
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div className="flex items-center space-x-3">
                            <div className="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100">
                                <IconComponent className="w-6 h-6 text-gray-600" />
                            </div>
                            <div>
                                <h2 className="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                                    Edit Role: {role.display_name}
                                    {hasChanges && (
                                        <span className="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Unsaved Changes
                                        </span>
                                    )}
                                </h2>
                                <p className="text-sm text-gray-600">
                                    Modify role permissions and settings • {role.users_count} users assigned
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        {hasChanges && (
                            <button
                                type="button"
                                onClick={resetChanges}
                                className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                <X className="w-4 h-4 mr-2" />
                                Reset
                            </button>
                        )}
                        <div className="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-lg">
                            {permissionStats.selected} of {permissionStats.total} permissions
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`Edit Role: ${role.display_name}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Warning for Super Admin */}
                    {role.name === 'super-admin' && (
                        <div className="mb-6 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg flex items-center">
                            <AlertCircle className="w-5 h-5 mr-2" />
                            <div>
                                <strong>Warning:</strong> You are editing the Super Admin role. Changes to this role affect system-wide administrative access.
                            </div>
                        </div>
                    )}

                    {/* Users Assignment Warning */}
                    {role.users_count > 0 && (
                        <div className="mb-6 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg flex items-center">
                            <Info className="w-5 h-5 mr-2" />
                            <div>
                                <strong>Note:</strong> This role is currently assigned to {role.users_count} user(s). Changes will affect their access immediately.
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Form */}
                        <div className="lg:col-span-2">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Role Basic Information */}
                                <div className="bg-white shadow-sm rounded-lg p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Role Information</h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                                Role Name <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="name"
                                                value={data.name}
                                                onChange={e => handleInputChange('name', e.target.value)}
                                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                    errors.name ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                                placeholder="e.g., parish-secretary"
                                                required
                                                disabled={role.name === 'super-admin'}
                                            />
                                            {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                                            {role.name === 'super-admin' && (
                                                <p className="text-xs text-gray-500 mt-1">Super Admin role name cannot be changed</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="display_name" className="block text-sm font-medium text-gray-700 mb-2">
                                                Display Name <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="display_name"
                                                value={data.display_name}
                                                onChange={e => handleInputChange('display_name', e.target.value)}
                                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                    errors.display_name ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                                placeholder="e.g., Parish Secretary"
                                                required
                                            />
                                            {errors.display_name && <p className="text-red-500 text-sm mt-1">{errors.display_name}</p>}
                                        </div>
                                    </div>

                                    <div className="mt-6">
                                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                            Description
                                        </label>
                                        <textarea
                                            id="description"
                                            rows={3}
                                            value={data.description}
                                            onChange={e => handleInputChange('description', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Brief description of this role's responsibilities and access level..."
                                        />
                                    </div>

                                    <div className="mt-6">
                                        <label htmlFor="clearance_level" className="block text-sm font-medium text-gray-700 mb-2">
                                            Clearance Level
                                        </label>
                                        <select
                                            id="clearance_level"
                                            value={data.clearance_level}
                                            onChange={e => handleInputChange('clearance_level', parseInt(e.target.value))}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            disabled={role.name === 'super-admin'}
                                        >
                                            <option value={1}>Level 1 - Viewer (Read-only access)</option>
                                            <option value={2}>Level 2 - Staff (Basic operations)</option>
                                            <option value={3}>Level 3 - Manager (Management access)</option>
                                            <option value={4}>Level 4 - Admin (Administrative access)</option>
                                            <option value={5}>Level 5 - Super Admin (Full access)</option>
                                        </select>
                                        {role.name === 'super-admin' && (
                                            <p className="text-xs text-gray-500 mt-1">Super Admin clearance level cannot be changed</p>
                                        )}
                                    </div>
                                </div>

                                {/* Permission Selection */}
                                <div className="bg-white shadow-sm rounded-lg p-6">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-medium text-gray-900">Permissions</h3>
                                        <div className="flex items-center space-x-4">
                                            <div className="text-sm text-gray-600">
                                                <span className="font-medium text-blue-600">{permissionStats.selected}</span> of {permissionStats.total} selected
                                            </div>
                                            {role.name !== 'super-admin' && (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setData('permissions', data.permissions?.length === permissionStats.total ? [] : getAllPermissions());
                                                        setHasChanges(true);
                                                    }}
                                                    className="text-sm text-blue-600 hover:text-blue-800"
                                                >
                                                    {data.permissions?.length === permissionStats.total ? 'Deselect All' : 'Select All'}
                                                </button>
                                            )}
                                        </div>
                                    </div>

                                    {role.name === 'super-admin' && (
                                        <div className="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                                            <div className="flex items-center">
                                                <Crown className="w-5 h-5 text-purple-600 mr-2" />
                                                <div className="text-sm text-purple-800">
                                                    <strong>Super Admin Role:</strong> This role automatically has all permissions and cannot be restricted.
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Permission Statistics */}
                                    <div className="grid grid-cols-4 gap-4 mb-6">
                                        <div className="bg-blue-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-blue-600">{permissionStats.readOnly}</div>
                                            <div className="text-xs text-blue-800">Read Access</div>
                                        </div>
                                        <div className="bg-red-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-red-600">{permissionStats.writeAccess}</div>
                                            <div className="text-xs text-red-800">Write Access</div>
                                        </div>
                                        <div className="bg-green-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-green-600">{permissionStats.exportAccess}</div>
                                            <div className="text-xs text-green-800">Export Access</div>
                                        </div>
                                        <div className="bg-gray-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-gray-600">{permissionStats.selected}</div>
                                            <div className="text-xs text-gray-800">Total Selected</div>
                                        </div>
                                    </div>

                                    {/* Permissions by Module */}
                                    <div className="space-y-4">
                                        {Object.entries(permissions).map(([module, modulePermissions]) => {
                                            const IconComponent = getModuleIcon(module);
                                            const isExpanded = expandedModules.includes(module);
                                            const selectedInModule = modulePermissions.filter(p => data.permissions?.includes(p.name)).length;
                                            
                                            return (
                                                <div key={module} className={`border border-gray-200 rounded-lg ${getModuleColor(module)}`}>
                                                    <div 
                                                        className="p-4 cursor-pointer hover:bg-opacity-80 transition-colors"
                                                        onClick={() => toggleModule(module)}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex items-center space-x-3">
                                                                <IconComponent className="w-5 h-5" />
                                                                <h4 className="font-medium capitalize">{module}</h4>
                                                                <span className="text-sm">
                                                                    {selectedInModule}/{modulePermissions.length} selected
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center space-x-2">
                                                                {role.name !== 'super-admin' && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={(e) => {
                                                                            e.stopPropagation();
                                                                            toggleModulePermissions(module, modulePermissions);
                                                                        }}
                                                                        className="text-xs px-2 py-1 bg-white bg-opacity-50 rounded hover:bg-opacity-75 transition-colors"
                                                                    >
                                                                        {selectedInModule === modulePermissions.length ? 'Deselect All' : 'Select All'}
                                                                    </button>
                                                                )}
                                                                <div className={`transform transition-transform ${isExpanded ? 'rotate-180' : ''}`}>
                                                                    <ArrowLeft className="w-4 h-4 rotate-90" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    {isExpanded && (
                                                        <div className="px-4 pb-4">
                                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                {modulePermissions.map(permission => {
                                                                    const isSelected = data.permissions?.includes(permission.name);
                                                                    const permissionType = getPermissionType(permission.name);
                                                                    
                                                                    return (
                                                                        <label 
                                                                            key={permission.id}
                                                                            className="flex items-center space-x-2 p-2 bg-white bg-opacity-50 rounded hover:bg-opacity-75 cursor-pointer transition-colors"
                                                                        >
                                                                            <input
                                                                                type="checkbox"
                                                                                checked={isSelected}
                                                                                onChange={() => role.name !== 'super-admin' && togglePermission(permission.name)}
                                                                                className="rounded text-blue-600 focus:ring-blue-500"
                                                                                disabled={role.name === 'super-admin'}
                                                                            />
                                                                            <span className="text-sm flex-1">{permission.name}</span>
                                                                            <span className={`text-xs px-2 py-0.5 rounded-full border ${getPermissionTypeColor(permissionType)}`}>
                                                                                {permissionType}
                                                                            </span>
                                                                        </label>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {errors.permissions && (
                                        <p className="text-red-500 text-sm mt-2">{errors.permissions}</p>
                                    )}
                                </div>

                                {/* Submit Button */}
                                <div className="flex justify-end space-x-4">
                                    <Link
                                        href={route('admin.roles.show', role.id)}
                                        className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing || !hasChanges}
                                        className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center"
                                    >
                                        <Save className="w-4 h-4 mr-2" />
                                        {processing ? 'Saving...' : 'Save Changes'}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {/* Current Role Info Sidebar */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow-sm rounded-lg p-6 sticky top-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Current Role Info</h3>
                                
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Original Name</label>
                                        <p className="mt-1 text-sm text-gray-900 font-mono bg-gray-50 px-2 py-1 rounded">{role.name}</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Assigned Users</label>
                                        <p className="mt-1 text-sm text-gray-900">{role.users_count} users</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Original Permissions</label>
                                        <p className="mt-1 text-sm text-gray-900">{role.permissions.length} permissions</p>
                                    </div>
                                </div>

                                {hasChanges && (
                                    <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <h4 className="font-medium text-sm text-yellow-800 mb-2">Pending Changes</h4>
                                        <div className="space-y-2 text-xs text-yellow-700">
                                            {data.name !== role.name && <div>• Name: {data.name}</div>}
                                            {data.display_name !== role.display_name && <div>• Display: {data.display_name}</div>}
                                            {data.permissions?.length !== role.permissions.length && (
                                                <div>• Permissions: {data.permissions?.length || 0} (was {role.permissions.length})</div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
