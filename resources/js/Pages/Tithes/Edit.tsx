import React, { useCallback, useState, useEffect } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, DollarSign, Calendar, CreditCard, User, FileText, Trash2, Loader2 } from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    permissions?: {
        can_edit_tithing?: boolean;
        can_delete_tithing?: boolean;
    };
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    member_number: string;
}

interface TithingRecord {
    id: number;
    member_id: number;
    member: Member;
    amount: number;
    payment_date: string;
    payment_method: 'cash' | 'cheque' | 'bank_transfer' | 'mobile_money' | 'card';
    reference_number?: string;
    offering_type: 'tithe' | 'offering' | 'thanksgiving' | 'project' | 'special';
    received_by: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface EditTithingProps {
    auth: {
        user: User;
    };
    record: TithingRecord;
    members: Member[];
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
}

interface TithingFormData {
    member_id: string;
    amount: string;
    payment_date: string;
    payment_method: string;
    reference_number: string;
    offering_type: string;
    received_by: string;
    notes: string;
    [key: string]: string;
}

type TithingFormFieldKeys = 
    | 'member_id'
    | 'amount'
    | 'payment_date'
    | 'payment_method'
    | 'reference_number'
    | 'offering_type'
    | 'received_by'
    | 'notes';

const paymentMethodOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'cheque', label: 'Cheque' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'mobile_money', label: 'Mobile Money' },
    { value: 'card', label: 'Card' },
];

const offeringTypeOptions = [
    { value: 'tithe', label: 'Tithe' },
    { value: 'offering', label: 'Offering' },
    { value: 'thanksgiving', label: 'Thanksgiving' },
    { value: 'project', label: 'Project Contribution' },
    { value: 'special', label: 'Special Offering' },
];

