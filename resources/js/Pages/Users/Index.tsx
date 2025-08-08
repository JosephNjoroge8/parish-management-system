import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Edit, Trash2, Eye, UserCheck, UserX, RefreshCcw } from 'lucide-react';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role?: Role;
    active?: boolean;
}

interface UsersIndexProps {
    users: {
        data: User[];
        // ...other pagination meta fields if needed
    };
    canCreate: boolean;
}

export default function UsersIndex({ users, canCreate }: UsersIndexProps) {
    const { flash } = usePage().props as any;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between flex-wrap gap-2">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        User Management
                    </h2>
                    {canCreate && (
                        <Link
                            href={route('admin.users.create')}
                            className="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Add User
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Users" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 p-3 bg-green-100 text-green-800 rounded">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-4 p-3 bg-red-100 text-red-800 rounded">
                            {flash.error}
                        </div>
                    )}
                    <div className="bg-white shadow rounded-lg overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-100">
                                {users.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-6 text-center text-gray-400">
                                            No users found.
                                        </td>
                                    </tr>
                                )}
                                {users.data.map(user => (
                                    <tr key={user.id}>
                                        <td className="px-4 py-2 whitespace-nowrap">{user.name}</td>
                                        <td className="px-4 py-2 whitespace-nowrap">{user.email}</td>
                                        <td className="px-4 py-2 whitespace-nowrap">{user.role?.name ?? '-'}</td>
                                        <td className="px-4 py-2 whitespace-nowrap">
                                            {user.active ? (
                                                <span className="inline-flex items-center text-green-600">
                                                    <UserCheck className="w-4 h-4 mr-1" /> Active
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center text-red-600">
                                                    <UserX className="w-4 h-4 mr-1" /> Inactive
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2 whitespace-nowrap text-right space-x-2">
                                            <Link
                                                href={route('admin.users.show', user.id)}
                                                className="inline-flex items-center px-2 py-1 text-xs text-blue-600 hover:underline"
                                                title="View"
                                            >
                                                <Eye className="w-4 h-4" />
                                            </Link>
                                            <Link
                                                href={route('admin.users.edit', user.id)}
                                                className="inline-flex items-center px-2 py-1 text-xs text-yellow-600 hover:underline"
                                                title="Edit"
                                            >
                                                <Edit className="w-4 h-4" />
                                            </Link>
                                            <Link
                                                as="button"
                                                method="delete"
                                                href={route('admin.users.destroy', user.id)}
                                                className="inline-flex items-center px-2 py-1 text-xs text-red-600 hover:underline"
                                                title="Delete"
                                                onClick={e => {
                                                    if (!confirm('Are you sure you want to delete this user?')) {
                                                        e.preventDefault();
                                                    }
                                                }}
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Link>
                                            <Link
                                                as="button"
                                                method="post"
                                                href={route('admin.users.toggle-status', user.id)}
                                                className="inline-flex items-center px-2 py-1 text-xs text-gray-600 hover:text-purple-600"
                                                title="Toggle Status"
                                            >
                                                <RefreshCcw className="w-4 h-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {/* Pagination (if needed) */}
                        {/* 
                        <div className="mt-4">
                            <Pagination meta={users.meta} links={users.links} />
                        </div>
                        */}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}