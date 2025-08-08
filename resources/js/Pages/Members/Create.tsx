import React, { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Users, AlertCircle, Info, Church, Search, X, ChevronDown, Loader2 } from 'lucide-react';
import { PageProps } from '@/types';
import { debounce } from 'lodash';

// Define interfaces
interface Family {
    id: number;
    family_name: string;
    family_code?: string;
    head_of_family?: string;
    head_of_family_name?: string;
    address?: string;
    phone?: string;
    email?: string;
    parish_section?: string;
    deanery?: string;
    members_count?: number;
}

interface CreateMemberProps extends PageProps {
    families: Family[];
}

// Form data interface matching backend expectations
interface MemberFormData {
    local_church: string;
    church_group: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    date_of_birth: string;
    gender: string;
    phone: string;
    email: string;
    id_number: string;
    sponsor: string;
    occupation: string;
    education_level: string;
    family_id: string;
    parent: string;
    minister: string;
    tribe: string;
    clan: string;
    baptism_date: string;
    residence: string;
    confirmation_date: string;
    matrimony_status: string;
    membership_date: string;
    membership_status: string;
    emergency_contact: string;
    emergency_phone: string;
    notes: string;
}

const FormInput = ({
    id,
    label,
    type = 'text',
    required = false,
    placeholder = '',
    rows,
    value,
    onChange,
    className = '',
    maxLength,
    min,
    max,
    options = [],
    hasError,
    errorMessage,
}: {
    id: string;
    label: string;
    type?: string;
    required?: boolean;
    placeholder?: string;
    rows?: number;
    value: string;
    onChange: (value: string) => void;
    className?: string;
    maxLength?: number;
    min?: string;
    max?: string;
    options?: Array<{ value: string; label: string }>;
    hasError?: boolean;
    errorMessage?: string;
}) => {
    const inputClassName = `w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
        hasError ? 'border-red-500 focus:ring-red-200' : 'border-gray-300'
    } ${className}`;

    let InputComponent;

    if (type === 'textarea') {
        InputComponent = (
            <textarea
                id={id}
                value={value}
                onChange={e => onChange(e.target.value)}
                className={inputClassName}
                placeholder={placeholder}
                required={required}
                rows={rows || 3}
                maxLength={maxLength}
            />
        );
    } else if (type === 'select') {
        InputComponent = (
            <select
                id={id}
                value={value}
                onChange={e => onChange(e.target.value)}
                className={inputClassName}
                required={required}
            >
                <option value="">{placeholder}</option>
                {options.map(option => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        );
    } else {
        InputComponent = (
            <input
                type={type}
                id={id}
                value={value}
                onChange={e => onChange(e.target.value)}
                className={inputClassName}
                placeholder={placeholder}
                required={required}
                maxLength={maxLength}
                min={min}
                max={max}
            />
        );
    }

    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-2">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            {InputComponent}
            {hasError && (
                <p className="mt-1 text-sm text-red-600" role="alert">
                    {errorMessage}
                </p>
            )}
        </div>
    );
};

export default function CreateMember({ auth, families = [] }: CreateMemberProps) {
    const [activeTab, setActiveTab] = useState<string>('church');
    const [visibleFields, setVisibleFields] = useState<string[]>([]);
    const [requiredFields, setRequiredFields] = useState<string[]>([]);
    
    // Family search state
    const [familySearchQuery, setFamilySearchQuery] = useState<string>('');
    const [familySearchResults, setFamilySearchResults] = useState<Family[]>([]);
    const [showFamilyDropdown, setShowFamilyDropdown] = useState<boolean>(false);
    const [selectedFamily, setSelectedFamily] = useState<Family | null>(null);
    const [isSearchingFamilies, setIsSearchingFamilies] = useState<boolean>(false);
    const familySearchRef = useRef<HTMLDivElement>(null);
    const familyInputRef = useRef<HTMLInputElement>(null);
    
    // Local church options
    const localChurches = useMemo(() => [
        'St James Kangemi',
        'St Veronica Pembe Tatu',
        'Our Lady of Consolata Cathedral',
        'St Peter Kiawara',
        'Sacred Heart Kandara'
    ], []);
    
    // Church group options with descriptions
    const churchGroups = useMemo(() => [
        { value: 'PMC', label: 'PMC (Pontifical Missionary Childhood)', description: 'Children\'s group (under 13 years)' },
        { value: 'Youth', label: 'Youth', description: 'Young people (13-25 years)' },
        { value: 'Young Parents', label: 'Young Parents', description: 'Married couples with young children' },
        { value: 'C.W.A', label: 'C.W.A (Catholic Women Association)', description: 'Women\'s fellowship group' },
        { value: 'CMA', label: 'CMA (Catholic Men Association)', description: 'Men\'s fellowship group' },
        { value: 'Choir', label: 'Choir', description: 'Church music ministry' },
        { value: 'Catholic Action', label: 'Catholic Action', description: 'Catholic Action ministry' },
        { value: 'Pioneer', label: 'Pioneer', description: 'Pioneer youth movement' }
    ], []);
    
    const { data, setData, post, processing, errors = {}, clearErrors } = useForm({
        local_church: '',
        church_group: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        date_of_birth: '',
        gender: '',
        phone: '',
        email: '',
        id_number: '',
        sponsor: '',
        occupation: '',
        education_level: '',
        family_id: '',
        parent: '',
        minister: '',
        tribe: '',
        clan: '',
        baptism_date: '',
        residence: '',
        confirmation_date: '',
        matrimony_status: '',
        membership_date: new Date().toISOString().split('T')[0],
        membership_status: 'active',
        emergency_contact: '',
        emergency_phone: '',
        notes: '',
    });

    // Enhanced debounced family search function with better error handling
    const debouncedFamilySearch = useCallback(
        debounce(async (query: string) => {
            if (query.trim().length < 2) {
                setFamilySearchResults(families.slice(0, 10)); // Show first 10 families
                setIsSearchingFamilies(false);
                return;
            }

            setIsSearchingFamilies(true);
            
            try {
                // First, filter from local families data for instant results
                const localResults = families.filter(family => 
                    family.family_name.toLowerCase().includes(query.toLowerCase()) ||
                    (family.family_code && family.family_code.toLowerCase().includes(query.toLowerCase()))
                ).slice(0, 10);
                
                setFamilySearchResults(localResults);
                
                // Then, make API call for more comprehensive search
                const response = await fetch(`/api/families/search?q=${encodeURIComponent(query)}&limit=10`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (response.ok) {
                    const results = await response.json();
                    const apiResults = results.data || results || [];
                    
                    // Merge and deduplicate results
                    const mergedResults = [...localResults];
                    apiResults.forEach((apiFamily: Family) => {
                        if (!mergedResults.some(local => local.id === apiFamily.id)) {
                            mergedResults.push(apiFamily);
                        }
                    });
                    
                    setFamilySearchResults(mergedResults.slice(0, 10));
                } else {
                    console.warn('API family search failed, using local results');
                }
            } catch (error) {
                console.warn('Family search error, using local results:', error);
                // Fallback to local search
                const localResults = families.filter(family => 
                    family.family_name.toLowerCase().includes(query.toLowerCase()) ||
                    (family.family_code && family.family_code.toLowerCase().includes(query.toLowerCase()))
                ).slice(0, 10);
                setFamilySearchResults(localResults);
            } finally {
                setIsSearchingFamilies(false);
            }
        }, 150), // Reduced debounce time for faster response
        [families]
    );

    // Optimized field change handler to prevent input lag
    const handleInputChange = useCallback((field: keyof MemberFormData, value: string) => {
        setData(prev => ({
            ...prev,
            [field]: value
        }));
        
        // Clear error for this field immediately
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, errors, clearErrors]);

    // Handle family search input change with optimized performance
    const handleFamilySearchChange = useCallback((value: string) => {
        setFamilySearchQuery(value);
        setShowFamilyDropdown(true);
        
        if (value.trim().length === 0) {
            setSelectedFamily(null);
            setData('family_id', '');
            setFamilySearchResults(families.slice(0, 10)); // Show first 10 families when empty
        } else {
            debouncedFamilySearch(value);
        }
    }, [setData, families, debouncedFamilySearch]);

    // Handle family selection with auto-fill functionality
    const handleFamilySelect = useCallback((family: Family) => {
        setSelectedFamily(family);
        setFamilySearchQuery(family.family_name + (family.family_code ? ` (${family.family_code})` : ''));
        setData('family_id', family.id.toString());
        setShowFamilyDropdown(false);
        setFamilySearchResults([]);

        // Auto-fill member's address if family has address and member's address is empty
        if (family.address && !data.residence.trim()) {
            setData('residence', family.address);
        }
    }, [setData, data.residence]);

    // Clear family selection
    const clearFamilySelection = useCallback(() => {
        setSelectedFamily(null);
        setFamilySearchQuery('');
        setData('family_id', '');
        setShowFamilyDropdown(false);
        setFamilySearchResults(families.slice(0, 10));
        familyInputRef.current?.focus();
    }, [setData, families]);

    // Handle clicking outside of family search dropdown
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (familySearchRef.current && !familySearchRef.current.contains(event.target as Node)) {
                setShowFamilyDropdown(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    // Initialize family search results
    useEffect(() => {
        setFamilySearchResults(families.slice(0, 10));
    }, [families]);

    // Auto-update form fields when church group changes - OPTIMIZED
    useEffect(() => {
        if (data.church_group) {
            updateFormFieldsByGroup(data.church_group);
        } else {
            // Reset to show only basic fields when no group is selected
            setVisibleFields([
                'local_church', 'church_group', 'first_name', 'middle_name', 'last_name', 
                'date_of_birth', 'gender', 'phone', 'email', 'residence', 'membership_date', 
                'membership_status', 'emergency_contact', 'emergency_phone', 'notes'
            ]);
            setRequiredFields([
                'local_church', 'church_group', 'first_name', 'last_name', 'date_of_birth', 'gender'
            ]);
        }
    }, [data.church_group]);

    // Optimized function to determine fields based on church group
    const updateFormFieldsByGroup = useCallback((churchGroup: string): void => {
        const baseFields = [
            'local_church', 'church_group', 'first_name', 'middle_name', 'last_name', 
            'date_of_birth', 'gender', 'phone', 'email', 'residence', 'membership_date', 
            'membership_status', 'baptism_date', 'confirmation_date', 'emergency_contact', 
            'emergency_phone', 'notes'
        ];
        
        const baseRequired = [
            'local_church', 'church_group', 'first_name', 'last_name', 'date_of_birth', 'gender'
        ];

        let visible = [...baseFields];
        let required = [...baseRequired];

        switch (churchGroup) {
            case 'PMC':
                visible.push('parent', 'family_id');
                required.push('parent');
                break;
                
            case 'C.W.A':
            case 'CMA':
            case 'Young Parents':
                visible.push('id_number', 'occupation', 'education_level', 'sponsor', 
                           'matrimony_status', 'minister', 'tribe', 'clan', 'family_id');
                required.push('id_number', 'matrimony_status');
                break;
                
            case 'Youth':
                visible.push('id_number', 'education_level', 'parent', 'family_id');
                required.push('education_level');
                break;
                
            case 'Choir':
                visible.push('id_number', 'occupation', 'education_level', 'sponsor', 'family_id');
                required.push('phone');
                break;
        }

        setVisibleFields(visible);
        setRequiredFields(required);
    }, []);

    // Enhanced form submission with better error handling
    const handleSubmit = useCallback((e: React.FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        
        if (clearErrors) {
            clearErrors();
        }
        
        // Validate required fields before submission
        const missingFields = requiredFields.filter(field => !data[field as keyof MemberFormData]?.trim());
        
        if (missingFields.length > 0) {
            const firstMissingField = missingFields[0];
            const element = document.getElementById(firstMissingField);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.focus();
            }
            return;
        }
        
        post(route('members.store'), {
            onSuccess: (page) => {
                // Show success message
                alert('Member created successfully! Redirecting to member details...');
                
                // Optional: If the response contains the created member data,
                // we could redirect to the member's detail page
                // For now, the default Inertia behavior will handle the redirect
            },
            onError: (validationErrors) => {
                const firstErrorField = Object.keys(validationErrors)[0];
                if (firstErrorField) {
                    const element = document.getElementById(firstErrorField);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        element.focus();
                    }
                    
                    // Auto-switch to appropriate tab
                    if (['local_church', 'church_group', 'membership_date', 'membership_status'].includes(firstErrorField)) {
                        setActiveTab('church');
                    } else if (['first_name', 'last_name', 'middle_name', 'date_of_birth', 'gender', 'id_number', 'occupation', 'education_level'].includes(firstErrorField)) {
                        setActiveTab('personal');
                    } else if (['family_id', 'parent', 'minister', 'sponsor', 'tribe', 'clan', 'baptism_date', 'confirmation_date', 'matrimony_status'].includes(firstErrorField)) {
                        setActiveTab('church_details');
                    } else if (['phone', 'email', 'residence', 'emergency_contact', 'emergency_phone', 'notes'].includes(firstErrorField)) {
                        setActiveTab('contact');
                    }
                }
            }
        });
    }, [clearErrors, requiredFields, data, post]);

    // Memoized tabs configuration
    const tabs = useMemo(() => [
        { id: 'church', name: 'Church Membership', icon: Church },
        { id: 'personal', name: 'Personal Info', icon: Users },
        { id: 'church_details', name: 'Church Details', icon: Info },
        { id: 'contact', name: 'Contact Info', icon: AlertCircle },
    ], []);

    // Utility functions
    const isFieldVisible = useCallback((fieldName: string): boolean => {
        return visibleFields.includes(fieldName);
    }, [visibleFields]);

    const isFieldRequired = useCallback((fieldName: string): boolean => {
        return requiredFields.includes(fieldName);
    }, [requiredFields]);

    const getErrorMessage = useCallback((fieldName: string): string => {
        return errors[fieldName as keyof typeof errors] || '';
    }, [errors]);

    const hasError = useCallback((fieldName: string): boolean => {
        return Boolean(errors[fieldName as keyof typeof errors]);
    }, [errors]);

    // Enhanced Family Search Component
    const FamilySearchField = useCallback(() => (
        <div className="relative" ref={familySearchRef}>
            <label htmlFor="family_search" className="block text-sm font-medium text-gray-700 mb-2">
                Family {isFieldRequired('family_id') && <span className="text-red-500">*</span>}
            </label>
            <div className="relative">                <input
                    ref={familyInputRef}
                    type="text"
                    id="family_search"
                    value={familySearchQuery}
                    onChange={useCallback((e: React.ChangeEvent<HTMLInputElement>) => handleFamilySearchChange(e.target.value), [handleFamilySearchChange])}
                    onFocus={() => setShowFamilyDropdown(true)}
                    className={`w-full px-4 py-2 pr-10 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                        hasError('family_id') ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder="Search by family name or code..."
                    autoComplete="off"
                />
                <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                    {isSearchingFamilies ? (
                        <Loader2 className="w-4 h-4 animate-spin text-blue-500" />
                    ) : selectedFamily ? (
                        <button
                            type="button"
                            onClick={clearFamilySelection}
                            className="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <X className="w-4 h-4" />
                        </button>
                    ) : (
                        <Search className="w-4 h-4 text-gray-400" />
                    )}
                </div>
            </div>

            {/* Selected Family Display */}
            {selectedFamily && (
                <div className="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div className="flex items-start justify-between">
                        <div className="flex-1">
                            <h4 className="text-sm font-medium text-blue-900">
                                {selectedFamily.family_name}
                                {selectedFamily.family_code && (
                                    <span className="ml-2 text-blue-600">({selectedFamily.family_code})</span>
                                )}
                            </h4>
                            {selectedFamily.head_of_family_name && (
                                <p className="text-sm text-blue-700">Head: {selectedFamily.head_of_family_name}</p>
                            )}
                            {selectedFamily.parish_section && (
                                <p className="text-sm text-blue-600">Section: {selectedFamily.parish_section}</p>
                            )}
                            {selectedFamily.members_count !== undefined && (
                                <p className="text-sm text-blue-600">Members: {selectedFamily.members_count}</p>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={clearFamilySelection}
                            className="text-blue-400 hover:text-blue-600 transition-colors"
                        >
                            <X className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            )}

            {/* Search Results Dropdown */}
            {showFamilyDropdown && (
                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    {isSearchingFamilies && familySearchQuery.length >= 2 ? (
                        <div className="p-4 text-center text-gray-500">
                            <Loader2 className="w-6 h-6 animate-spin mx-auto mb-2 text-blue-500" />
                            Searching families...
                        </div>
                    ) : familySearchResults.length > 0 ? (
                        familySearchResults.map((family) => (
                            <button
                                key={family.id}
                                type="button"
                                onClick={() => handleFamilySelect(family)}
                                className="w-full text-left p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 focus:bg-blue-50 focus:outline-none transition-colors"
                            >
                                <div className="font-medium text-gray-900">
                                    {family.family_name}
                                    {family.family_code && (
                                        <span className="ml-2 text-sm text-gray-600">({family.family_code})</span>
                                    )}
                                </div>
                                {family.head_of_family_name && (
                                    <div className="text-sm text-gray-600">Head: {family.head_of_family_name}</div>
                                )}
                                <div className="flex items-center space-x-4 text-xs text-gray-500 mt-1">
                                    {family.parish_section && <span>Section: {family.parish_section}</span>}
                                    {family.members_count !== undefined && <span>Members: {family.members_count}</span>}
                                </div>
                            </button>
                        ))
                    ) : (
                        <div className="p-4 text-center text-gray-500">
                            <div className="mb-2">No families found</div>
                            <div className="text-sm">Try searching with a different name or code</div>
                        </div>
                    )}
                </div>
            )}
            
            {hasError('family_id') && (
                <p className="mt-1 text-sm text-red-600">{getErrorMessage('family_id')}</p>
            )}
            {!selectedFamily && familySearchQuery.length === 0 && (
                <p className="mt-1 text-sm text-gray-500">
                    Start typing to search for a family, or leave empty if not applicable
                </p>
            )}
        </div>
    ), [
        familySearchQuery, isSearchingFamilies, selectedFamily, familySearchResults, 
        showFamilyDropdown, handleFamilySearchChange, handleFamilySelect, 
        clearFamilySelection, hasError, getErrorMessage, isFieldRequired
    ]);

    // Render tab content with memoization for better performance
    const renderTabContent = useCallback((): JSX.Element | null => {
        switch (activeTab) {
            case 'church':
                return (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="local_church"
                                label="Local Church"
                                type="select"
                                required
                                placeholder="Select Local Church"
                                value={data.local_church}
                                onChange={(value) => handleInputChange('local_church', value)}
                                options={localChurches.map(church => ({ value: church, label: church }))}
                            />

                            <div>
                                <FormInput
                                    id="church_group"
                                    label="Church Group"
                                    type="select"
                                    required
                                    placeholder="Select Church Group"
                                    value={data.church_group}
                                    onChange={(value) => handleInputChange('church_group', value)}
                                    options={churchGroups.map(group => ({ value: group.value, label: group.label }))}
                                />
                                {data.church_group && (
                                    <div className="mt-2 p-2 bg-blue-50 rounded-lg">
                                        <p className="text-sm text-blue-700">
                                            {churchGroups.find(g => g.value === data.church_group)?.description}
                                        </p>
                                        <p className="text-xs text-blue-600 mt-1">
                                            Form fields have been customized for this group.
                                        </p>
                                    </div>
                                )}
                            </div>
                            
                            <FormInput
                                id="membership_date"
                                label="Membership Date"
                                type="date"
                                required
                                max={new Date().toISOString().split('T')[0]}
                                value={data.membership_date}
                                onChange={(value) => handleInputChange('membership_date', value)}
                            />

                            <FormInput
                                id="membership_status"
                                label="Membership Status"
                                type="select"
                                placeholder="Select Status"
                                value={data.membership_status}
                                onChange={(value) => handleInputChange('membership_status', value)}
                                options={[
                                    { value: 'active', label: 'Active' },
                                    { value: 'inactive', label: 'Inactive' },
                                    { value: 'transferred', label: 'Transferred' },
                                    { value: 'deceased', label: 'Deceased' }
                                ]}
                            />
                        </div>
                    </div>
                );

            case 'personal':
                return (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <FormInput
                                id="first_name"
                                label="First Name"
                                required
                                maxLength={255}
                                value={data.first_name}
                                onChange={(value) => handleInputChange('first_name', value)}
                                placeholder="Enter first name"
                            />

                            <FormInput
                                id="middle_name"
                                label="Middle Name"
                                maxLength={255}
                                value={data.middle_name}
                                onChange={(value) => handleInputChange('middle_name', value)}
                                placeholder="Enter middle name"
                            />

                            <FormInput
                                id="last_name"
                                label="Last Name"
                                required
                                maxLength={255}
                                value={data.last_name}
                                onChange={(value) => handleInputChange('last_name', value)}
                                placeholder="Enter last name"
                            />

                            <FormInput
                                id="date_of_birth"
                                label="Date of Birth"
                                type="date"
                                required
                                max={new Date().toISOString().split('T')[0]}
                                value={data.date_of_birth}
                                onChange={(value) => handleInputChange('date_of_birth', value)}
                            />

                            <FormInput
                                id="gender"
                                label="Gender"
                                type="select"
                                required
                                placeholder="Select Gender"
                                value={data.gender}
                                onChange={(value) => handleInputChange('gender', value)}
                                options={[
                                    { value: 'Male', label: 'Male' },
                                    { value: 'Female', label: 'Female' }
                                ]}
                            />

                            {isFieldVisible('id_number') && (
                                <FormInput
                                    id="id_number"
                                    label="ID Number"
                                    required={isFieldRequired('id_number')}
                                    maxLength={20}
                                    value={data.id_number}
                                    onChange={(value) => handleInputChange('id_number', value)}
                                    placeholder="National ID number"
                                />
                            )}

                            {isFieldVisible('occupation') && (
                                <FormInput
                                    id="occupation"
                                    label="Occupation"
                                    type="select"
                                    placeholder="Select Occupation"
                                    value={data.occupation}
                                    onChange={(value) => handleInputChange('occupation', value)}
                                    options={[
                                        { value: 'employed', label: 'Employed' },
                                        { value: 'self_employed', label: 'Self-employed' },
                                        { value: 'not_employed', label: 'Not Employed' }
                                    ]}
                                />
                            )}

                            {isFieldVisible('education_level') && (
                                <FormInput
                                    id="education_level"
                                    label="Education Level"
                                    required={isFieldRequired('education_level')}
                                    maxLength={255}
                                    value={data.education_level}
                                    onChange={(value) => handleInputChange('education_level', value)}
                                    placeholder="e.g., High School, University, Certificate"
                                />
                            )}
                        </div>
                    </div>
                );

            case 'church_details':
                return (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {isFieldVisible('family_id') && (
                                <div className="md:col-span-2">
                                    <FamilySearchField />
                                </div>
                            )}

                            {isFieldVisible('parent') && (
                                <FormInput
                                    id="parent"
                                    label="Parent/Guardian"
                                    required={isFieldRequired('parent')}
                                    maxLength={255}
                                    value={data.parent}
                                    onChange={(value) => handleInputChange('parent', value)}
                                    placeholder="Full name of parent or guardian"
                                />
                            )}

                            {isFieldVisible('minister') && (
                                <FormInput
                                    id="minister"
                                    label="Minister"
                                    maxLength={255}
                                    value={data.minister}
                                    onChange={(value) => handleInputChange('minister', value)}
                                    placeholder="Name of the minister"
                                />
                            )}

                            {isFieldVisible('sponsor') && (
                                <FormInput
                                    id="sponsor"
                                    label="Sponsor"
                                    maxLength={255}
                                    value={data.sponsor}
                                    onChange={(value) => handleInputChange('sponsor', value)}
                                    placeholder="Baptism/Confirmation sponsor"
                                />
                            )}
                            
                            {isFieldVisible('tribe') && (
                                <FormInput
                                    id="tribe"
                                    label="Tribe"
                                    maxLength={255}
                                    value={data.tribe}
                                    onChange={(value) => handleInputChange('tribe', value)}
                                    placeholder="Ethnic tribe"
                                />
                            )}

                            {isFieldVisible('clan') && (
                                <FormInput
                                    id="clan"
                                    label="Clan"
                                    maxLength={255}
                                    value={data.clan}
                                    onChange={(value) => handleInputChange('clan', value)}
                                    placeholder="Family clan"
                                />
                            )}

                            {isFieldVisible('baptism_date') && (
                                <FormInput
                                    id="baptism_date"
                                    label="Baptism Date"
                                    type="date"
                                    value={data.baptism_date}
                                    onChange={(value) => handleInputChange('baptism_date', value)}
                                />
                            )}

                            {isFieldVisible('confirmation_date') && (
                                <FormInput
                                    id="confirmation_date"
                                    label="Confirmation Date"
                                    type="date"
                                    value={data.confirmation_date}
                                    onChange={(value) => handleInputChange('confirmation_date', value)}
                                />
                            )}

                            {isFieldVisible('matrimony_status') && (
                                <FormInput
                                    id="matrimony_status"
                                    label="Matrimony Status"
                                    type="select"
                                    required={isFieldRequired('matrimony_status')}
                                    placeholder="Select Matrimony Status"
                                    value={data.matrimony_status}
                                    onChange={(value) => handleInputChange('matrimony_status', value)}
                                    options={[
                                        { value: 'single', label: 'Single' },
                                        { value: 'married', label: 'Married' },
                                        { value: 'widowed', label: 'Widowed' }
                                    ]}
                                />
                            )}
                        </div>
                    </div>
                );

            case 'contact':
                return (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="phone"
                                label="Phone Number"
                                type="tel"
                                required={isFieldRequired('phone')}
                                maxLength={20}
                                value={data.phone}
                                onChange={(value) => handleInputChange('phone', value)}
                                placeholder="+254 700 000 000"
                            />

                            <FormInput
                                id="email"
                                label="Email Address"
                                type="email"
                                maxLength={255}
                                value={data.email}
                                onChange={(value) => handleInputChange('email', value)}
                                placeholder="member@email.com"
                            />
                            
                            <div className="md:col-span-2">
                                <FormInput
                                    id="residence"
                                    label="Residence/Address"
                                    type="textarea"
                                    rows={3}
                                    value={data.residence}
                                    onChange={(value) => handleInputChange('residence', value)}
                                    placeholder="Enter residence address..."
                                />
                                {selectedFamily && selectedFamily.address && (
                                    <p className="mt-1 text-sm text-blue-600">
                                        💡 Auto-filled from family address: {selectedFamily.address}
                                    </p>
                                )}
                            </div>

                            <FormInput
                                id="emergency_contact"
                                label="Emergency Contact Name"
                                maxLength={255}
                                value={data.emergency_contact}
                                onChange={(value) => handleInputChange('emergency_contact', value)}
                                placeholder="Name of emergency contact"
                            />

                            <FormInput
                                id="emergency_phone"
                                label="Emergency Contact Phone"
                                type="tel"
                                maxLength={20}
                                value={data.emergency_phone}
                                onChange={(value) => handleInputChange('emergency_phone', value)}
                                placeholder="+254 700 000 000"
                            />

                            <div className="md:col-span-2">
                                <FormInput
                                    id="notes"
                                    label="Additional Notes"
                                    type="textarea"
                                    rows={4}
                                    value={data.notes}
                                    onChange={(value) => handleInputChange('notes', value)}
                                    placeholder="Any additional information about this member..."
                                />
                            </div>
                        </div>
                    </div>
                );

            default:
                return null;
        }
    }, [activeTab, data, isFieldVisible, isFieldRequired, handleInputChange, FormInput, FamilySearchField, selectedFamily, churchGroups, localChurches]);

    // Progress indicator
    const progressPercentage = useMemo(() => {
        const totalTabs = tabs.length;
        const currentTabIndex = tabs.findIndex(tab => tab.id === activeTab);
        return ((currentTabIndex + 1) / totalTabs) * 100;
    }, [activeTab, tabs]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('members.index')}
                        className="text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div className="flex-1">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Add New Parish Member
                        </h2>
                        <p className="text-sm text-gray-600">
                            Enter member information to add them to the parish database
                        </p>
                        {/* Progress bar */}
                        <div className="mt-2">
                            <div className="bg-gray-200 rounded-full h-1.5">
                                <div 
                                    className="bg-blue-500 h-1.5 rounded-full transition-all duration-300 ease-out"
                                    style={{ width: `${progressPercentage}%` }}
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Add Member" />

            <div className="py-8">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white rounded-lg shadow-lg">
                        {/* Tab Navigation */}
                        <div className="border-b border-gray-200 bg-gray-50 rounded-t-lg">
                            <nav className="flex space-x-8 px-6" aria-label="Tabs">
                                {tabs.map((tab, index) => {
                                    const Icon = tab.icon;
                                    const isActive = activeTab === tab.id;
                                    const isCompleted = tabs.findIndex(t => t.id === activeTab) > index;
                                    
                                    return (
                                        <button
                                            key={tab.id}
                                            onClick={() => setActiveTab(tab.id)}
                                            className={`${
                                                isActive
                                                    ? 'border-blue-500 text-blue-600 bg-white'
                                                    : isCompleted
                                                    ? 'border-green-500 text-green-600 bg-green-50'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            } whitespace-nowrap py-4 px-3 border-b-2 font-medium text-sm flex items-center space-x-2 transition-all duration-200 rounded-t-lg`}
                                        >
                                            <Icon className="w-4 h-4" />
                                            <span>{tab.name}</span>
                                            {isCompleted && (
                                                <span className="ml-1 text-green-500">✓</span>
                                            )}
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>

                        {/* Form Content */}
                        <form onSubmit={handleSubmit} noValidate>
                            <div className="p-6 min-h-[400px]">
                                {renderTabContent()}
                            </div>

                            {/* Submit Buttons */}
                            <div className="flex justify-between items-center px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                                <div className="flex space-x-3">
                                    {activeTab !== tabs[0].id && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                const currentIndex = tabs.findIndex(tab => tab.id === activeTab);
                                                if (currentIndex > 0) {
                                                    setActiveTab(tabs[currentIndex - 1].id);
                                                }
                                            }}
                                            className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors flex items-center space-x-2"
                                        >
                                            <ArrowLeft className="w-4 h-4" />
                                            <span>Previous</span>
                                        </button>
                                    )}
                                    {activeTab !== tabs[tabs.length - 1].id && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                const currentIndex = tabs.findIndex(tab => tab.id === activeTab);
                                                if (currentIndex < tabs.length - 1) {
                                                    setActiveTab(tabs[currentIndex + 1].id);
                                                }
                                            }}
                                            className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center space-x-2"
                                        >
                                            <span>Next</span>
                                            <ChevronDown className="w-4 h-4 rotate-[-90deg]" />
                                        </button>
                                    )}
                                </div>

                                <div className="flex space-x-3">
                                    <Link
                                        href={route('members.index')}
                                        className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing || !data.church_group}
                                        className="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? (
                                            <Loader2 className="w-4 h-4 animate-spin" />
                                        ) : (
                                            <Save className="w-4 h-4" />
                                        )}
                                        <span>{processing ? 'Saving Member...' : 'Save Member'}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}