import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    UserPlus, 
    ArrowLeft, 
    Save, 
    Eye, 
    EyeOff, 
    Mail, 
    User, 
    Phone, 
    Shield,
    AlertCircle
} from 'lucide-react';

interface Role {
    id: number;
    name: string;
    display_name: string;
    clearance_level: number;
}

interface Props {
    roles: Role[];
    can: {
        assign_roles: boolean;
    };
}

export default function Create({ roles, can }: Props) {
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        roles: [] as number[],
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.users.store'), {
            onSuccess: () => {
                router.visit(route('admin.users.index'));
            }
        });
    };

    const handleRoleToggle = (roleId: number) => {
        const newRoles = data.roles.includes(roleId)
            ? data.roles.filter(id => id !== roleId)
            : [...data.roles, roleId];
        setData('roles', newRoles);
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
                        <UserPlus className="h-6 w-6" />
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Create New User
                        </h2>
                    </div>
                    <Link href={route('admin.users.index')}>
                        <button className="border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md inline-flex items-center transition-colors">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Users
                        </button>
                    </Link>
                </div>
            }
        >
            <Head title="Create User" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div className="flex flex-col space-y-1.5 p-6 border-b border-gray-200">
                            <h3 className="text-lg font-semibold leading-none tracking-tight">
                                User Information
                            </h3>
                            <p className="text-sm text-gray-600">
                                Create a new user account and assign appropriate roles.
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Basic Information */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <label htmlFor="name" className="text-sm font-medium text-gray-700 flex items-center">
                                        <User className="h-4 w-4 mr-1" />
                                        Full Name *
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className={`flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all ${
                                            errors.name ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'
                                        }`}
                                        placeholder="Enter full name"
                                        required
                                    />
                                    {errors.name && (
                                        <div className="flex items-center text-red-600 text-sm">
                                            <AlertCircle className="h-4 w-4 mr-1" />
                                            {errors.name}
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="email" className="text-sm font-medium text-gray-700 flex items-center">
                                        <Mail className="h-4 w-4 mr-1" />
                                        Email Address *
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className={`flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all ${
                                            errors.email ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'
                                        }`}
                                        placeholder="Enter email address"
                                        required
                                    />
                                    {errors.email && (
                                        <div className="flex items-center text-red-600 text-sm">
                                            <AlertCircle className="h-4 w-4 mr-1" />
                                            {errors.email}
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="phone" className="text-sm font-medium text-gray-700 flex items-center">
                                        <Phone className="h-4 w-4 mr-1" />
                                        Phone Number
                                    </label>
                                    <input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className={`flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all ${
                                            errors.phone ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'
                                        }`}
                                        placeholder="Enter phone number"
                                    />
                                    {errors.phone && (
                                        <div className="flex items-center text-red-600 text-sm">
                                            <AlertCircle className="h-4 w-4 mr-1" />
                                            {errors.phone}
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-gray-700">
                                        Account Status
                                    </label>
                                    <div className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            id="is_active"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <label htmlFor="is_active" className="text-sm text-gray-700">
                                            Active user account
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {/* Password Section */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <label htmlFor="password" className="text-sm font-medium text-gray-700">
                                        Password *
                                    </label>
                                    <div className="relative">
                                        <input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            className={`flex h-10 w-full rounded-md border px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all ${
                                                errors.password ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'
                                            }`}
                                            placeholder="Enter password"
                                            required
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <div className="flex items-center text-red-600 text-sm">
                                            <AlertCircle className="h-4 w-4 mr-1" />
                                            {errors.password}
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="password_confirmation" className="text-sm font-medium text-gray-700">
                                        Confirm Password *
                                    </label>
                                    <div className="relative">
                                        <input
                                            id="password_confirmation"
                                            type={showPasswordConfirm ? 'text' : 'password'}
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                                            placeholder="Confirm password"
                                            required
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPasswordConfirm(!showPasswordConfirm)}
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            {showPasswordConfirm ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Role Assignment */}
                            {can.assign_roles && (
                                <div className="space-y-4">
                                    <div className="border-t pt-6">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium text-gray-700 flex items-center">
                                                <Shield className="h-4 w-4 mr-1" />
                                                Assign Roles
                                            </label>
                                            <p className="text-sm text-gray-600">
                                                Select the roles to assign to this user. Higher clearance levels can manage lower levels.
                                            </p>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                                            {roles.map((role) => (
                                                <div key={role.id} className="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                                    <input
                                                        type="checkbox"
                                                        id={`role-${role.id}`}
                                                        checked={data.roles.includes(role.id)}
                                                        onChange={() => handleRoleToggle(role.id)}
                                                        className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    />
                                                    <div className="flex-1">
                                                        <label htmlFor={`role-${role.id}`} className="cursor-pointer">
                                                            <div className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${getRoleBadgeColor(role.name)}`}>
                                                                <Shield className="h-3 w-3 mr-1" />
                                                                {role.display_name}
                                                            </div>
                                                            <div className="text-xs text-gray-500 mt-1">
                                                                Level {role.clearance_level}
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>

                                        {errors.roles && (
                                            <div className="flex items-center text-red-600 text-sm mt-2">
                                                <AlertCircle className="h-4 w-4 mr-1" />
                                                {errors.roles}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Submit Buttons */}
                            <div className="flex items-center justify-end space-x-4 pt-6 border-t">
                                <Link href={route('admin.users.index')}>
                                    <button
                                        type="button"
                                        className="border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 px-6 py-2 rounded-md transition-colors"
                                    >
                                        Cancel
                                    </button>
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md inline-flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {processing ? (
                                        <>
                                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                            Creating...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="h-4 w-4 mr-2" />
                                            Create User
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}