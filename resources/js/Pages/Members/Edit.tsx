import React, { useState, useCallback, useMemo } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Users, AlertCircle, Info, Trash2, Eye, Loader2 } from 'lucide-react';
import { PageProps, Member, Family, User } from '@/types';

interface EditMemberProps extends PageProps {
    auth: {
        user: User;
    };
    member: Member;
    families: Family[];
}

// Simplified form data interface - remove index signature to avoid conflicts
interface MemberFormData {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    date_of_birth: string;
    gender: string;
    marital_status: string;
    occupation: string;
    address: string;
    family_id: string;
    relationship_to_head: string;
    membership_status: string;
    joining_date: string;
    emergency_contact: string;
    emergency_phone: string;
    notes: string;
}

type MemberFormFieldKeys = keyof MemberFormData;

interface Tab {
    id: string;
    name: string;
    icon: React.ComponentType<{ className?: string }>;
    fields: MemberFormFieldKeys[];
}

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
    disabled = false,
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
    disabled?: boolean;
}) => (
    <div className={className}>
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
            {label} {required && <span className="text-red-500">*</span>}
        </label>
        <select
            id={id}
            value={value}
            onChange={e => onChange(e.target.value)}
            disabled={!canEdit || disabled}
            aria-invalid={!!error}
            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                error ? 'border-red-500' : 'border-gray-300'
            } ${!canEdit || disabled ? 'bg-gray-100 cursor-not-allowed' : ''}`}
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

export default function EditMember({ auth, member, families }: EditMemberProps) {
    const [activeTab, setActiveTab] = useState<string>('personal');
    const [showDeleteConfirm, setShowDeleteConfirm] = useState<boolean>(false);
    const [isDeleting, setIsDeleting] = useState<boolean>(false);
    
    // Initialize form with safe data access
    const { data, setData, put, processing, errors, clearErrors, delete: destroy } = useForm({
        first_name: member?.first_name ?? '',
        last_name: member?.last_name ?? '',
        email: member?.email ?? '',
        phone: member?.phone ?? '',
        date_of_birth: member?.date_of_birth ?? '',
        gender: member?.gender ?? '',
        marital_status: member?.marital_status ?? 'single',
        occupation: member?.occupation ?? '',
        address: member?.address ?? '',
        family_id: member?.family_id?.toString() ?? '',
        relationship_to_head: member?.relationship_to_head ?? '',
        membership_status: member?.membership_status ?? 'active',
        joining_date: member?.joining_date ?? '',
        emergency_contact: member?.emergency_contact ?? '',
        emergency_phone: member?.emergency_phone ?? '',
        notes: member?.notes ?? '',
    });

    // Simplified computed values
    const safeFamilies = useMemo(() => families || [], [families]);
    const canEdit = useMemo(() => auth?.user?.permissions?.can_edit_members || false, [auth?.user?.permissions]);
    const canDelete = useMemo(() => auth?.user?.permissions?.can_delete_members || false, [auth?.user?.permissions]);

    // Tab configuration
    const tabs: Tab[] = useMemo(() => [
        { 
            id: 'personal', 
            name: 'Personal Info', 
            icon: Users,
            fields: ['first_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'occupation']
        },
        { 
            id: 'family', 
            name: 'Family & Parish', 
            icon: Users,
            fields: ['family_id', 'relationship_to_head', 'membership_status', 'joining_date']
        },
        { 
            id: 'contact', 
            name: 'Contact & Emergency', 
            icon: AlertCircle,
            fields: ['email', 'phone', 'address', 'emergency_contact', 'emergency_phone']
        },
        { 
            id: 'additional', 
            name: 'Additional Info', 
            icon: Info,
            fields: ['notes']
        },
    ], []);

    // Simplified field change handler
    const handleFieldChange = useCallback((field: MemberFormFieldKeys, value: string) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, errors, clearErrors]);

    // Streamlined validation
    const validateForm = useCallback((): boolean => {
        const requiredFields: MemberFormFieldKeys[] = [
            'first_name', 'last_name', 'date_of_birth', 'gender', 'membership_status', 'joining_date'
        ];

        // Check required fields
        for (const field of requiredFields) {
            if (!data[field]?.trim()) {
                const element = document.getElementById(field);
                if (element) {
                    element.focus();
                    const tabWithField = tabs.find(tab => tab.fields.includes(field));
                    if (tabWithField) {
                        setActiveTab(tabWithField.id);
                    }
                }
                return false;
            }
        }

        // Email validation
        if (data.email?.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(data.email)) {
                document.getElementById('email')?.focus();
                setActiveTab('contact');
                return false;
            }
        }

        // Date validation
        const today = new Date();
        const dateFields = [
            { field: 'date_of_birth' as const, tab: 'personal' },
            { field: 'joining_date' as const, tab: 'family' }
        ];

        for (const { field, tab } of dateFields) {
            if (data[field]) {
                const date = new Date(data[field]);
                if (isNaN(date.getTime()) || date > today) {
                    document.getElementById(field)?.focus();
                    setActiveTab(tab);
                    return false;
                }
            }
        }

        return true;
    }, [data, tabs]);

    // Form submission
    const handleSubmit = useCallback((e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        
        if (!validateForm()) return;

        clearErrors();
        
        put(route('members.update', member.id), {
            onError: (validationErrors: Record<string, string>) => {
                const firstErrorField = Object.keys(validationErrors)[0] as MemberFormFieldKeys;
                if (firstErrorField) {
                    const element = document.getElementById(firstErrorField);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        element.focus();
                        const tabWithField = tabs.find(tab => tab.fields.includes(firstErrorField));
                        if (tabWithField) {
                            setActiveTab(tabWithField.id);
                        }
                    }
                }
            }
        });
    }, [validateForm, clearErrors, put, member.id, tabs]);

    // Delete handler
    const handleDelete = useCallback(() => {
        if (!member?.id) return;
        
        setIsDeleting(true);
        destroy(route('members.destroy', member.id), {
            onError: () => {
                setIsDeleting(false);
                alert('Failed to delete member. Please try again.');
            },
            onFinish: () => {
                setIsDeleting(false);
                setShowDeleteConfirm(false);
            }
        });
    }, [destroy, member?.id]);

    // Tab content renderer
    const renderTabContent = () => {
        switch (activeTab) {
            case 'personal':
                return (
                    <div className="space-y-6">
                        {member?.member_number && (
                            <div className="bg-blue-50 p-4 rounded-lg">
                                <div className="flex items-center space-x-2 text-blue-800">
                                    <Info className="w-4 h-4" />
                                    <span className="text-sm font-medium">
                                        Member Number: {member.member_number}
                                    </span>
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormField
                                id="first_name"
                                label="First Name"
                                required
                                placeholder="Enter first name"
                                value={data.first_name}
                                error={errors.first_name}
                                onChange={val => handleFieldChange('first_name', val)}
                                canEdit={canEdit}
                            />
                            <FormField
                                id="last_name"
                                label="Last Name"
                                required
                                placeholder="Enter last name"
                                value={data.last_name}
                                error={errors.last_name}
                                onChange={val => handleFieldChange('last_name', val)}
                                canEdit={canEdit}
                            />
                            <FormField
                                id="date_of_birth"
                                label="Date of Birth"
                                type="date"
                                required
                                value={data.date_of_birth}
                                error={errors.date_of_birth}
                                onChange={val => handleFieldChange('date_of_birth', val)}
                                canEdit={canEdit}
                            />
                            <SelectField
                                id="gender"
                                label="Gender"
                                required
                                options={[
                                    { value: 'Male', label: 'Male' },
                                    { value: 'Female', label: 'Female' }
                                ]}
                                placeholder="Select Gender"
                                value={data.gender}
                                error={errors.gender}
                                onChange={val => handleFieldChange('gender', val)}
                                canEdit={canEdit}
                            />
                            <SelectField
                                id="marital_status"
                                label="Marital Status"
                                options={[
                                    { value: 'single', label: 'Single' },
                                    { value: 'married', label: 'Married' },
                                    { value: 'divorced', label: 'Divorced' },
                                    { value: 'widowed', label: 'Widowed' }
                                ]}
                                value={data.marital_status}
                                error={errors.marital_status}
                                onChange={val => handleFieldChange('marital_status', val)}
                                canEdit={canEdit}
                            />
                            <FormField
                                id="occupation"
                                label="Occupation"
                                placeholder="Current occupation"
                                value={data.occupation}
                                error={errors.occupation}
                                onChange={val => handleFieldChange('occupation', val)}
                                canEdit={canEdit}
                            />
                        </div>
                    </div>
                );

            case 'family':
                return (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <SelectField
                                id="family_id"
                                label="Family"
                                options={safeFamilies.map(family => ({
                                    value: family.id.toString(),
                                    label: `${family.family_name} - ${family.head_of_family}`
                                }))}
                                placeholder="Select Family (Optional)"
                                value={data.family_id}
                                error={errors.family_id}
                                onChange={val => handleFieldChange('family_id', val)}
                                canEdit={canEdit}
                            />
                            <SelectField
                                id="relationship_to_head"
                                label="Relationship to Family Head"
                                disabled={!data.family_id}
                                options={[
                                    { value: 'head', label: 'Head of Family' },
                                    { value: 'spouse', label: 'Spouse' },
                                    { value: 'child', label: 'Child' },
                                    { value: 'parent', label: 'Parent' },
                                    { value: 'sibling', label: 'Sibling' },
                                    { value: 'relative', label: 'Other Relative' }
                                ]}
                                placeholder="Select Relationship"
                                value={data.relationship_to_head}
                                error={errors.relationship_to_head}
                                onChange={val => handleFieldChange('relationship_to_head', val)}
                                canEdit={canEdit}
                            />
                            <SelectField
                                id="membership_status"
                                label="Membership Status"
                                required
                                options={[
                                    { value: 'active', label: 'Active' },
                                    { value: 'inactive', label: 'Inactive' },
                                    { value: 'transferred', label: 'Transferred' },
                                    { value: 'deceased', label: 'Deceased' }
                                ]}
                                value={data.membership_status}
                                error={errors.membership_status}
                                onChange={val => handleFieldChange('membership_status', val)}
                                canEdit={canEdit}
                            />
                            <FormField
                                id="joining_date"
                                label="Joining Date"
                                type="date"
                                required
                                value={data.joining_date}
                                error={errors.joining_date}
                                onChange={val => handleFieldChange('joining_date', val)}
                                canEdit={canEdit}
                            />
                        </div>
                    </div>
                );

            case 'contact':
                return (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormField
                                id="email"
                                label="Email Address"
                                type="email"
                                placeholder="member@email.com"
                                value={data.email}
                                error={errors.email}
                                onChange={val => handleFieldChange('email', val)}
                                canEdit={canEdit}
                            />
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
                                id="address"
                                label="Address"
                                type="textarea"
                                placeholder="Enter full address..."
                                className="md:col-span-2"
                                rows={3}
                                value={data.address}
                                error={errors.address}
                                onChange={val => handleFieldChange('address', val)}
                                canEdit={canEdit}
                            />
                            <FormField
                                id="emergency_contact"
                                label="Emergency Contact Name"
                                placeholder="Name of emergency contact"
                                value={data.emergency_contact}
                                error={errors.emergency_contact}
                                onChange={val => handleFieldChange('emergency_contact', val)}
                                canEdit={canEdit}
                            />
                            <FormField
                                id="emergency_phone"
                                label="Emergency Contact Phone"
                                type="tel"
                                placeholder="+254 700 000 000"
                                value={data.emergency_phone}
                                error={errors.emergency_phone}
                                onChange={val => handleFieldChange('emergency_phone', val)}
                                canEdit={canEdit}
                            />
                        </div>
                    </div>
                );

            case 'additional':
                return (
                    <div className="space-y-6">
                        <FormField
                            id="notes"
                            label="Additional Notes"
                            type="textarea"
                            rows={6}
                            placeholder="Any additional information about this member..."
                            value={data.notes}
                            error={errors.notes}
                            onChange={val => handleFieldChange('notes', val)}
                            canEdit={canEdit}
                        />
                    </div>
                );

            default:
                return null;
        }
    };

    // Guard clause for missing member
    if (!member) {
        return (
            <AuthenticatedLayout>
                <Head title="Member Not Found" />
                <div className="py-12">
                    <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
                            <div className="flex items-center">
                                <AlertCircle className="w-5 h-5 text-red-600 mr-2" />
                                <p className="text-red-800">Member not found or access denied.</p>
                            </div>
                            <Link
                                href={route('members.index')}
                                className="mt-4 inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Back to Members
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
                            href={route('members.index')}
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {canEdit ? 'Edit' : 'View'} Member: {member.first_name} {member.last_name}
                            </h2>
                            {member.member_number && (
                                <p className="text-sm text-gray-600">
                                    Member #{member.member_number}
                                </p>
                            )}
                        </div>
                    </div>
                    
                    <div className="flex space-x-3">
                        <Link
                            href={route('members.show', member.id)}
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
            <Head title={`${canEdit ? 'Edit' : 'View'} ${member.first_name} ${member.last_name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {!canEdit && (
                        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div className="flex items-center">
                                <AlertCircle className="w-5 h-5 text-yellow-600 mr-2" />
                                <p className="text-yellow-800 text-sm">
                                    You have read-only access. Contact an administrator to make changes.
                                </p>
                            </div>
                        </div>
                    )}

                    <div className="bg-white rounded-lg shadow-md">
                        {/* Tab Navigation */}
                        <div className="border-b border-gray-200">
                            <nav className="flex space-x-8 px-6">
                                {tabs.map((tab) => {
                                    const Icon = tab.icon;
                                    const hasErrors = tab.fields.some(field => errors[field]);
                                    return (
                                        <button
                                            key={tab.id}
                                            onClick={() => setActiveTab(tab.id)}
                                            className={`${
                                                activeTab === tab.id
                                                    ? 'border-blue-500 text-blue-600'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                                            } py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2 transition-colors ${
                                                hasErrors ? 'text-red-600' : ''
                                            }`}
                                        >
                                            <Icon className="w-4 h-4" />
                                            <span>{tab.name}</span>
                                            {hasErrors && (
                                                <span className="w-2 h-2 bg-red-500 rounded-full"></span>
                                            )}
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>

                        {/* Form Content */}
                        <form onSubmit={handleSubmit}>
                            <div className="p-6">
                                {renderTabContent()}
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-between items-center px-6 py-4 bg-gray-50 border-t">
                                <div className="flex space-x-3">
                                    {activeTab !== 'personal' && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                const currentIndex = tabs.findIndex(tab => tab.id === activeTab);
                                                setActiveTab(tabs[currentIndex - 1].id);
                                            }}
                                            className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                        >
                                            Previous
                                        </button>
                                    )}
                                    {activeTab !== 'additional' && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                const currentIndex = tabs.findIndex(tab => tab.id === activeTab);
                                                setActiveTab(tabs[currentIndex + 1].id);
                                            }}
                                            className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                                        >
                                            Next
                                        </button>
                                    )}
                                </div>

                                <div className="flex space-x-3">
                                    <Link
                                        href={route('members.index')}
                                        className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        {canEdit ? 'Cancel' : 'Back'}
                                    </Link>
                                    {canEdit && (
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50"
                                        >
                                            {processing ? (
                                                <Loader2 className="w-4 h-4 animate-spin" />
                                            ) : (
                                                <Save className="w-4 h-4" />
                                            )}
                                            <span>{processing ? 'Updating...' : 'Update Member'}</span>
                                        </button>
                                    )}
                                </div>
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
                                <h3 className="text-lg font-medium text-gray-900">Delete Member</h3>
                            </div>
                        </div>
                        <div className="mb-4">
                            <p className="text-sm text-gray-500">
                                Are you sure you want to delete <strong>{member.first_name} {member.last_name}</strong>? 
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
                                    <span>Delete</span>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}