export default function EditTithing({ auth, record, members }: EditTithingProps) {
    const [showDeleteConfirm, setShowDeleteConfirm] = useState<boolean>(false);
    const [isDeleting, setIsDeleting] = useState<boolean>(false);
    const [showReferenceField, setShowReferenceField] = useState(false);

    const { data, setData, put, processing, errors, clearErrors, delete: destroy } = useForm({
        member_id: record?.member_id?.toString() || '',
        amount: record?.amount?.toString() || '',
        payment_date: record?.payment_date || '',
        payment_method: record?.payment_method || '',
        reference_number: record?.reference_number || '',
        offering_type: record?.offering_type || '',
        received_by: record?.received_by || '',
        notes: record?.notes || '',
    });

    const canEdit = auth?.user?.permissions?.can_edit_tithing ?? true;
    const canDelete = auth?.user?.permissions?.can_delete_tithing ?? true;

    // Show reference number field for non-cash payments
    useEffect(() => {
        setShowReferenceField(data.payment_method !== 'cash' && data.payment_method !== null && data.payment_method !== undefined);
        if (data.payment_method === 'cash') {
            setData('reference_number', '');
        }
    }, [data.payment_method]);

    const handleFieldChange = useCallback((field: TithingFormFieldKeys, value: string) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, errors, clearErrors]);

    const validateForm = useCallback((): boolean => {
        const requiredFields: TithingFormFieldKeys[] = [
            'member_id', 'amount', 'payment_date', 'payment_method', 'offering_type', 'received_by'
        ];

        for (const field of requiredFields) {
            if (!data[field]?.trim()) {
                document.getElementById(field)?.focus();
                return false;
            }
        }

        // Amount validation
        const amount = parseFloat(data.amount);
        if (isNaN(amount) || amount <= 0) {
            document.getElementById('amount')?.focus();
            return false;
        }

        // Date validation
        if (data.payment_date) {
            const paymentDate = new Date(data.payment_date);
            const today = new Date();
            if (paymentDate > today) {
                document.getElementById('payment_date')?.focus();
                return false;
            }
        }

        // Reference number validation for non-cash payments
        if (showReferenceField && !data.reference_number?.trim()) {
            document.getElementById('reference_number')?.focus();
            return false;
        }

        return true;
    }, [data, showReferenceField]);

    const handleSubmit = useCallback((e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        
        if (!validateForm()) return;

        clearErrors();
        
        const formData = {
            ...data,
            amount: parseFloat(data.amount),
        };
        
        put(route('tithing.update', record.id), {
            onError: (validationErrors: Record<string, string>) => {
                const firstErrorField = Object.keys(validationErrors)[0] as TithingFormFieldKeys;
                if (firstErrorField) {
                    document.getElementById(firstErrorField)?.focus();
                }
            }
        });
    }, [validateForm, clearErrors, put, record.id, data]);

    const handleDelete = useCallback(() => {
        if (!record?.id) return;
        
        setIsDeleting(true);
        destroy(route('tithing.destroy', record.id), {
            onError: () => {
                setIsDeleting(false);
                alert('Failed to delete tithing record. Please try again.');
            },
            onFinish: () => {
                setIsDeleting(false);
                setShowDeleteConfirm(false);
            }
        });
    }, [destroy, record?.id]);

    const formatCurrency = (amount: string | number) => {
        const num = typeof amount === 'string' ? parseFloat(amount) : amount;
        if (isNaN(num)) return '';
        return new Intl.NumberFormat('en-KE', {
            style: 'currency',
            currency: 'KES',
        }).format(num);
    };

    const FormField = ({ 
        id, 
        label, 
        type = 'text', 
        required = false, 
        placeholder = '',
        className = '',
        rows,
        step
    }: {
        id: TithingFormFieldKeys;
        label: string;
        type?: string;
        required?: boolean;
        placeholder?: string;
        className?: string;
        rows?: number;
        step?: string;
    }) => {
        const isTextarea = type === 'textarea';
        const Component = isTextarea ? 'textarea' : 'input';
        
        return (
            <div className={className}>
                <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                    {label} {required && <span className="text-red-500">*</span>}
                </label>
                <Component
                    {...(!isTextarea && { type })}
                    {...(isTextarea && { rows: rows || 3 })}
                    {...(step && { step })}
                    id={id}
                    value={data[id]}
                    onChange={(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => 
                        handleFieldChange(id, e.target.value)
                    }
                    placeholder={placeholder}
                    disabled={!canEdit}
                    aria-invalid={errors[id] ? 'true' : 'false'}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors ${
                        errors[id] ? 'border-red-500' : 'border-gray-300'
                    } ${!canEdit ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                    required={required}
                    {...(type === 'date' && { max: new Date().toISOString().split('T')[0] })}
                />
                {type === 'number' && data[id] && (
                    <p className="mt-1 text-sm text-green-600">
                        {formatCurrency(data[id])}
                    </p>
                )}
                {errors[id] && (
                    <p className="mt-1 text-sm text-red-600" role="alert">
                        {errors[id]}
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
        options
    }: {
        id: TithingFormFieldKeys;
        label: string;
        required?: boolean;
        placeholder?: string;
        className?: string;
        options: { value: string; label: string }[];
    }) => (
        <div className={className}>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            <select
                id={id}
                value={data[id]}
                onChange={(e) => handleFieldChange(id, e.target.value)}
                disabled={!canEdit}
                aria-invalid={errors[id] ? 'true' : 'false'}
                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors ${
                    errors[id] ? 'border-red-500' : 'border-gray-300'
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
            {errors[id] && (
                <p className="mt-1 text-sm text-red-600" role="alert">
                    {errors[id]}
                </p>
            )}
        </div>
    );

    if (!record) {
        return (
            <AuthenticatedLayout>
                <Head title="Record Not Found" />
                <div className="py-12">
                    <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
                            <p className="text-red-800">Tithing record not found or access denied.</p>
                            <Link
                                href={route('tithing.index')}
                                className="mt-4 inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Back to Tithing Records
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
                            href={route('tithing.index')}
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {canEdit ? 'Edit' : 'View'} Tithing Record
                            </h2>
                            <p className="text-sm text-gray-600">
                                {record.member.first_name} {record.member.last_name} - {formatCurrency(record.amount)}
                            </p>
                        </div>
                    </div>
                    
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
                            <span>{isDeleting ? 'Deleting...' : 'Delete Record'}</span>
                        </button>
                    )}
                </div>
            }
        >
            <Head title={`Edit Tithing Record - ${formatCurrency(record.amount)}`} />

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
                                <div className="bg-green-100 rounded-full p-2">
                                    <DollarSign className="w-6 h-6 text-green-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Financial Contribution</h3>
                                    <p className="text-sm text-gray-600">
                                        Update the contribution record details
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} noValidate>
                            <div className="p-6 space-y-6">
                                {/* Member and Type */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <SelectField
                                        id="member_id"
                                        label="Member"
                                        required
                                        placeholder="Select Member"
                                        options={members.map(member => ({
                                            value: member.id.toString(),
                                            label: `${member.first_name} ${member.last_name} (${member.member_number})`
                                        }))}
                                    />
                                    <SelectField
                                        id="offering_type"
                                        label="Contribution Type"
                                        required
                                        placeholder="Select Type"
                                        options={offeringTypeOptions}
                                    />
                                </div>

                                {/* Amount and Date */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <DollarSign className="w-5 h-5 mr-2 text-green-600" />
                                        Amount & Date
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormField
                                            id="amount"
                                            label="Amount"
                                            type="number"
                                            step="0.01"
                                            required
                                            placeholder="0.00"
                                        />
                                        <FormField
                                            id="payment_date"
                                            label="Payment Date"
                                            type="date"
                                            required
                                        />
                                    </div>
                                </div>

                                {/* Payment Method and Reference */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <CreditCard className="w-5 h-5 mr-2 text-green-600" />
                                        Payment Details
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <SelectField
                                            id="payment_method"
                                            label="Payment Method"
                                            required
                                            placeholder="Select Payment Method"
                                            options={paymentMethodOptions}
                                        />
                                        {showReferenceField && (
                                            <FormField
                                                id="reference_number"
                                                label="Reference Number"
                                                required={showReferenceField}
                                                placeholder="Transaction/Cheque number"
                                            />
                                        )}
                                    </div>
                                </div>

                                {/* Received By */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <User className="w-5 h-5 mr-2 text-green-600" />
                                        Recording Information
                                    </h4>
                                    <FormField
                                        id="received_by"
                                        label="Received By"
                                        required
                                        placeholder="Name of person receiving the contribution"
                                    />
                                </div>

                                {/* Additional Notes */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <FileText className="w-5 h-5 mr-2 text-green-600" />
                                        Additional Information
                                    </h4>
                                    <FormField
                                        id="notes"
                                        label="Notes"
                                        type="textarea"
                                        placeholder="Any additional information about this contribution..."
                                        rows={4}
                                    />
                                </div>

                                {/* Record History */}
                                <div className="bg-gray-50 p-4 rounded-lg border-t border-gray-200">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Record History</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span className="font-medium">Created:</span>{' '}
                                            {new Date(record.created_at).toLocaleDateString()}
                                        </div>
                                        <div>
                                            <span className="font-medium">Last Updated:</span>{' '}
                                            {new Date(record.updated_at).toLocaleDateString()}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-end items-center px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg space-x-3">
                                <Link
                                    href={route('tithing.index')}
                                    className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    {canEdit ? 'Cancel' : 'Back to Records'}
                                </Link>
                                {canEdit && (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50"
                                    >
                                        <Save className="w-4 h-4" />
                                        <span>{processing ? 'Updating...' : 'Update Record'}</span>
                                    </button>
                                )}
                            </div>
                        </form>
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
                                <h3 className="text-lg font-medium text-gray-900">Delete Tithing Record</h3>
                            </div>
                        </div>
                        <div className="mb-4">
                            <p className="text-sm text-gray-500">
                                Are you sure you want to delete this {offeringTypeOptions.find(o => o.value === record.offering_type)?.label.toLowerCase()} record of{' '}
                                <strong>{formatCurrency(record.amount)}</strong> from{' '}
                                <strong>{record.member.first_name} {record.member.last_name}</strong>? 
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
                                    <span>Delete Record</span>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}