import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Users, 
    Search, 
    Filter, 
    Plus, 
    Edit, 
    Eye, 
    Trash2, 
    UserCheck, 
    UserX, 
    Shield,
    Mail,
    Phone,
    Calendar
} from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    is_active: boolean;
    created_at: string;
    last_login_at?: string;
    roles: Array<{
        id: number;
        name: string;
    }>;
    created_by?: {
        name: string;
    };
}

interface Role {
    id: number;
    name: string;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        role?: string;
        status?: string;
    };
    roles: Role[];
    can: {
        create_user: boolean;
        edit_user: boolean;
        delete_user: boolean;
    };
}

const getRoleBadgeColor = (roleName: string) => {
    const colors = {
        'super-admin': 'bg-red-100 text-red-800 border border-red-200',
        'admin': 'bg-orange-100 text-orange-800 border border-orange-200',
        'manager': 'bg-blue-100 text-blue-800 border border-blue-200',
        'staff': 'bg-green-100 text-green-800 border border-green-200',
        'secretary': 'bg-green-100 text-green-800 border border-green-200',
        'treasurer': 'bg-green-100 text-green-800 border border-green-200',
        'viewer': 'bg-gray-100 text-gray-800 border border-gray-200',
    };
    return colors[roleName as keyof typeof colors] || colors.viewer;
};

const getClearanceLevel = (roleName: string): number => {
    const levels = {
        'super-admin': 5,
        'admin': 4,
        'manager': 3,
        'staff': 2,
        'secretary': 2,
        'treasurer': 2,
        'viewer': 1,
    };
    return levels[roleName as keyof typeof levels] || 1;
};

