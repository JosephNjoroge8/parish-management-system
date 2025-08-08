import React, { useCallback, useState, useEffect, useMemo } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Church, Calendar, MapPin, FileText, Users, CheckCircle, AlertCircle, Search } from 'lucide-react';
import { PageProps, User } from '@/types';

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    member_number: string;
    id_number?: string;
}

interface CreateSacramentProps extends PageProps {
    auth: {
        user: User;
    };
    members?: Member[];
    member_id?: number;
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
    { value: 'baptism', label: 'Baptism', icon: '💧', description: 'The first sacrament of initiation' },
    { value: 'eucharist', label: 'First Holy Communion', icon: '🍞', description: 'First reception of the Eucharist' },
    { value: 'confirmation', label: 'Confirmation', icon: '🔥', description: 'Completion of baptismal grace' },
    { value: 'reconciliation', label: 'Reconciliation', icon: '🤝', description: 'Sacrament of penance' },
    { value: 'anointing', label: 'Anointing of the Sick', icon: '🙏', description: 'Sacrament for the sick' },
    { value: 'marriage', label: 'Marriage', icon: '💒', description: 'Sacrament of matrimony' },
    { value: 'holy_orders', label: 'Holy Orders', icon: '⛪', description: 'Ordination to priesthood' },
];

// --- FormField moved OUTSIDE main component ---
const FormField = ({
    id,
    label,
    type = 'text',
    required = false,
    placeholder = '',
    className = '',
    rows,
    data,
    completedFields,
    fieldTouched,
    errors,
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
    completedFields: Set<string>;
    fieldTouched: Set<string>;
    errors: any;
    handleFieldChange: (id: SacramentFormFieldKeys, value: string) => void;
}) => {
    const isTextarea = type === 'textarea';
    const isCompleted = completedFields.has(id);
    const isTouched = fieldTouched.has(id);
    const hasError = errors[id];
    return (
        <div className={`relative ${className}`}>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                <div className="flex items-center justify-between">
                    <span>
                        {label} {required && <span className="text-red-500">*</span>}
                    </span>
                    {isCompleted && <CheckCircle className="w-4 h-4 text-green-500" />}
                    {hasError && isTouched && <AlertCircle className="w-4 h-4 text-red-500" />}
                </div>
            </label>
            {isTextarea ? (
                <textarea
                    id={id}
                    value={data[id] || ''}
                    onChange={e => handleFieldChange(id, e.target.value)}
                    placeholder={placeholder}
                    rows={rows || 3}
                    aria-invalid={hasError ? 'true' : 'false'}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 ${
                        hasError ? 'border-red-500 bg-red-50' :
                        isCompleted ? 'border-green-400 bg-green-50' :
                        'border-gray-300 hover:border-gray-400'
                    }`}
                    required={required}
                />
            ) : (
                <input
                    type={type}
                    id={id}
                    value={data[id] || ''}
                    onChange={e => handleFieldChange(id, e.target.value)}
                    placeholder={placeholder}
                    aria-invalid={hasError ? 'true' : 'false'}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 ${
                        hasError ? 'border-red-500 bg-red-50' :
                        isCompleted ? 'border-green-400 bg-green-50' :
                        'border-gray-300 hover:border-gray-400'
                    }`}
                    required={required}
                    {...(type === 'date' && { max: new Date().toISOString().split('T')[0] })}
                />
            )}
            {hasError && (
                <p className="mt-1 text-sm text-red-600 flex items-center" role="alert">
                    <AlertCircle className="w-4 h-4 mr-1" />
                    {errors[id]}
                </p>
            )}
            {required && (
                <div className="mt-1 w-full bg-gray-200 rounded-full h-1">
                    <div
                        className={`h-1 rounded-full transition-all duration-300 ${
                            isCompleted ? 'bg-green-500 w-full' :
                            isTouched ? 'bg-yellow-500 w-1/2' :
                            'bg-gray-300 w-0'
                        }`}
                    />
                </div>
            )}
        </div>
    );
};
// --- End FormField ---

