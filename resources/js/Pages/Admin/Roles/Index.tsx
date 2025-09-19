import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Plus, 
    Shield, 
    Users, 
    Settings, 
    Eye, 
    Edit, 
    Trash2,
    Crown,
    Award,
    Star,
    User,
    AlertCircle,
    Check,
    X,
    ChevronRight,
    Lock,
    Unlock,
    UserCheck,
    Search,
    Filter
} from 'lucide-react';

interface Permission {
    id: number;
    name: string;
    display_name?: string;
}

interface Role {
    id: number;
    name: string;
    display_name: string;
    permissions_count: number;
    users_count: number;
    created_at: string;
    permissions?: Permission[];
    clearance_level?: number;
    description?: string;
}

interface RolesIndexProps {
    roles: Role[];
}

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

export default function RolesIndex({ roles }: RolesIndexProps) {
    const { flash } = usePage().props as any;
    const [searchTerm, setSearchTerm] = useState('');
    const [filterLevel, setFilterLevel] = useState<number | null>(null);
    const [selectedRole, setSelectedRole] = useState<Role | null>(null);

    // Sort roles by clearance level (highest first)
    const sortedRoles = [...roles].sort((a, b) => {
        const levelA = getClearanceLevel(a.name);
        const levelB = getClearanceLevel(b.name);
        return levelB - levelA;
    });

    // Filter roles based on search and level
    const filteredRoles = sortedRoles.filter(role => {
        const matchesSearch = role.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            role.display_name.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesLevel = filterLevel === null || getClearanceLevel(role.name) === filterLevel;
        return matchesSearch && matchesLevel;
    });

    const handleDeleteRole = (role: Role) => {
        if (role.users_count > 0) {
            alert(`Cannot delete role "${role.display_name}" because it has ${role.users_count} assigned users.`);
            return;
        }
        
        if (confirm(`Are you sure you want to delete the role "${role.display_name}"? This action cannot be undone.`)) {
            router.delete(route('admin.roles.destroy', role.id));
        }
    };

    const clearanceLevels = [
        { level: 5, name: 'Super Admin', description: 'Full system access' },
        { level: 4, name: 'Admin', description: 'Administrative access' },
        { level: 3, name: 'Manager', description: 'Management access' },
        { level: 2, name: 'Staff', description: 'Staff operations' },
        { level: 1, name: 'Viewer', description: 'Read-only access' }
    ];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                            <Shield className="w-6 h-6 mr-2 text-blue-600" />
                            Role & Permission Management
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage system roles and permissions with hierarchical access control
                        </p>
                    </div>
                    <Link
                        href={route('admin.roles.create')}
                        className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <Plus className="w-4 h-4 mr-2" />
                        Create Role
                    </Link>
                </div>
            }
        >
            <Head title="Roles & Permissions" />

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

                    {/* Role Statistics & Quick Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Shield className="h-8 w-8 text-blue-600" />
                                    </div>
                                    <div className="ml-4">
                                        <dt className="text-sm font-medium text-gray-500 truncate">Total Roles</dt>
                                        <dd className="text-2xl font-bold text-gray-900">{roles.length}</dd>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-green-600" />
                                    </div>
                                    <div className="ml-4">
                                        <dt className="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                        <dd className="text-2xl font-bold text-gray-900">
                                            {roles.reduce((sum, role) => sum + role.users_count, 0)}
                                        </dd>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Settings className="h-8 w-8 text-yellow-600" />
                                    </div>
                                    <div className="ml-4">
                                        <dt className="text-sm font-medium text-gray-500 truncate">Avg Permissions</dt>
                                        <dd className="text-2xl font-bold text-gray-900">
                                            {roles.length > 0 ? Math.round(roles.reduce((sum, role) => sum + role.permissions_count, 0) / roles.length) : 0}
                                        </dd>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Crown className="h-8 w-8 text-purple-600" />
                                    </div>
                                    <div className="ml-4">
                                        <dt className="text-sm font-medium text-gray-500 truncate">Clearance Levels</dt>
                                        <dd className="text-2xl font-bold text-gray-900">
                                            {new Set(roles.map(role => getClearanceLevel(role.name))).size}
                                        </dd>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filter */}
                    <div className="bg-white shadow-sm rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div className="flex-1 max-w-lg">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                        <input
                                            type="text"
                                            placeholder="Search roles..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center space-x-4">
                                    <div className="flex items-center space-x-2">
                                        <Filter className="w-5 h-5 text-gray-400" />
                                        <select
                                            value={filterLevel || ''}
                                            onChange={(e) => setFilterLevel(e.target.value ? parseInt(e.target.value) : null)}
                                            className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">All Levels</option>
                                            {clearanceLevels.map(level => (
                                                <option key={level.level} value={level.level}>
                                                    Level {level.level} - {level.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Clearance Level Guide */}
                    <div className="bg-white shadow-sm rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Clearance Level Hierarchy</h3>
                            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                                {clearanceLevels.map((level, index) => (
                                    <div key={level.level} className="relative">
                                        <div className={`p-4 rounded-lg border-2 ${
                                            level.level === 5 ? 'bg-purple-50 border-purple-200' :
                                            level.level === 4 ? 'bg-red-50 border-red-200' :
                                            level.level === 3 ? 'bg-blue-50 border-blue-200' :
                                            level.level === 2 ? 'bg-green-50 border-green-200' :
                                            'bg-gray-50 border-gray-200'
                                        }`}>
                                            <div className="text-center">
                                                <div className={`inline-flex items-center justify-center w-8 h-8 rounded-full mb-2 ${
                                                    level.level === 5 ? 'bg-purple-600 text-white' :
                                                    level.level === 4 ? 'bg-red-600 text-white' :
                                                    level.level === 3 ? 'bg-blue-600 text-white' :
                                                    level.level === 2 ? 'bg-green-600 text-white' :
                                                    'bg-gray-600 text-white'
                                                }`}>
                                                    {level.level}
                                                </div>
                                                <div className="font-medium text-sm">{level.name}</div>
                                                <div className="text-xs text-gray-500 mt-1">{level.description}</div>
                                            </div>
                                        </div>
                                        {index < clearanceLevels.length - 1 && (
                                            <ChevronRight className="hidden md:block absolute -right-6 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Roles List */}
                    <div className="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-medium text-gray-900">System Roles</h3>
                            <p className="text-sm text-gray-500 mt-1">
                                {filteredRoles.length} of {roles.length} roles {searchTerm && `matching "${searchTerm}"`}
                            </p>
                        </div>
                        
                        <div className="divide-y divide-gray-200">
                            {filteredRoles.length === 0 ? (
                                <div className="px-6 py-12 text-center">
                                    <Shield className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No roles found</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {searchTerm || filterLevel ? 'Try adjusting your search or filter criteria.' : 'Get started by creating a new role.'}
                                    </p>
                                    {!searchTerm && !filterLevel && (
                                        <div className="mt-6">
                                            <Link
                                                href={route('admin.roles.create')}
                                                className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                            >
                                                <Plus className="w-4 h-4 mr-2" />
                                                Create Role
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                filteredRoles.map((role) => {
                                    const IconComponent = getRoleIcon(role.name);
                                    const clearanceLevel = getClearanceLevel(role.name);
                                    
                                    return (
                                        <div key={role.id} className="px-6 py-4 hover:bg-gray-50 transition-colors">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center flex-1">
                                                    <div className="flex-shrink-0">
                                                        <div className="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100">
                                                            <IconComponent className="w-5 h-5 text-gray-600" />
                                                        </div>
                                                    </div>
                                                    <div className="ml-4 flex-1">
                                                        <div className="flex items-center space-x-2">
                                                            <h4 className="text-lg font-medium text-gray-900">{role.display_name}</h4>
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${getRoleColor(role.name)}`}>
                                                                Level {clearanceLevel}
                                                            </span>
                                                        </div>
                                                        <div className="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                            <span className="flex items-center">
                                                                <Settings className="w-4 h-4 mr-1" />
                                                                {role.permissions_count} permissions
                                                            </span>
                                                            <span className="flex items-center">
                                                                <Users className="w-4 h-4 mr-1" />
                                                                {role.users_count} users
                                                            </span>
                                                            <span className="flex items-center">
                                                                <Shield className="w-4 h-4 mr-1" />
                                                                Created {new Date(role.created_at).toLocaleDateString()}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Link
                                                        href={route('admin.roles.show', role.id)}
                                                        className="inline-flex items-center px-3 py-1.5 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors"
                                                        title="View Details"
                                                    >
                                                        <Eye className="w-4 h-4 mr-1" />
                                                        View
                                                    </Link>
                                                    <Link
                                                        href={route('admin.roles.edit', role.id)}
                                                        className="inline-flex items-center px-3 py-1.5 text-sm text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 rounded-lg transition-colors"
                                                        title="Edit Role"
                                                    >
                                                        <Edit className="w-4 h-4 mr-1" />
                                                        Edit
                                                    </Link>
                                                    {role.name !== 'super-admin' && (
                                                        <button
                                                            onClick={() => handleDeleteRole(role)}
                                                            className="inline-flex items-center px-3 py-1.5 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors"
                                                            title="Delete Role"
                                                        >
                                                            <Trash2 className="w-4 h-4 mr-1" />
                                                            Delete
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
