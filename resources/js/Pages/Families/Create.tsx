// resources/js/Pages/Families/Create.jsx
import React, { useCallback } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Home, User, Phone, Mail, MapPin, Building, Users } from 'lucide-react';
import { PageProps } from '@/types';

interface AvailableMember {
    id: number;
    name: string;
    phone?: string;
    email?: string;
}

interface CreateFamilyProps extends PageProps {
    availableMembers: AvailableMember[];
    success?: string;
    error?: string;
}

interface FamilyFormData {
    family_name: string;
    family_code: string;
    address: string;
    phone: string;
    email: string;
    deanery: string;
    parish: string;
    parish_section: string;
    head_of_family_id: string;
}

type FamilyFormFieldKeys = keyof FamilyFormData;

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
                    aria-invalid={!!error}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                        error ? 'border-red-500' : 'border-gray-300'
                    }`}
                    required={required}
                />
            ) : (
                <input
                    type={type}
                    id={id}
                    value={value}
                    onChange={e => onChange(e.target.value)}
                    placeholder={placeholder}
                    aria-invalid={!!error}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                        error ? 'border-red-500' : 'border-gray-300'
                    }`}
                    required={required}
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

export default function CreateFamily({ auth, availableMembers = [], success, error }: CreateFamilyProps) {
    const { data, setData, post, processing, errors, clearErrors } = useForm({
        family_name: '',
        family_code: '',
        address: '',
        phone: '',
        email: '',
        deanery: '',
        parish: '',
        parish_section: '',
        head_of_family_id: '',
    });

    const handleFieldChange = useCallback((field: FamilyFormFieldKeys, value: string) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, errors, clearErrors]);

    const validateForm = useCallback((): boolean => {
        // Only family_name is required according to your controller
        if (!data.family_name?.trim()) {
            document.getElementById('family_name')?.focus();
            return false;
        }

        // Email validation if provided
        if (data.email?.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(data.email)) {
                document.getElementById('email')?.focus();
                return false;
            }
        }

        return true;
    }, [data]);

    const handleSubmit = useCallback((e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        
        if (!validateForm()) return;

        clearErrors();
        
        // Clean up empty fields before submission
        const cleanedData = Object.fromEntries(
            Object.entries(data).map(([key, value]) => [key, value?.trim() || null])
        );

        post(route('families.store'), {
            onSuccess: () => {
                // The controller will redirect to families.index with success message
            },
            onError: (validationErrors: Record<string, string>) => {
                const firstErrorField = Object.keys(validationErrors)[0] as keyof FamilyFormData;
                if (firstErrorField) {
                    document.getElementById(firstErrorField)?.focus();
                }
            }
        });
    }, [validateForm, clearErrors, post, data]);

    const SelectField = useCallback(({ 
        id, 
        label, 
        required = false, 
        placeholder = 'Select an option',
        className = '',
        options
    }: {
        id: keyof FamilyFormData;
        label: string;
        required?: boolean;
        placeholder?: string;
        className?: string;
        options: { value: string | number; label: string }[];
    }) => {
        // Stable onChange handler
        const handleSelectChange = useCallback((e: React.ChangeEvent<HTMLSelectElement>) => {
            handleFieldChange(id, e.target.value);
        }, [id]);
        
        return (
            <div className={className}>
                <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                    {label} {required && <span className="text-red-500">*</span>}
                </label>
                <select
                    id={id}
                    value={data[id] || ''}
                    onChange={handleSelectChange}
                    aria-invalid={errors[id] ? 'true' : 'false'}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                        errors[id] ? 'border-red-500' : 'border-gray-300'
                    }`}
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
    }, [data, errors, handleFieldChange]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('families.index')}
                        className="text-gray-600 hover:text-gray-900 transition-colors"
                        aria-label="Back to families list"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <Home className="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h2 className="font-bold text-2xl text-gray-800 leading-tight">
                                Add New Family
                            </h2>
                            <p className="text-sm text-gray-600">
                                Register a new family in the parish
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Add New Family" />

            <div className="py-8">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Success/Error Messages */}
                    {success && (
                        <div className="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                            {success}
                        </div>
                    )}
                    
                    {error && (
                        <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                            {error}
                        </div>
                    )}

                    <div className="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center space-x-3">
                                <div className="bg-blue-100 rounded-full p-2">
                                    <Home className="w-6 h-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Family Information</h3>
                                    <p className="text-sm text-gray-600">
                                        Enter the basic information for the new family
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} noValidate>
                            <div className="p-6 space-y-8">
                                {/* Basic Information */}
                                <div className="space-y-6">
                                    <h4 className="text-lg font-medium text-gray-900 flex items-center">
                                        <Users className="w-5 h-5 mr-2 text-blue-600" />
                                        Basic Information
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormField
                                            id="family_name"
                                            label="Family Name"
                                            required
                                            placeholder="e.g., The Njoroge Family"
                                            value={data.family_name}
                                            error={errors.family_name}
                                            onChange={val => handleFieldChange('family_name', val)}
                                        />
                                        <FormField
                                            id="family_code"
                                            label="Family Code"
                                            placeholder="e.g., FAM001 (optional)"
                                            value={data.family_code}
                                            error={errors.family_code}
                                            onChange={val => handleFieldChange('family_code', val)}
                                        />
                                    </div>
                                    
                                    {availableMembers && availableMembers.length > 0 && (
                                        <SelectField
                                            id="head_of_family_id"
                                            label="Head of Family"
                                            placeholder="Select a member as head of family (optional)"
                                            options={availableMembers.map(member => ({
                                                value: member.id,
                                                label: `${member.name}${member.phone ? ` - ${member.phone}` : ''}`
                                            }))}
                                        />
                                    )}
                                </div>

                                {/* Contact Information */}
                                <div className="border-t border-gray-200 pt-6 space-y-6">
                                    <h4 className="text-lg font-medium text-gray-900 flex items-center">
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
                                        />
                                        <FormField
                                            id="email"
                                            label="Email Address"
                                            type="email"
                                            placeholder="family@email.com"
                                            value={data.email}
                                            error={errors.email}
                                            onChange={val => handleFieldChange('email', val)}
                                        />
                                    </div>
                                    <FormField
                                        id="address"
                                        label="Address"
                                        type="textarea"
                                        placeholder="Full address including city and postal code"
                                        rows={3}
                                        value={data.address}
                                        error={errors.address}
                                        onChange={val => handleFieldChange('address', val)}
                                    />
                                </div>

                                {/* Parish Information */}
                                <div className="border-t border-gray-200 pt-6 space-y-6">
                                    <h4 className="text-lg font-medium text-gray-900 flex items-center">
                                        <Building className="w-5 h-5 mr-2 text-blue-600" />
                                        Parish Information
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <FormField
                                            id="parish"
                                            label="Parish"
                                            placeholder="e.g., St. Mary's Parish"
                                            value={data.parish}
                                            error={errors.parish}
                                            onChange={val => handleFieldChange('parish', val)}
                                        />
                                        <FormField
                                            id="deanery"
                                            label="Deanery"
                                            placeholder="e.g., Nairobi Deanery"
                                            value={data.deanery}
                                            error={errors.deanery}
                                            onChange={val => handleFieldChange('deanery', val)}
                                        />
                                        <FormField
                                            id="parish_section"
                                            label="Parish Section"
                                            placeholder="e.g., Central Section"
                                            value={data.parish_section}
                                            error={errors.parish_section}
                                            onChange={val => handleFieldChange('parish_section', val)}
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-end items-center px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl space-x-3">
                                <Link
                                    href={route('families.index')}
                                    className="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg flex items-center space-x-2 transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                                >
                                    <Save className="w-4 h-4" />
                                    <span>{processing ? 'Creating Family...' : 'Create Family'}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Helper Information */}
                    {(!availableMembers || availableMembers.length === 0) && (
                        <div className="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <User className="h-5 w-5 text-amber-400" aria-hidden="true" />
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-amber-800">
                                        No Available Members
                                    </h3>
                                    <div className="mt-2 text-sm text-amber-700">
                                        <p>
                                            There are no members without families available to assign as head of family. 
                                            You can create the family first and assign a head of family later when you add members.
                                        </p>
                                    </div>
                                    <div className="mt-4">
                                        <div className="-mx-2 -my-1.5 flex">
                                            <Link
                                                href={route('members.create')}
                                                className="bg-amber-50 px-2 py-1.5 rounded-md text-sm font-medium text-amber-800 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-amber-50 focus:ring-amber-600"
                                            >
                                                Add Member First
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}