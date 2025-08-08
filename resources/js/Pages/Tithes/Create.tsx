import React, { useState, useEffect, useRef } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, DollarSign, CreditCard, User, FileText } from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    member_number: string;
}

interface CreateTithingProps extends PageProps {
    auth: {
        user: User;
    };
    members: Member[];
    member_id?: number;
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

export function FormField({
    id,
    label,
    type = 'text',
    required = false,
    placeholder = '',
    className = '',
    rows,
    step,
    data,
    errors,
    handleFieldChange
}: {
    id: TithingFormFieldKeys;
    label: string;
    type?: string;
    required?: boolean;
    placeholder?: string;
    className?: string;
    rows?: number;
    step?: string;
    data: Record<string, any>;
    errors: Record<string, string>;
    handleFieldChange: (field: TithingFormFieldKeys, value: string) => void;
}) {
    const isTextarea = type === 'textarea';
    const formatCurrency = (amount: string) => {
        const num = parseFloat(amount);
        if (isNaN(num)) return '';
        return new Intl.NumberFormat('en-KE', {
            style: 'currency',
            currency: 'KES',
        }).format(num);
    };
    return (
        <div className={className}>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            {isTextarea ? (
                <textarea
                    id={id}
                    value={data[id]}
                    onChange={e => handleFieldChange(id, e.target.value)}
                    placeholder={placeholder}
                    rows={rows || 3}
                    aria-invalid={errors[id] ? 'true' : 'false'}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors ${
                        errors[id] ? 'border-red-500' : 'border-gray-300'
                    }`}
                    required={required}
                />
            ) : (
                <input
                    type={type}
                    id={id}
                    value={data[id]}
                    onChange={e => handleFieldChange(id, e.target.value)}
                    placeholder={placeholder}
                    aria-invalid={errors[id] ? 'true' : 'false'}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors ${
                        errors[id] ? 'border-red-500' : 'border-gray-300'
                    }`}
                    required={required}
                    {...(type === 'date' && { max: new Date().toISOString().split('T')[0] })}
                    {...(step && { step })}
                />
            )}
            {type === 'number' && data[id] && formatCurrency && (
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
}

export function SelectField({
    id,
    label,
    required = false,
    placeholder = 'Select an option',
    className = '',
    options,
    data,
    errors,
    handleFieldChange
}: {
    id: TithingFormFieldKeys;
    label: string;
    required?: boolean;
    placeholder?: string;
    className?: string;
    options: { value: string; label: string }[];
    data: Record<string, any>;
    errors: Record<string, string>;
    handleFieldChange: (field: TithingFormFieldKeys, value: string) => void;
}) {
    return (
        <div className={className}>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            <select
                id={id}
                value={data[id]}
                onChange={(e) => handleFieldChange(id, e.target.value)}
                aria-invalid={errors[id] ? 'true' : 'false'}
                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors ${
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
}

export default function CreateTithing({ auth, members = [], member_id }: CreateTithingProps) {
    const [showReferenceField, setShowReferenceField] = useState(false);
    const [memberInput, setMemberInput] = useState('');
    const [filteredMembers, setFilteredMembers] = useState<Member[]>(members);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const suggestionsRef = useRef<HTMLDivElement>(null);

    const { data, setData, post, processing, errors, clearErrors } = useForm({
        member_id: member_id?.toString() || '',
        amount: '',
        payment_date: new Date().toISOString().split('T')[0],
        payment_method: '',
        reference_number: '',
        offering_type: 'tithe',
        received_by: auth.user.name,
        notes: '',
    });

    // Filter members as user types
    useEffect(() => {
        if (memberInput.trim() === '') {
            setFilteredMembers(members);
            setShowSuggestions(false);
            if (data.member_id !== '') {
                setData('member_id', '');
            }
        } else {
            const input = memberInput.toLowerCase();
            const filtered = members.filter(
                m =>
                    m.first_name.toLowerCase().includes(input) ||
                    m.last_name.toLowerCase().includes(input) ||
                    m.member_number.toLowerCase().includes(input)
            );
            setFilteredMembers(filtered);
            setShowSuggestions(filtered.length > 0);
        }
    }, [memberInput, members]);

    // When a member is selected from the dropdown, update member_id and input
    const handleMemberSelect = (member: Member) => {
        setData('member_id', member.id.toString());
        setMemberInput(`${member.first_name} ${member.last_name} (${member.member_number})`);
        setFilteredMembers([member]);
        setShowSuggestions(false);
    };

    // Hide suggestions when user clicks outside
    useEffect(() => {
        const handleClick = (e: MouseEvent) => {
            if (
                suggestionsRef.current &&
                !suggestionsRef.current.contains(e.target as Node)
            ) {
                setShowSuggestions(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    // Show reference number field for non-cash payments
    useEffect(() => {
        const shouldShow = data.payment_method !== 'cash' && data.payment_method !== '';
        setShowReferenceField(shouldShow);
        if (data.payment_method === 'cash' && data.reference_number) {
            setData('reference_number', '');
        }
    }, [data.payment_method]);

    // Field change handler
    const handleFieldChange = (field: TithingFormFieldKeys, value: string) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    };

    // Validation
    const validateForm = (): boolean => {
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
    };

    // Submit handler
    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (!validateForm()) return;

        clearErrors();

        const payload = {
            ...data,
            tithe_type: data.offering_type,
            date_given: data.payment_date,
            recorded_by: data.received_by,
        };
        delete (payload as any).offering_type;
        delete (payload as any).payment_date;
        delete (payload as any).received_by;

        post(route('tithes.store'), {
            ...payload,
            onError: (validationErrors: Record<string, string>) => {
                const firstErrorField = Object.keys(validationErrors)[0] as TithingFormFieldKeys;
                if (firstErrorField) {
                    document.getElementById(firstErrorField)?.focus();
                }
            },
            onSuccess: () => {
                setMemberInput('');
                setShowSuggestions(false);
                window.alert('Contribution recorded successfully!');
            }
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('tithes.index')}
                        className="text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Add New Tithing Record
                        </h2>
                        <p className="text-sm text-gray-600">
                            Record a new tithe, offering, or contribution
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Add New Tithing Record" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white rounded-lg shadow-md">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center space-x-3">
                                <div className="bg-green-100 rounded-full p-2">
                                    <DollarSign className="w-6 h-6 text-green-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Financial Contribution</h3>
                                    <p className="text-sm text-gray-600">
                                        Enter the details of the financial contribution
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} noValidate>
                            <div className="p-6 space-y-6">
                                {/* Member and Type */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="relative" id="member-autocomplete" ref={suggestionsRef}>
                                        <label htmlFor="member_input" className="block text-sm font-medium text-gray-700 mb-2">
                                            Member <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="member_input"
                                            type="text"
                                            value={memberInput}
                                            onChange={e => {
                                                setMemberInput(e.target.value);
                                                if (data.member_id) setData('member_id', '');
                                                setShowSuggestions(true);
                                            }}
                                            placeholder="Type member name or number"
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors ${
                                                errors.member_id ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            autoComplete="off"
                                            required
                                            onFocus={() => {
                                                if (filteredMembers.length > 0 && memberInput) setShowSuggestions(true);
                                            }}
                                        />
                                        {/* Dropdown for filtered members */}
                                        {showSuggestions && memberInput && filteredMembers.length > 0 && !data.member_id && (
                                            <ul className="absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 max-h-48 overflow-y-auto w-full">
                                                {filteredMembers.slice(0, 10).map(member => (
                                                    <li
                                                        key={member.id}
                                                        className="px-4 py-2 hover:bg-green-100 cursor-pointer"
                                                        onClick={() => handleMemberSelect(member)}
                                                    >
                                                        {member.first_name} {member.last_name} ({member.member_number})
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                        {errors.member_id && (
                                            <p className="mt-1 text-sm text-red-600" role="alert">
                                                {errors.member_id}
                                            </p>
                                        )}
                                    </div>
                                    <SelectField
                                        id="offering_type"
                                        label="Contribution Type"
                                        required
                                        placeholder="Select Type"
                                        options={offeringTypeOptions}
                                        data={data}
                                        errors={errors}
                                        handleFieldChange={handleFieldChange}
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
                                            data={data}
                                            errors={errors}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="payment_date"
                                            label="Payment Date"
                                            type="date"
                                            required
                                            data={data}
                                            errors={errors}
                                            handleFieldChange={handleFieldChange}
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
                                            data={data}
                                            errors={errors}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        {showReferenceField && (
                                            <FormField
                                                id="reference_number"
                                                label="Reference Number"
                                                required={showReferenceField}
                                                placeholder="Transaction/Cheque number"
                                                data={data}
                                                errors={errors}
                                                handleFieldChange={handleFieldChange}
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
                                        data={data}
                                        errors={errors}
                                        handleFieldChange={handleFieldChange}
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
                                        data={data}
                                        errors={errors}
                                        handleFieldChange={handleFieldChange}
                                    />
                                </div>

                                {/* Summary Card */}
                                {data.amount && data.offering_type && data.member_id && (
                                    <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                        <h4 className="text-lg font-medium text-green-900 mb-2">Contribution Summary</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <span className="font-medium text-green-700">Member:</span>
                                                <div className="text-green-900">
                                                    {members.find(m => m.id.toString() === data.member_id)?.first_name}{' '}
                                                    {members.find(m => m.id.toString() === data.member_id)?.last_name}
                                                </div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-green-700">Type:</span>
                                                <div className="text-green-900">
                                                    {offeringTypeOptions.find(o => o.value === data.offering_type)?.label}
                                                </div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-green-700">Amount:</span>
                                                <div className="text-green-900 font-bold">
                                                    {new Intl.NumberFormat('en-KE', {
                                                        style: 'currency',
                                                        currency: 'KES',
                                                    }).format(parseFloat(data.amount))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-end items-center px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg space-x-3">
                                <Link
                                    href={route('tithes.index')}
                                    className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={`px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50 ${processing ? 'cursor-not-allowed' : ''}`}
                                >
                                    {processing ? (
                                        <>
                                            <svg className="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                            </svg>
                                            <span>Recording...</span>
                                        </>
                                    ) : (
                                        <>
                                            <Save className="w-4 h-4" />
                                            <span>Record Contribution</span>
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