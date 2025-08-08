import React, { useCallback, useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Home, Phone, Calendar, Trash2, Eye, Loader2, Users } from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    permissions?: {
        can_edit_families?: boolean;
        can_delete_families?: boolean;
    };
}

interface Family {
    id: number;
    family_name: string;
    head_of_family: string;
    phone?: string;
    email?: string;
    address?: string;
    registration_date: string;
    status: string;
    notes?: string;
    members_count: number;
    created_at: string;
    updated_at: string;
}

interface EditFamilyProps {
    auth: {
        user: User;
    };
    family: Family;
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
}

interface FamilyFormData {
    family_name: string;
    head_of_family: string;
    phone: string;
    email: string;
    address: string;
    registration_date: string;
    status: string;
    notes: string;
    [key: string]: string;
}

type FamilyFormFieldKeys = 
    | 'family_name' 
    | 'head_of_family' 
    | 'phone' 
    | 'email' 
    | 'address' 
    | 'registration_date' 
    | 'status' 
    | 'notes';

const FormField = ({
    id,
    label,
    type = 'text',
    required = false,
    placeholder = '',
    className = '',
    rows,
    value,
    error,
    onChange,
    canEdit = true,
}: {
    id: string;
    label: string;
    type?: string;
    required?: boolean;
    placeholder?: string;
    className?: string;
    rows?: number;
    value: string;
    error?: string;
    onChange: (value: string) => void;
    canEdit?: boolean;
}) => {
    const isTextarea = type === 'textarea';
    return (
        <div className={className}>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            {isTextarea ? (
                <textarea
                    id={id}
                    value={value}
                    onChange={e => onChange(e.target.value)}
                    placeholder={placeholder}
                    rows={rows || 3}
                    disabled={!canEdit}
                    aria-invalid={!!error}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                        error ? 'border-red-500' : 'border-gray-300'
                    } ${!canEdit ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                    required={required}
                />
            ) : (
                <input
                    type={type}
                    id={id}
                    value={value}
                    onChange={e => onChange(e.target.value)}
                    placeholder={placeholder}
                    disabled={!canEdit}
                    aria-invalid={!!error}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                        error ? 'border-red-500' : 'border-gray-300'
                    } ${!canEdit ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                    required={required}
                    {...(type === 'date' && { max: new Date().toISOString().split('T')[0] })}
                />
            )}
            {error && (
                <p className="mt-1 text-sm text-red-600" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
};

const SelectField = ({
    id,
    label,
    required = false,
    placeholder = 'Select an option',
    className = '',
    options,
    value,
    error,
    onChange,
    canEdit = true,
}: {
    id: string;
    label: string;
    required?: boolean;
    placeholder?: string;
    className?: string;
    options: { value: string; label: string }[];
    value: string;
    error?: string;
    onChange: (value: string) => void;
    canEdit?: boolean;
}) => (
    <div className={className}>
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
            {label} {required && <span className="text-red-500">*</span>}
        </label>
        <select
            id={id}
            value={value}
            onChange={e => onChange(e.target.value)}
            disabled={!canEdit}
            aria-invalid={!!error}
            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                error ? 'border-red-500' : 'border-gray-300'
            } ${!canEdit ? 'bg-gray-100 cursor-not-allowed' : ''}`}
            required={required}
        >
            <option value="">{placeholder}</option>
            {options.map(option => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
        {error && (
            <p className="mt-1 text-sm text-red-600" role="alert">
                {error}
            </p>
        )}
    </div>
);

export default function EditFamily({ auth, family }: EditFamilyProps) {
    const [showDeleteConfirm, setShowDeleteConfirm] = useState<boolean>(false);
    const [isDeleting, setIsDeleting] = useState<boolean>(false);

    const { data, setData, put, processing, errors, clearErrors, delete: destroy } = useForm({
        family_name: family?.family_name || '',
        head_of_family: family?.head_of_family || '',
        phone: family?.phone || '',
        email: family?.email || '',
        address: family?.address || '',
        registration_date: family?.registration_date || '',
        status: family?.status || 'active',
        notes: family?.notes || '',
    });

    const canEdit = auth?.user?.permissions?.can_edit_families ?? true;
    const canDelete = auth?.user?.permissions?.can_delete_families ?? true;

    const handleFieldChange = useCallback((field: FamilyFormFieldKeys, value: string) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, errors, clearErrors]);

    const validateForm = useCallback((): boolean => {
        const requiredFields: FamilyFormFieldKeys[] = ['family_name', 'head_of_family', 'registration_date', 'status'];

        for (const field of requiredFields) {
            if (!data[field]?.trim()) {
                document.getElementById(field)?.focus();
                return false;
            }
        }

        // Email validation
        if (data.email?.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(data.email)) {
                document.getElementById('email')?.focus();
                return false;
            }
        }

        // Date validation
        if (data.registration_date) {
            const regDate = new Date(data.registration_date);
            const today = new Date();
            if (regDate > today) {
                document.getElementById('registration_date')?.focus();
                return false;
            }
        }

        return true;
    }, [data]);

    const handleSubmit = useCallback((e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        
        if (!validateForm()) return;

        clearErrors();
        
        put(route('families.update', family.id), {
            onError: (validationErrors: Record<string, string>) => {
                const firstErrorField = Object.keys(validationErrors)[0] as FamilyFormFieldKeys;
                if (firstErrorField) {
                    document.getElementById(firstErrorField)?.focus();
                }
            }
        });
    }, [validateForm, clearErrors, put, family.id]);

    const handleDelete = useCallback(() => {
        if (!family?.id) return;
        
        setIsDeleting(true);
        destroy(route('families.destroy', family.id), {
            onError: () => {
                setIsDeleting(false);
                alert('Failed to delete family. Please try again.');
            },
            onFinish: () => {
                setIsDeleting(false);
                setShowDeleteConfirm(false);
            }
        });
    }, [destroy, family?.id]);

    if (!family) {
        return (
            <AuthenticatedLayout>
                <Head title="Family Not Found" />
                <div className="py-12">
                    <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
                            <p className="text-red-800">Family not found or access denied.</p>
                            <Link
                                href={route('families.index')}
                                className="mt-4 inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Back to Families
                            </Link>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('families.index')}
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {canEdit ? 'Edit' : 'View'} Family: {family.family_name}
                            </h2>
                            <p className="text-sm text-gray-600">
                                {family.members_count} member{family.members_count !== 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>
                    
                    <div className="flex space-x-3">
                        <Link
                            href={route('families.show', family.id)}
                            className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                        >
                            <Eye className="w-4 h-4" />
                            <span>View Details</span>
                        </Link>
                        
                        {canDelete && (
                            <button
                                onClick={() => setShowDeleteConfirm(true)}
                                className="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                                disabled={isDeleting}
                            >
                                {isDeleting ? (
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                ) : (
                                    <Trash2 className="w-4 h-4" />
                                )}
                                <span>{isDeleting ? 'Deleting...' : 'Delete'}</span>
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Edit ${family.family_name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {!canEdit && (
                        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <p className="text-yellow-800 text-sm">
                                You have read-only access. Contact an administrator to make changes.
                            </p>
                        </div>
                    )}

                    <div className="bg-white rounded-lg shadow-md">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center space-x-3">
                                <div className="bg-blue-100 rounded-full p-2">
                                    <Home className="w-6 h-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Family Information</h3>
                                    <p className="text-sm text-gray-600">
                                        Update the family information
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} noValidate>
                            <div className="p-6 space-y-6">
                                {/* Basic Information */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <FormField
                                        id="family_name"
                                        label="Family Name"
                                        required
                                        placeholder="e.g., The Smith Family"
                                        value={data.family_name}
                                        error={errors.family_name}
                                        onChange={val => handleFieldChange('family_name', val)}
                                        canEdit={canEdit}
                                    />
                                    <FormField
                                        id="head_of_family"
                                        label="Head of Family"
                                        required
                                        placeholder="Full name of family head"
                                        value={data.head_of_family}
                                        error={errors.head_of_family}
                                        onChange={val => handleFieldChange('head_of_family', val)}
                                        canEdit={canEdit}
                                    />
                                </div>

                                {/* Contact Information */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <Phone className="w-5 h-5 mr-2 text-blue-600" />
                                        Contact Information
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormField
                                            id="phone"
                                            label="Phone Number"
                                            type="tel"
                                            placeholder="+254 700 000 000"
                                            value={data.phone}
                                            error={errors.phone}
                                            onChange={val => handleFieldChange('phone', val)}
                                            canEdit={canEdit}
                                        />
                                        <FormField
                                            id="email"
                                            label="Email Address"
                                            type="email"
                                            placeholder="family@email.com"
                                            value={data.email}
                                            error={errors.email}
                                            onChange={val => handleFieldChange('email', val)}
                                            canEdit={canEdit}
                                        />
                                    </div>
                                    <div className="mt-6">
                                        <FormField
                                            id="address"
                                            label="Address"
                                            type="textarea"
                                            placeholder="Full address including city and postal code"
                                            rows={3}
                                            value={data.address}
                                            error={errors.address}
                                            onChange={val => handleFieldChange('address', val)}
                                            canEdit={canEdit}
                                        />
                                    </div>
                                </div>

                                {/* Registration Details */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <Calendar className="w-5 h-5 mr-2 text-blue-600" />
                                        Registration Details
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormField
                                            id="registration_date"
                                            label="Registration Date"
                                            type="date"
                                            required
                                            value={data.registration_date}
                                            error={errors.registration_date}
                                            onChange={val => handleFieldChange('registration_date', val)}
                                            canEdit={canEdit}
                                        />
                                        <SelectField
                                            id="status"
                                            label="Family Status"
                                            required
                                            options={[
                                                { value: 'active', label: 'Active' },
                                                { value: 'inactive', label: 'Inactive' }
                                            ]}
                                            value={data.status}
                                            error={errors.status}
                                            onChange={val => handleFieldChange('status', val)}
                                            canEdit={canEdit}
                                        />
                                    </div>
                                </div>

                                {/* Additional Notes */}
                                <div className="border-t border-gray-200 pt-6">
                                    <FormField
                                        id="notes"
                                        label="Additional Notes"
                                        type="textarea"
                                        placeholder="Any additional information about this family..."
                                        rows={4}
                                        value={data.notes}
                                        error={errors.notes}
                                        onChange={val => handleFieldChange('notes', val)}
                                        canEdit={canEdit}
                                    />
                                </div>

                                {/* Family History */}
                                <div className="bg-gray-50 p-4 rounded-lg border-t border-gray-200">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Family History</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span className="font-medium">Created:</span>{' '}
                                            {new Date(family.created_at).toLocaleDateString()}
                                        </div>
                                        <div>
                                            <span className="font-medium">Last Updated:</span>{' '}
                                            {new Date(family.updated_at).toLocaleDateString()}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-end items-center px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg space-x-3">
                                <Link
                                    href={route('families.index')}
                                    className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    {canEdit ? 'Cancel' : 'Back to Families'}
                                </Link>
                                {canEdit && (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <Save className="w-4 h-4" />
                                        <span>{processing ? 'Updating Family...' : 'Update Family'}</span>
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>

                    {/* Quick Actions for Family Members */}
                    <div className="mt-6">
                        <div className="bg-white rounded-lg shadow-md p-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <div className="bg-green-100 rounded-full p-2">
                                        <Users className="w-5 h-5 text-green-600" />
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">Family Members</h3>
                                        <p className="text-sm text-gray-600">
                                            {family.members_count} member{family.members_count !== 1 ? 's' : ''} in this family
                                        </p>
                                    </div>
                                </div>
                                <div className="flex space-x-3">
                                    <Link
                                        href={route('members.index', { family_id: family.id })}
                                        className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                    >
                                        View Members
                                    </Link>
                                    <Link
                                        href={route('members.create', { family_id: family.id })}
                                        className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                    >
                                        Add Member
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            {showDeleteConfirm && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <div className="flex items-center mb-4">
                            <div className="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                <Trash2 className="w-6 h-6 text-red-600" />
                            </div>
                            <div className="ml-3">
                                <h3 className="text-lg font-medium text-gray-900">Delete Family</h3>
                            </div>
                        </div>
                        <div className="mb-4">
                            <p className="text-sm text-gray-500">
                                Are you sure you want to delete <strong>{family.family_name}</strong>? 
                                This will also remove all {family.members_count} family member{family.members_count !== 1 ? 's' : ''}. 
                                This action cannot be undone.
                            </p>
                        </div>
                        <div className="flex space-x-3">
                            <button
                                onClick={() => setShowDeleteConfirm(false)}
                                disabled={isDeleting}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleDelete}
                                disabled={isDeleting}
                                className="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors disabled:opacity-50 flex items-center justify-center space-x-2"
                            >
                                {isDeleting ? (
                                    <>
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                        <span>Deleting...</span>
                                    </>
                                ) : (
                                    <span>Delete Family</span>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}