export default function Index({ users, filters, roles, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedRole, setSelectedRole] = useState(filters.role || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleSearch = () => {
        router.get(route('admin.users.index'), {
            search: search || undefined,
            role: selectedRole || undefined,
            status: selectedStatus || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleClearFilters = () => {
        setSearch('');
        setSelectedRole('');
        setSelectedStatus('');
        router.get(route('admin.users.index'));
    };

    const handleToggleStatus = (userId: number) => {
        router.patch(route('admin.users.toggle-status', userId), {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (userId: number) => {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            router.delete(route('admin.users.destroy', userId));
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const formatLastLogin = (dateString: string | null) => {
        if (!dateString) return 'Never';
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now.getTime() - date.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        return formatDate(dateString);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                        <Users className="h-6 w-6" />
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            User Management
                        </h2>
                    </div>
                    {can.create_user && (
                        <Link href={route('admin.users.create')}>
                            <button className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center transition-colors">
                                <Plus className="h-4 w-4 mr-2" />
                                Add User
                            </button>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="User Management" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white rounded-lg border border-gray-200 shadow-sm mb-6">
                        <div className="flex flex-col space-y-1.5 p-6">
                            <h3 className="text-lg font-semibold leading-none tracking-tight flex items-center">
                                <Filter className="h-5 w-5 mr-2" />
                                Filters
                            </h3>
                        </div>
                        <div className="p-6 pt-0">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div className="relative">
                                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                    <input
                                        className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 pl-10 text-sm placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                                        placeholder="Search users..."
                                        value={search}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                                        onKeyPress={(e: React.KeyboardEvent<HTMLInputElement>) => e.key === 'Enter' && handleSearch()}
                                    />
                                </div>
                                
                                <select 
                                    value={selectedRole} 
                                    onChange={(e) => setSelectedRole(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                                >
                                    <option value="">All Roles</option>
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.name}>
                                            {role.name.split('-').map(word => 
                                                word.charAt(0).toUpperCase() + word.slice(1)
                                            ).join(' ')}
                                        </option>
                                    ))}
                                </select>

                                <select 
                                    value={selectedStatus} 
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>

                                <div className="flex space-x-2">
                                    <button 
                                        onClick={handleSearch} 
                                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors"
                                    >
                                        Search
                                    </button>
                                    <button 
                                        onClick={handleClearFilters}
                                        className="flex-1 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md transition-colors"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Results Summary */}
                    <div className="mb-4 text-sm text-gray-600">
                        Showing {users.data.length} of {users.total} users
                    </div>

                    {/* Users Table */}
                    <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contact
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Role & Level
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Last Login
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {users.data.map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <div className="flex-shrink-0 h-10 w-10">
                                                        <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <span className="text-sm font-medium text-blue-600">
                                                                {user.name.charAt(0).toUpperCase()}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {user.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            ID: {user.id}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="space-y-1">
                                                    <div className="flex items-center text-sm text-gray-900">
                                                        <Mail className="h-3 w-3 mr-1 text-gray-400" />
                                                        {user.email}
                                                    </div>
                                                    {user.phone && (
                                                        <div className="flex items-center text-sm text-gray-500">
                                                            <Phone className="h-3 w-3 mr-1 text-gray-400" />
                                                            {user.phone}
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="space-y-2">
                                                    {user.roles.map((role) => (
                                                        <div key={role.id} className="flex items-center space-x-2">
                                                            <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${getRoleBadgeColor(role.name)}`}>
                                                                <Shield className="h-3 w-3 mr-1" />
                                                                {role.name.split('-').map(word => 
                                                                    word.charAt(0).toUpperCase() + word.slice(1)
                                                                ).join(' ')}
                                                            </span>
                                                            <span className="text-xs text-gray-500">
                                                                Level {getClearanceLevel(role.name)}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border ${user.is_active 
                                                    ? 'bg-green-100 text-green-800 border-green-200' 
                                                    : 'bg-red-100 text-red-800 border-red-200'
                                                }`}>
                                                    {user.is_active ? (
                                                        <>
                                                            <UserCheck className="h-3 w-3 mr-1" />
                                                            Active
                                                        </>
                                                    ) : (
                                                        <>
                                                            <UserX className="h-3 w-3 mr-1" />
                                                            Inactive
                                                        </>
                                                    )}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500">
                                                <div className="flex items-center">
                                                    <Calendar className="h-3 w-3 mr-1" />
                                                    {formatLastLogin(user.last_login_at || null)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500">
                                                <div>
                                                    {formatDate(user.created_at)}
                                                </div>
                                                {user.created_by && (
                                                    <div className="text-xs text-gray-400">
                                                        by {user.created_by.name}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm font-medium">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <Link 
                                                        href={route('admin.users.show', user.id)}
                                                        className="text-blue-600 hover:text-blue-900 p-1 rounded transition-colors"
                                                        title="View User"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                    {can.edit_user && (
                                                        <Link 
                                                            href={route('admin.users.edit', user.id)}
                                                            className="text-green-600 hover:text-green-900 p-1 rounded transition-colors"
                                                            title="Edit User"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    )}
                                                    <button 
                                                        onClick={() => handleToggleStatus(user.id)}
                                                        className={`p-1 rounded transition-colors ${user.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900'}`}
                                                        title={user.is_active ? 'Deactivate User' : 'Activate User'}
                                                    >
                                                        {user.is_active ? <UserX className="h-4 w-4" /> : <UserCheck className="h-4 w-4" />}
                                                    </button>
                                                    {can.delete_user && (
                                                        <button 
                                                            onClick={() => handleDelete(user.id)}
                                                            className="text-red-600 hover:text-red-900 p-1 rounded transition-colors"
                                                            title="Delete User"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Empty State */}
                        {users.data.length === 0 && (
                            <div className="text-center py-12">
                                <Users className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    {(search || selectedRole || selectedStatus) 
                                        ? 'Try adjusting your search criteria.' 
                                        : 'Get started by creating your first user.'
                                    }
                                </p>
                                {can.create_user && !(search || selectedRole || selectedStatus) && (
                                    <div className="mt-6">
                                        <Link href={route('admin.users.create')}>
                                            <button className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center transition-colors">
                                                <Plus className="h-4 w-4 mr-2" />
                                                Add User
                                            </button>
                                        </Link>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Simple Pagination */}
                        {users.last_page > 1 && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Showing page <span className="font-medium">{users.current_page}</span> of{' '}
                                        <span className="font-medium">{users.last_page}</span> 
                                        {' '}({users.total} total users)
                                    </div>
                                    <div className="flex space-x-2">
                                        {users.current_page > 1 && (
                                            <Link
                                                href={route('admin.users.index', { ...filters, page: users.current_page - 1 })}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {users.current_page < users.last_page && (
                                            <Link
                                                href={route('admin.users.index', { ...filters, page: users.current_page + 1 })}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
