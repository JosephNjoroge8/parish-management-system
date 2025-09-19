import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
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
    Crown,
    Award,
    Star,
    User,
    Calendar,
    Mail,
    UserCheck,
    UserX,
    Activity,
    Database,
    FileText,
    DollarSign,
    BarChart3,
    Home
} from 'lucide-react';

interface Permission {
    id: number;
    name: string;
    display_name?: string;
}

interface RoleUser {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
}

interface Role {
    id: number;
    name: string;
    display_name: string;
    permissions: string[];
    users: RoleUser[];
    created_at: string;
    description?: string;
}

interface ShowRoleProps {
    role: Role;
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

const getRoleColor = (roleName: string) => {
    const name = roleName.toLowerCase();
    if (name.includes('super')) return 'bg-purple-100 text-purple-800 border-purple-200';
    if (name.includes('admin')) return 'bg-red-100 text-red-800 border-red-200';
    if (name.includes('manager')) return 'bg-blue-100 text-blue-800 border-blue-200';
    if (name.includes('staff') || name.includes('secretary')) return 'bg-green-100 text-green-800 border-green-200';
    if (name.includes('treasurer')) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    return 'bg-gray-100 text-gray-800 border-gray-200';
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

export default function ShowRole({ role }: ShowRoleProps) {
    const { flash } = usePage().props as any;
    const [activeTab, setActiveTab] = useState<'overview' | 'permissions' | 'users'>('overview');
    
    const IconComponent = getRoleIcon(role.name);
    const clearanceLevel = getClearanceLevel(role.name);

    // Group permissions by module
    const groupedPermissions = role.permissions.reduce((groups: {[key: string]: string[]}, permission) => {
        const module = permission.split(' ')[1] || 'other';
        if (!groups[module]) {
            groups[module] = [];
        }
        groups[module].push(permission);
        return groups;
    }, {});

    // Permission statistics
    const permissionStats = {
        total: role.permissions.length,
        readOnly: role.permissions.filter(p => getPermissionType(p) === 'read').length,
        writeAccess: role.permissions.filter(p => getPermissionType(p) === 'write').length,
        exportAccess: role.permissions.filter(p => getPermissionType(p) === 'export').length
    };

    const handleDeleteRole = () => {
        if (role.users.length > 0) {
            alert(`Cannot delete role "${role.display_name}" because it has ${role.users.length} assigned users.`);
            return;
        }
        
        if (confirm(`Are you sure you want to delete the role "${role.display_name}"? This action cannot be undone.`)) {
            router.delete(route('admin.roles.destroy', role.id));
        }
    };

    const handleToggleUserStatus = (userId: number) => {
        if (confirm('Are you sure you want to toggle this user\'s status?')) {
            router.patch(route('admin.users.toggle-status', userId));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link 
                            href={route('admin.roles.index')} 
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
                                    {role.display_name}
                                    <span className={`ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${getRoleColor(role.name)}`}>
                                        Level {clearanceLevel}
                                    </span>
                                </h2>
                                <p className="text-sm text-gray-600">
                                    {role.permissions.length} permissions • {role.users.length} users • Created {new Date(role.created_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Link
                            href={route('admin.roles.edit', role.id)}
                            className="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
                        >
                            <Edit className="w-4 h-4 mr-2" />
                            Edit Role
                        </Link>
                        {role.name !== 'super-admin' && (
                            <button
                                onClick={handleDeleteRole}
                                className="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                            >
                                <Trash2 className="w-4 h-4 mr-2" />
                                Delete
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Role: ${role.display_name}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Flash Messages */}
                    {flash?.success && (
                        <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center">
                            <Check className="w-5 h-5 mr-2" />
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center">
                            <AlertCircle className="w-5 h-5 mr-2" />
                            {flash.error}
                        </div>
                    )}

                    {/* Tab Navigation */}
                    <div className="bg-white shadow-sm rounded-lg mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8 px-6">
                                <button
                                    onClick={() => setActiveTab('overview')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'overview'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <Info className="w-4 h-4 inline mr-2" />
                                    Overview
                                </button>
                                <button
                                    onClick={() => setActiveTab('permissions')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'permissions'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <Lock className="w-4 h-4 inline mr-2" />
                                    Permissions ({role.permissions.length})
                                </button>
                                <button
                                    onClick={() => setActiveTab('users')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'users'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <Users className="w-4 h-4 inline mr-2" />
                                    Users ({role.users.length})
                                </button>
                            </nav>
                        </div>
                    </div>

                    {/* Tab Content */}
                    {activeTab === 'overview' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Role Information */}
                            <div className="lg:col-span-2">
                                <div className="bg-white shadow-sm rounded-lg p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Role Information</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Role Name</label>
                                            <p className="mt-1 text-sm text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">{role.name}</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Display Name</label>
                                            <p className="mt-1 text-sm text-gray-900">{role.display_name}</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Clearance Level</label>
                                            <p className="mt-1">
                                                <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border ${getRoleColor(role.name)}`}>
                                                    Level {clearanceLevel}
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Created At</label>
                                            <p className="mt-1 text-sm text-gray-900">{new Date(role.created_at).toLocaleString()}</p>
                                        </div>
                                    </div>
                                    {role.description && (
                                        <div className="mt-6">
                                            <label className="block text-sm font-medium text-gray-700">Description</label>
                                            <p className="mt-1 text-sm text-gray-900">{role.description}</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Quick Stats */}
                            <div className="lg:col-span-1">
                                <div className="bg-white shadow-sm rounded-lg p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Statistics</h3>
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600">Total Permissions</span>
                                            <span className="text-lg font-bold text-blue-600">{permissionStats.total}</span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600">Read Access</span>
                                            <span className="text-lg font-bold text-blue-600">{permissionStats.readOnly}</span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600">Write Access</span>
                                            <span className="text-lg font-bold text-red-600">{permissionStats.writeAccess}</span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600">Export Access</span>
                                            <span className="text-lg font-bold text-green-600">{permissionStats.exportAccess}</span>
                                        </div>
                                        <hr className="my-4" />
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600">Assigned Users</span>
                                            <span className="text-lg font-bold text-purple-600">{role.users.length}</span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600">Active Users</span>
                                            <span className="text-lg font-bold text-green-600">
                                                {role.users.filter(u => u.is_active).length}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'permissions' && (
                        <div className="bg-white shadow-sm rounded-lg p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-medium text-gray-900">Role Permissions</h3>
                                <div className="grid grid-cols-4 gap-4 text-center">
                                    <div className="bg-blue-50 p-2 rounded">
                                        <div className="text-lg font-bold text-blue-600">{permissionStats.readOnly}</div>
                                        <div className="text-xs text-blue-800">Read</div>
                                    </div>
                                    <div className="bg-red-50 p-2 rounded">
                                        <div className="text-lg font-bold text-red-600">{permissionStats.writeAccess}</div>
                                        <div className="text-xs text-red-800">Write</div>
                                    </div>
                                    <div className="bg-green-50 p-2 rounded">
                                        <div className="text-lg font-bold text-green-600">{permissionStats.exportAccess}</div>
                                        <div className="text-xs text-green-800">Export</div>
                                    </div>
                                    <div className="bg-gray-50 p-2 rounded">
                                        <div className="text-lg font-bold text-gray-600">{permissionStats.total}</div>
                                        <div className="text-xs text-gray-800">Total</div>
                                    </div>
                                </div>
                            </div>

                            {Object.keys(groupedPermissions).length === 0 ? (
                                <div className="text-center py-12">
                                    <Lock className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No permissions assigned</h3>
                                    <p className="mt-1 text-sm text-gray-500">This role has no permissions assigned to it.</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {Object.entries(groupedPermissions).map(([module, modulePermissions]) => {
                                        const IconComponent = getModuleIcon(module);
                                        
                                        return (
                                            <div key={module} className={`border border-gray-200 rounded-lg ${getModuleColor(module)}`}>
                                                <div className="p-4">
                                                    <div className="flex items-center space-x-2 mb-3">
                                                        <IconComponent className="w-5 h-5" />
                                                        <h4 className="font-medium capitalize">{module}</h4>
                                                        <span className="text-sm">({modulePermissions.length})</span>
                                                    </div>
                                                    <div className="space-y-2">
                                                        {modulePermissions.map(permission => {
                                                            const permissionType = getPermissionType(permission);
                                                            
                                                            return (
                                                                <div key={permission} className="flex items-center justify-between bg-white bg-opacity-50 p-2 rounded">
                                                                    <span className="text-sm">{permission}</span>
                                                                    <span className={`text-xs px-2 py-0.5 rounded-full border ${getPermissionTypeColor(permissionType)}`}>
                                                                        {permissionType}
                                                                    </span>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'users' && (
                        <div className="bg-white shadow-sm rounded-lg p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-medium text-gray-900">Users with this Role</h3>
                                <div className="text-sm text-gray-600">
                                    {role.users.filter(u => u.is_active).length} of {role.users.length} users active
                                </div>
                            </div>

                            {role.users.length === 0 ? (
                                <div className="text-center py-12">
                                    <Users className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No users assigned</h3>
                                    <p className="mt-1 text-sm text-gray-500">No users have been assigned to this role yet.</p>
                                    <div className="mt-6">
                                        <Link
                                            href={route('admin.users.create')}
                                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            <Users className="w-4 h-4 mr-2" />
                                            Create User
                                        </Link>
                                    </div>
                                </div>
                            ) : (
                                <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                    <table className="min-w-full divide-y divide-gray-300">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {role.users.map(user => (
                                                <tr key={user.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0 h-8 w-8">
                                                                <div className="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                                    <User className="h-4 w-4 text-gray-500" />
                                                                </div>
                                                            </div>
                                                            <div className="ml-3">
                                                                <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900">{user.email}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {user.is_active ? (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                <UserCheck className="w-3 h-3 mr-1" />
                                                                Active
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                <UserX className="w-3 h-3 mr-1" />
                                                                Inactive
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                                        <Link
                                                            href={route('admin.users.show', user.id)}
                                                            className="text-blue-600 hover:text-blue-900"
                                                        >
                                                            <Eye className="w-4 h-4 inline" />
                                                        </Link>
                                                        <Link
                                                            href={route('admin.users.edit', user.id)}
                                                            className="text-yellow-600 hover:text-yellow-900"
                                                        >
                                                            <Edit className="w-4 h-4 inline" />
                                                        </Link>
                                                        <button
                                                            onClick={() => handleToggleUserStatus(user.id)}
                                                            className={`${user.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'}`}
                                                        >
                                                            {user.is_active ? <UserX className="w-4 h-4 inline" /> : <UserCheck className="w-4 h-4 inline" />}
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
