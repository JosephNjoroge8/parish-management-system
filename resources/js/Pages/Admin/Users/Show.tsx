import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    User, 
    ArrowLeft, 
    Edit, 
    Trash2, 
    Mail, 
    Phone, 
    Calendar, 
    Shield, 
    UserCheck, 
    UserX,
    Clock,
    MapPin,
    Activity
} from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    last_login_at?: string;
    email_verified_at?: string;
    roles: Array<{
        id: number;
        name: string;
        display_name: string;
        clearance_level: number;
        permissions: Array<{
            id: number;
            name: string;
            display_name: string;
        }>;
    }>;
    created_by?: {
        id: number;
        name: string;
    };
    updated_by?: {
        id: number;
        name: string;
    };
}

interface Props {
    user: User;
    can: {
        edit_user: boolean;
        delete_user: boolean;
    };
}

export default function Show({ user, can }: Props) {
    const handleToggleStatus = () => {
        router.patch(route('admin.users.toggle-status', user.id), {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            router.delete(route('admin.users.destroy', user.id), {
                onSuccess: () => {
                    router.visit(route('admin.users.index'));
                }
            });
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatLastLogin = (dateString: string | null) => {
        if (!dateString) return 'Never logged in';
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now.getTime() - date.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        return formatDate(dateString);
    };

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

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                        <User className="h-6 w-6" />
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            User Details
                        </h2>
                    </div>
                    <div className="flex items-center space-x-4">
                        {can.edit_user && (
                            <Link href={route('admin.users.edit', user.id)}>
                                <button className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center transition-colors">
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit User
                                </button>
                            </Link>
                        )}
                        <Link href={route('admin.users.index')}>
                            <button className="border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md inline-flex items-center transition-colors">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Back to Users
                            </button>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`User: ${user.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* User Overview Card */}
                    <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div className="p-6">
                            <div className="flex items-start justify-between">
                                <div className="flex items-center space-x-4">
                                    <div className="h-20 w-20 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span className="text-2xl font-bold text-blue-600">
                                            {user.name.charAt(0).toUpperCase()}
                                        </span>
                                    </div>
                                    <div>
                                        <h1 className="text-2xl font-bold text-gray-900">{user.name}</h1>
                                        <p className="text-gray-600">User ID: {user.id}</p>
                                        <div className="flex items-center mt-2">
                                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold border ${user.is_active 
                                                ? 'bg-green-100 text-green-800 border-green-200' 
                                                : 'bg-red-100 text-red-800 border-red-200'
                                            }`}>
                                                {user.is_active ? (
                                                    <>
                                                        <UserCheck className="h-4 w-4 mr-1" />
                                                        Active
                                                    </>
                                                ) : (
                                                    <>
                                                        <UserX className="h-4 w-4 mr-1" />
                                                        Inactive
                                                    </>
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex space-x-2">
                                    <button 
                                        onClick={handleToggleStatus}
                                        className={`p-2 rounded-md transition-colors ${user.is_active 
                                            ? 'text-orange-600 hover:text-orange-900 hover:bg-orange-50' 
                                            : 'text-green-600 hover:text-green-900 hover:bg-green-50'
                                        }`}
                                        title={user.is_active ? 'Deactivate User' : 'Activate User'}
                                    >
                                        {user.is_active ? <UserX className="h-5 w-5" /> : <UserCheck className="h-5 w-5" />}
                                    </button>
                                    {can.delete_user && (
                                        <button 
                                            onClick={handleDelete}
                                            className="text-red-600 hover:text-red-900 hover:bg-red-50 p-2 rounded-md transition-colors"
                                            title="Delete User"
                                        >
                                            <Trash2 className="h-5 w-5" />
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Contact Information */}
                        <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                            <div className="flex flex-col space-y-1.5 p-6 border-b border-gray-200">
                                <h3 className="text-lg font-semibold leading-none tracking-tight">
                                    Contact Information
                                </h3>
                            </div>
                            <div className="p-6 space-y-4">
                                <div className="flex items-center space-x-3">
                                    <Mail className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-600">Email Address</p>
                                        <p className="font-medium">{user.email}</p>
                                        {user.email_verified_at && (
                                            <p className="text-xs text-green-600 mt-1">
                                                ✓ Verified on {formatDate(user.email_verified_at)}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                
                                {user.phone && (
                                    <div className="flex items-center space-x-3">
                                        <Phone className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-600">Phone Number</p>
                                            <p className="font-medium">{user.phone}</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Account Activity */}
                        <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                            <div className="flex flex-col space-y-1.5 p-6 border-b border-gray-200">
                                <h3 className="text-lg font-semibold leading-none tracking-tight">
                                    Account Activity
                                </h3>
                            </div>
                            <div className="p-6 space-y-4">
                                <div className="flex items-center space-x-3">
                                    <Activity className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-600">Last Login</p>
                                        <p className="font-medium">{formatLastLogin(user.last_login_at || null)}</p>
                                    </div>
                                </div>

                                <div className="flex items-center space-x-3">
                                    <Calendar className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-600">Account Created</p>
                                        <p className="font-medium">{formatDate(user.created_at)}</p>
                                        {user.created_by && (
                                            <p className="text-xs text-gray-500">by {user.created_by.name}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center space-x-3">
                                    <Clock className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-600">Last Updated</p>
                                        <p className="font-medium">{formatDate(user.updated_at)}</p>
                                        {user.updated_by && (
                                            <p className="text-xs text-gray-500">by {user.updated_by.name}</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Roles and Permissions */}
                    <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div className="flex flex-col space-y-1.5 p-6 border-b border-gray-200">
                            <h3 className="text-lg font-semibold leading-none tracking-tight flex items-center">
                                <Shield className="h-5 w-5 mr-2" />
                                Roles and Permissions
                            </h3>
                            <p className="text-sm text-gray-600">
                                User roles and their associated permissions
                            </p>
                        </div>
                        <div className="p-6">
                            {user.roles.length > 0 ? (
                                <div className="space-y-6">
                                    {user.roles.map((role) => (
                                        <div key={role.id} className="border border-gray-200 rounded-lg p-4">
                                            <div className="flex items-center justify-between mb-4">
                                                <div className="flex items-center space-x-3">
                                                    <span className={`inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ${getRoleBadgeColor(role.name)}`}>
                                                        <Shield className="h-4 w-4 mr-1" />
                                                        {role.display_name}
                                                    </span>
                                                    <span className="text-sm text-gray-600">
                                                        Clearance Level {role.clearance_level}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            {role.permissions.length > 0 && (
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Permissions:</h4>
                                                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                                        {role.permissions.map((permission) => (
                                                            <span 
                                                                key={permission.id}
                                                                className="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                                            >
                                                                {permission.display_name}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <Shield className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No roles assigned</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        This user has not been assigned any roles yet.
                                    </p>
                                    {can.edit_user && (
                                        <div className="mt-4">
                                            <Link href={route('admin.users.edit', user.id)}>
                                                <button className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center transition-colors">
                                                    <Shield className="h-4 w-4 mr-2" />
                                                    Assign Roles
                                                </button>
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}