export default function CreateSacrament({ auth, members = [], member_id }: CreateSacramentProps) {
    const [memberSearch, setMemberSearch] = useState('');
    const [showMemberDropdown, setShowMemberDropdown] = useState(false);
    const [completedFields, setCompletedFields] = useState<Set<string>>(new Set());
    const [fieldTouched, setFieldTouched] = useState<Set<string>>(new Set());
    const [autoSaveEnabled, setAutoSaveEnabled] = useState(false);

    const safeMembers = Array.isArray(members) ? members : [];
    const getInitialMemberId = (): string => {
        if (!member_id || !safeMembers || safeMembers.length === 0) return '';
        const foundMember = safeMembers.find(member => member.id === member_id);
        return foundMember ? foundMember.id.toString() : '';
    };

    const filteredMembers = useMemo(() => {
        if (!memberSearch.trim()) return safeMembers;
        const searchTerm = memberSearch.toLowerCase();
        return safeMembers.filter(member => {
            const fullName = `${member.first_name} ${member.last_name}`.toLowerCase();
            const idNumber = member.id_number?.toLowerCase() || '';
            const memberId = member.id.toString().toLowerCase();
            return fullName.includes(searchTerm) || 
                   idNumber.includes(searchTerm) ||
                   memberId.includes(searchTerm) ||
                   member.first_name.toLowerCase().includes(searchTerm) ||
                   member.last_name.toLowerCase().includes(searchTerm);
        });
    }, [memberSearch, safeMembers]);

    const { data, setData, post, processing, errors, clearErrors } = useForm({
        member_id: getInitialMemberId(),
        sacrament_type: '',
        date_administered: '',
        administered_by: '',
        location: '',
        certificate_number: '',
        witness_1: '',
        witness_2: '',
        notes: '',
    });

    // Track field completion
    useEffect(() => {
        const completed = new Set<string>();
        Object.entries(data).forEach(([key, value]) => {
            if (value && value.toString().trim() !== '') {
                completed.add(key);
            }
        });
        setCompletedFields(completed);
    }, [data]);

    // Auto-save to localStorage
    useEffect(() => {
        if (autoSaveEnabled && Object.values(data).some(v => v !== '')) {
            localStorage.setItem('sacrament_draft', JSON.stringify(data));
        }
    }, [data, autoSaveEnabled]);
    useEffect(() => {
        const timer = setTimeout(() => {
            setAutoSaveEnabled(true);
        }, 1000);
        return () => clearTimeout(timer);
    }, []);

    const handleFieldChange = useCallback((field: SacramentFormFieldKeys, value: string) => {
        setData(field, value);
        setFieldTouched(prev => new Set([...prev, field]));
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, clearErrors]);

    useEffect(() => {
        if (data.sacrament_type && !data.date_administered) {
            const today = new Date().toISOString().split('T')[0];
            setData('date_administered', today);
        }
    }, [data.sacrament_type, setData]);

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
        post(route('sacraments.store'), {
            onSuccess: () => {
                localStorage.removeItem('sacrament_draft');
            },
            onError: (validationErrors: Record<string, string>) => {
                const firstErrorField = Object.keys(validationErrors)[0] as SacramentFormFieldKeys;
                if (firstErrorField) {
                    const element = document.getElementById(firstErrorField === 'member_id' ? 'member_search' : firstErrorField);
                    element?.focus();
                }
            }
        });
    }, [validateForm, clearErrors, post, data]);

    // MemberSearchField can remain as is, or you can move it out for further optimization if needed

    const progress = Math.round((completedFields.size / 9) * 100);

    if (safeMembers.length === 0) {
        return (
            <AuthenticatedLayout
                header={
                    <div className="flex items-center space-x-4">
                        <Link href={route('sacraments.index')} className="text-gray-600 hover:text-gray-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Add New Sacramental Record
                            </h2>
                            <p className="text-sm text-gray-600">Record a new sacrament administration</p>
                        </div>
                    </div>
                }
            >
                <Head title="Add New Sacrament" />
                <div className="py-12">
                    <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-white rounded-lg shadow-md p-8 text-center">
                            <Users className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Members Available</h3>
                            <p className="text-gray-600 mb-6">
                                You need to have registered members before you can create sacramental records.
                            </p>
                            <div className="space-x-4">
                                <Link
                                    href={route('members.create')}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Register New Member
                                </Link>
                                <Link
                                    href={route('members.index')}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    View Members
                                </Link>
                            </div>
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
                        <Link href={route('sacraments.index')} className="text-gray-600 hover:text-gray-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Add New Sacramental Record
                            </h2>
                            <p className="text-sm text-gray-600">Record a new sacrament administration</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <div className="text-sm text-gray-600">
                            Progress: {progress}%
                        </div>
                        <div className="w-32 bg-gray-200 rounded-full h-2">
                            <div 
                                className="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                style={{ width: `${progress}%` }}
                            />
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Add New Sacrament" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white rounded-lg shadow-md">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <div className="bg-purple-100 rounded-full p-2">
                                        <Church className="w-6 h-6 text-purple-600" />
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">Sacramental Information</h3>
                                        <p className="text-sm text-gray-600">
                                            Enter the details of the sacrament administration
                                        </p>
                                    </div>
                                </div>
                                {autoSaveEnabled && Object.values(data).some(v => v !== '') && (
                                    <div className="text-sm text-green-600 flex items-center">
                                        <CheckCircle className="w-4 h-4 mr-1" />
                                        Auto-saved
                                    </div>
                                )}
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} noValidate>
                            <div className="p-6 space-y-8">
                                {/* Member Selection */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* <MemberSearchField /> */}
                                    {/* <SelectField ... /> */}
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
                                            completedFields={completedFields}
                                            fieldTouched={fieldTouched}
                                            errors={errors}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="administered_by"
                                            label="Administered By"
                                            required
                                            placeholder="Name of priest/minister"
                                            data={data}
                                            completedFields={completedFields}
                                            fieldTouched={fieldTouched}
                                            errors={errors}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="location"
                                            label="Location"
                                            required
                                            placeholder="Church name or location"
                                            className="md:col-span-2"
                                            data={data}
                                            completedFields={completedFields}
                                            fieldTouched={fieldTouched}
                                            errors={errors}
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
                                            completedFields={completedFields}
                                            fieldTouched={fieldTouched}
                                            errors={errors}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="witness_1"
                                            label="Witness 1"
                                            placeholder="First witness name"
                                            data={data}
                                            completedFields={completedFields}
                                            fieldTouched={fieldTouched}
                                            errors={errors}
                                            handleFieldChange={handleFieldChange}
                                        />
                                        <FormField
                                            id="witness_2"
                                            label="Witness 2"
                                            placeholder="Second witness name"
                                            data={data}
                                            completedFields={completedFields}
                                            fieldTouched={fieldTouched}
                                            errors={errors}
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
                                        completedFields={completedFields}
                                        fieldTouched={fieldTouched}
                                        errors={errors}
                                        handleFieldChange={handleFieldChange}
                                    />
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-between items-center px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                                <div className="text-sm text-gray-600">
                                    {completedFields.size}/9 fields completed
                                </div>
                                <div className="flex space-x-3">
                                    <Link
                                        href={route('sacraments.index')}
                                        className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing || completedFields.size < 5}
                                        className="px-6 py-2 bg-purple-500 hover:bg-purple-600 disabled:bg-gray-400 text-white rounded-lg flex items-center space-x-2 transition-colors"
                                    >
                                        <Save className="w-4 h-4" />
                                        <span>{processing ? 'Creating Record...' : 'Create Sacrament Record'}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {/* Help Text */}
                    <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div className="flex items-start">
                            <Users className="w-5 h-5 text-blue-600 mr-2 mt-0.5" />
                            <div className="text-sm text-blue-800">
                                <p className="font-medium mb-1">Tips for faster data entry:</p>
                                <ul className="space-y-1 text-xs">
                                    <li>• Start typing in any field to see suggestions</li>
                                    <li>• Your progress is automatically saved as you type</li>
                                    <li>• Use the search box to quickly find members</li>
                                    <li>• Date suggestions are provided based on sacrament type</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}