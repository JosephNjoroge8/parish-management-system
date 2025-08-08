import React, { useCallback, useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Church, Calendar, FileText, Trash2, Loader2 } from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    permissions?: {
        can_edit_sacraments?: boolean;
        can_delete_sacraments?: boolean;
    };
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    member_number: string;
    id_number?: string;
}

interface Sacrament {
    id: number;
    member_id: number;
    member: Member; // Required since controller now sends it
    sacrament_type: string;
    date_administered: string;
    administered_by: string;
    location: string;
    certificate_number?: string;
    witness_1?: string;
    witness_2?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface EditSacramentProps {
    auth: {
        user: User;
    };
    sacrament: Sacrament;
    members: Member[];
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
}

interface SacramentFormData {
    member_id: string;
    sacrament_type: string;
    date_administered: string;
    administered_by: string;
    location: string;
    certificate_number: string;
    witness_1: string;
    witness_2: string;
    notes: string;
    [key: string]: string;
}

type SacramentFormFieldKeys = 
    | 'member_id'
    | 'sacrament_type'
    | 'date_administered'
    | 'administered_by'
    | 'location'
    | 'certificate_number'
    | 'witness_1'
    | 'witness_2'
    | 'notes';

const sacramentOptions = [
    { value: 'baptism', label: 'Baptism' },
    { value: 'eucharist', label: 'First Holy Communion' },
    { value: 'confirmation', label: 'Confirmation' },
    { value: 'reconciliation', label: 'Reconciliation' },
    { value: 'anointing', label: 'Anointing of the Sick' },
    { value: 'marriage', label: 'Marriage' },
    { value: 'holy_orders', label: 'Holy Orders' },
];
const FormField = ({
        id,
        label,
        type = 'text',
        required = false,
        placeholder = '',
        className = '',
        rows,
        data,
        errors,
        canEdit,
        handleFieldChange,
    }: {
        id: SacramentFormFieldKeys;
        label: string;
        type?: string;
        required?: boolean;
        placeholder?: string;
        className?: string;
        rows?: number;
        data: any;
        errors: any;
        canEdit: boolean;
        handleFieldChange: (id: SacramentFormFieldKeys, value: string) => void;
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
                        value={data[id] || ''}
                        onChange={e => handleFieldChange(id, e.target.value)}
                        placeholder={placeholder}
                        rows={rows || 3}
                        disabled={!canEdit}
                        aria-invalid={errors[id] ? 'true' : 'false'}
                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                            errors[id] ? 'border-red-500' : 'border-gray-300'
                        } ${!canEdit ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                        required={required}
                    />
                ) : (
                    <input
                        type={type}
                        id={id}
                        value={data[id] || ''}
                        onChange={e => handleFieldChange(id, e.target.value)}
                        placeholder={placeholder}
                        disabled={!canEdit}
                        aria-invalid={errors[id] ? 'true' : 'false'}
                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                            errors[id] ? 'border-red-500' : 'border-gray-300'
                        } ${!canEdit ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                        required={required}
                        {...(type === 'date' && { max: new Date().toISOString().split('T')[0] })}
                    />
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
        options,
        data,
        errors,
        canEdit,
        handleFieldChange,
    }: {
        id: SacramentFormFieldKeys;
        label: string;
        required?: boolean;
        placeholder?: string;
        className?: string;
        options: { value: string; label: string }[];
        data: any;
        errors: any;
        canEdit: boolean;
        handleFieldChange: (id: SacramentFormFieldKeys, value: string) => void;
    }) => (
        <div className={className}>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            <select
                id={id}
                value={data[id] || ''}
                onChange={e => handleFieldChange(id, e.target.value)}
                disabled={!canEdit}
                aria-invalid={errors[id] ? 'true' : 'false'}
                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
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


export default function EditSacrament({ auth, sacrament, members }: EditSacramentProps) {
    const [showDeleteConfirm, setShowDeleteConfirm] = useState<boolean>(false);
    const [isDeleting, setIsDeleting] = useState<boolean>(false);

    const { data, setData, put, processing, errors, clearErrors, delete: destroy } = useForm({
        member_id: sacrament?.member_id?.toString() || '',
        sacrament_type: sacrament?.sacrament_type || '',
        date_administered: sacrament?.date_administered || '',
        administered_by: sacrament?.administered_by || '',
        location: sacrament?.location || '',
        certificate_number: sacrament?.certificate_number || '',
        witness_1: sacrament?.witness_1 || '',
        witness_2: sacrament?.witness_2 || '',
        notes: sacrament?.notes || '',
    });

    const canEdit = auth?.user?.permissions?.can_edit_sacraments ?? true;
    const canDelete = auth?.user?.permissions?.can_delete_sacraments ?? true;

   const handleFieldChange = useCallback((field: SacramentFormFieldKeys, value: string) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, clearErrors]);

    const validateForm = useCallback((): boolean => {
        const requiredFields: SacramentFormFieldKeys[] = [
            'member_id', 'sacrament_type', 'date_administered', 'administered_by', 'location'
        ];

        for (const field of requiredFields) {
            if (!data[field]?.trim()) {
                document.getElementById(field)?.focus();
                return false;
            }
        }

        // Date validation
        if (data.date_administered) {
            const adminDate = new Date(data.date_administered);
            const today = new Date();
            if (adminDate > today) {
                document.getElementById('date_administered')?.focus();
                return false;
            }
        }

        return true;
    }, [data]);

    const handleSubmit = useCallback((e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        
        if (!validateForm()) return;

        clearErrors();
        
        put(route('sacraments.update', sacrament.id), {
            onError: (validationErrors: Record<string, string>) => {
                const firstErrorField = Object.keys(validationErrors)[0] as SacramentFormFieldKeys;
                if (firstErrorField) {
                    document.getElementById(firstErrorField)?.focus();
                }
            }
        });
    }, [validateForm, clearErrors, put, sacrament.id]);

    const handleDelete = useCallback(() => {
        if (!sacrament?.id) return;
        
        setIsDeleting(true);
        destroy(route('sacraments.destroy', sacrament.id), {
            onError: () => {
                setIsDeleting(false);
                alert('Failed to delete sacrament record. Please try again.');
            },
            onFinish: () => {
                setIsDeleting(false);
                setShowDeleteConfirm(false);
            }
        });
    }, [destroy, sacrament?.id]);    // Optimized FormField component to prevent cursor jumping
    
    if (!sacrament) {
        return (
            <AuthenticatedLayout>
                <Head title="Sacrament Not Found" />
                <div className="py-12">
                    <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
                            <p className="text-red-800">Sacrament record not found or access denied.</p>
                            <Link
                                href={route('sacraments.index')}
                                className="mt-4 inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Back to Sacraments
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
                            href={route('sacraments.index')}
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {canEdit ? 'Edit' : 'View'} Sacrament Record
                            </h2>                            <p className="text-sm text-gray-600">
                                {sacrament.member.first_name} {sacrament.member.last_name} - {sacrament.sacrament_type}
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
            <Head title={`Edit Sacrament - ${sacrament.sacrament_type}`} />

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
                                <div className="bg-purple-100 rounded-full p-2">
                                    <Church className="w-6 h-6 text-purple-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Sacramental Information</h3>
                                    <p className="text-sm text-gray-600">
                                        Update the sacrament record details
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} noValidate>
                            <div className="p-6 space-y-6">
                                {/* Member and Sacrament Type */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <SelectField
                                        id="sacrament_type"
                                        label="Sacrament Type"
                                        required
                                        placeholder="Select Sacrament"
                                        options={sacramentOptions}
                                        data={data}
                                        errors={errors}
                                        canEdit={canEdit}
                                        handleFieldChange={handleFieldChange}
                                    />
                                    <SelectField
                                        id="member_id"
                                        label="Member"
                                        required
                                        placeholder="Select Member"
                                        options={members.map(member => ({
                                            value: member.id.toString(),
                                            label: `${member.first_name} ${member.last_name} (${member.member_number})`
                                        }))}
                                        data={data}
                                        errors={errors}
                                        canEdit={canEdit}
                                        handleFieldChange={handleFieldChange}
                                    />
                                </div>

                                {/* Administration Details */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <Calendar className="w-5 h-5 mr-2 text-purple-600" />
                                        Administration Details
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormField
                                            id="date_administered"
                                            label="Date Administered"
                                            type="date"
                                            required
                                            data={data}
                                            errors={errors}
                                            canEdit={canEdit}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="administered_by"
                                            label="Administered By"
                                            required
                                            placeholder="Name of priest/minister"
                                            data={data}
                                            errors={errors}
                                            canEdit={canEdit}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="location"
                                            label="Location"
                                            required
                                            placeholder="Church name or location"
                                            className="md:col-span-2"
                                            data={data}
                                            errors={errors}
                                            canEdit={canEdit}
                                            handleFieldChange={handleFieldChange}
                                        />
                                    </div>
                                </div>

                                {/* Certificate and Witnesses */}
                                <div className="border-t border-gray-200 pt-6">
                                    <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <FileText className="w-5 h-5 mr-2 text-purple-600" />
                                        Certificate & Witnesses
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <FormField
                                            id="certificate_number"
                                            label="Certificate Number"
                                            placeholder="Certificate number (if any)"
                                            data={data}
                                            errors={errors}
                                            canEdit={canEdit}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="witness_1"
                                            label="Witness 1"
                                            placeholder="First witness name"
                                            data={data}
                                            errors={errors}
                                            canEdit={canEdit}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="witness_2"
                                            label="Witness 2"
                                            placeholder="Second witness name"
                                            data={data}
                                            errors={errors}
                                            canEdit={canEdit}
                                            handleFieldChange={handleFieldChange}
                                        />
                                    </div>
                                </div>

                                {/* Additional Notes */}
                                <div className="border-t border-gray-200 pt-6">
                                    <FormField
                                        id="notes"
                                        label="Additional Notes"
                                        type="textarea"
                                        placeholder="Any additional information about this sacrament..."
                                        rows={4}
                                        data={data}
                                        errors={errors}
                                        canEdit={canEdit}
                                        handleFieldChange={handleFieldChange}
                                    />
                                </div>

                                {/* Record History */}
                                <div className="bg-gray-50 p-4 rounded-lg border-t border-gray-200">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Record History</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span className="font-medium">Created:</span>{' '}
                                            {new Date(sacrament.created_at).toLocaleDateString()}
                                        </div>
                                        <div>
                                            <span className="font-medium">Last Updated:</span>{' '}
                                            {new Date(sacrament.updated_at).toLocaleDateString()}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-end items-center px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg space-x-3">
                                <Link
                                    href={route('sacraments.index')}
                                    className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    {canEdit ? 'Cancel' : 'Back to Sacraments'}
                                </Link>
                                {canEdit && (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-6 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50"
                                    >
                                        <Save className="w-4 h-4" />
                                        <span>{processing ? 'Updating Record...' : 'Update Sacrament Record'}</span>
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
                                <h3 className="text-lg font-medium text-gray-900">Delete Sacrament Record</h3>
                            </div>
                        </div>
                        <div className="mb-4">                            <p className="text-sm text-gray-500">
                                Are you sure you want to delete this {sacrament.sacrament_type} record for{' '}
                                <strong>{sacrament.member.first_name} {sacrament.member.last_name}</strong>? 
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