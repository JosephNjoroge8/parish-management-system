import React, { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Users, AlertCircle, Info, Church, Search, X, ChevronDown, Loader2, Eye } from 'lucide-react';
import { PageProps } from '@/types';
import { debounce } from 'lodash';
import axios from 'axios';

// Define Member interface
interface Member {
    id: number;
    local_church?: string;
    small_christian_community?: string;
    church_group?: string;
    additional_church_groups?: string[];
    first_name: string;
    middle_name?: string;
    last_name: string;
    date_of_birth?: string;
    gender?: string;
    phone?: string;
    email?: string;
    id_number?: string;
    godparent?: string;
    occupation?: string;
    education_level?: string;
    family_id?: number;
    parent?: string;
    minister?: string;
    tribe?: string;
    clan?: string;
    baptism_date?: string;
    residence?: string;
    confirmation_date?: string;
    matrimony_status?: string;
    marriage_type?: string;
    membership_date?: string;
    membership_status?: string;
    emergency_contact?: string;
    emergency_phone?: string;
    notes?: string;
}

// Define Family interface
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

interface EditMemberProps extends PageProps {
    member: Member;
    families: Family[];
}

// Enhanced form data interface matching our backend structure
interface MemberFormData {
    local_church: string;
    small_christian_community: string;
    church_group: string;
    additional_church_groups: string[];
    first_name: string;
    middle_name: string;
    last_name: string;
    date_of_birth: string;
    gender: string;
    phone: string;
    email: string;
    id_number: string;
    godparent: string;
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
    marriage_type: string;
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
    value: string | boolean;
    onChange: (value: string | boolean) => void;
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

    if (type === 'checkbox') {
        InputComponent = (
            <input
                type="checkbox"
                id={id}
                checked={Boolean(value)}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => onChange(e.target.checked)}
                className="rounded text-blue-500 focus:ring-blue-500"
                required={required}
            />
        );
    } else if (type === 'textarea') {
        InputComponent = (
            <textarea
                id={id}
                value={String(value)}
                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => onChange(e.target.value)}
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
                value={String(value)}
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => onChange(e.target.value)}
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
                value={String(value)}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => onChange(e.target.value)}
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

export default function EditMember({ auth, member, families = [] }: EditMemberProps) {
    const [activeTab, setActiveTab] = useState<string>('church');
    const [visibleFields, setVisibleFields] = useState<string[]>([]);
    const [requiredFields, setRequiredFields] = useState<string[]>([]);
    
    // Small Christian Community autocomplete state
    const [communitySearchQuery, setCommunitySearchQuery] = useState<string>('');
    const [communitySearchResults, setCommunitySearchResults] = useState<string[]>([]);
    const [showCommunityDropdown, setShowCommunityDropdown] = useState<boolean>(false);
    const [isSearchingCommunities, setIsSearchingCommunities] = useState<boolean>(false);
    const communitySearchRef = useRef<HTMLDivElement>(null);
    const communityInputRef = useRef<HTMLInputElement>(null);
    
    // Family search state
    const [familySearchQuery, setFamilySearchQuery] = useState<string>('');
    const [familySearchResults, setFamilySearchResults] = useState<Family[]>([]);
    const [showFamilyDropdown, setShowFamilyDropdown] = useState<boolean>(false);
    const [selectedFamily, setSelectedFamily] = useState<Family | null>(null);
    const [isSearchingFamilies, setIsSearchingFamilies] = useState<boolean>(false);
    const familySearchRef = useRef<HTMLDivElement>(null);
    const familyInputRef = useRef<HTMLInputElement>(null);
    
    // Multiple church groups state
    const [selectedAdditionalGroups, setSelectedAdditionalGroups] = useState<string[]>([]);
    
    // Local church options
    const localChurches = useMemo(() => [
        'St James Kangemi',
        'St Veronica Pembe Tatu',
        'Our Lady of Consolata Cathedral',
        'St Peter Kiawara',
        'Sacred Heart Kandara'
    ], []);
    
    // Church group options - Only 7 groups, with gender restrictions
    const churchGroups = useMemo(() => [
        { value: 'PMC', label: 'PMC (Pontifical Missionary Childhood)', description: 'Children\'s group', restriction: '' },
        { value: 'Youth', label: 'Youth', description: 'Young people (13-30 years)', restriction: '' },
        { value: 'C.W.A', label: 'C.W.A (Catholic Women Association)', description: 'Women\'s fellowship group', restriction: 'female' },
        { value: 'CMA', label: 'CMA (Catholic Men Association)', description: 'Men\'s fellowship group', restriction: 'male' },
        { value: 'Choir', label: 'Choir', description: 'Church music ministry', restriction: '' },
        { value: 'Catholic Action', label: 'Catholic Action', description: 'Catholic Action ministry', restriction: '' },
        { value: 'Pioneer', label: 'Pioneer', description: 'Pioneer total abstinence association', restriction: '' }
    ], []);
    
    // Education levels following Kenyan system
    const educationLevels = useMemo(() => [
        { value: 'none', label: 'No Formal Education' },
        { value: 'primary', label: 'Primary Education' },
        { value: 'kcpe', label: 'KCPE' },
        { value: 'secondary', label: 'Secondary Education' },
        { value: 'kcse', label: 'KCSE' },
        { value: 'certificate', label: 'Certificate' },
        { value: 'diploma', label: 'Diploma' },
        { value: 'degree', label: 'Degree' },
        { value: 'masters', label: 'Masters' },
        { value: 'phd', label: 'PhD' }
    ], []);
    
    // Initialize form with member data
    const { data, setData, put, processing, errors = {}, clearErrors } = useForm<MemberFormData>({
        local_church: member.local_church || '',
        small_christian_community: member.small_christian_community || '',
        church_group: member.church_group || '',
        additional_church_groups: member.additional_church_groups || [],
        first_name: member.first_name || '',
        middle_name: member.middle_name || '',
        last_name: member.last_name || '',
        date_of_birth: member.date_of_birth || '',
        gender: member.gender || '',
        phone: member.phone || '',
        email: member.email || '',
        id_number: member.id_number || '',
        godparent: member.godparent || '',
        occupation: member.occupation || '',
        education_level: member.education_level || '',
        family_id: member.family_id ? member.family_id.toString() : '',
        parent: member.parent || '',
        minister: member.minister || '',
        tribe: member.tribe || '',
        clan: member.clan || '',
        baptism_date: member.baptism_date || '',
        residence: member.residence || '',
        confirmation_date: member.confirmation_date || '',
        matrimony_status: member.matrimony_status || '',
        marriage_type: member.marriage_type || '',
        membership_date: member.membership_date || '',
        membership_status: member.membership_status || 'active',
        emergency_contact: member.emergency_contact || '',
        emergency_phone: member.emergency_phone || '',
        notes: member.notes || '',
    });

    // Initialize search queries and selected family from member data
    useEffect(() => {
        if (member.small_christian_community) {
            setCommunitySearchQuery(member.small_christian_community);
        }
        
        if (member.family_id) {
            const family = families.find(f => f.id === member.family_id);
            if (family) {
                setSelectedFamily(family);
                setFamilySearchQuery(family.family_name + (family.family_code ? ` (${family.family_code})` : ''));
            }
        }
        
        if (member.additional_church_groups) {
            setSelectedAdditionalGroups(member.additional_church_groups);
        }
        
        setFamilySearchResults(families.slice(0, 10));
    }, [member, families]);

    // Enhanced debounced community search function
    const debouncedCommunitySearch = useCallback(
        debounce(async (query: string) => {
            if (query.trim().length < 2) {
                setCommunitySearchResults([]);
                setIsSearchingCommunities(false);
                return;
            }

            setIsSearchingCommunities(true);
            
            try {
                const response = await axios.get(`/api/small-christian-communities/search`, {
                    params: { q: query, limit: 10 },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const results = response.data?.data || response.data || [];
                setCommunitySearchResults(results);
            } catch (error) {
                console.warn('Community search error:', error);
                setCommunitySearchResults([]);
            } finally {
                setIsSearchingCommunities(false);
            }
        }, 150),
        []
    );

    // Enhanced debounced family search function
    const debouncedFamilySearch = useCallback(
        debounce(async (query: string) => {
            if (query.trim().length < 2) {
                setFamilySearchResults(families.slice(0, 10));
                setIsSearchingFamilies(false);
                return;
            }

            setIsSearchingFamilies(true);
            
            try {
                const localResults = families.filter(family => 
                    family.family_name.toLowerCase().includes(query.toLowerCase()) ||
                    (family.family_code && family.family_code.toLowerCase().includes(query.toLowerCase()))
                ).slice(0, 10);
                
                setFamilySearchResults(localResults);
            } catch (error) {
                console.warn('Family search error:', error);
            } finally {
                setIsSearchingFamilies(false);
            }
        }, 150),
        [families]
    );

    // Optimized field change handler
    const handleInputChange = useCallback((field: keyof MemberFormData, value: string | boolean | string[]) => {
        setData((prev: any) => ({
            ...prev,
            [field]: value
        }));
        
        if (errors[field]) {
            clearErrors(field);
        }
    }, [setData, errors, clearErrors]);

    // Handle community search input change
    const handleCommunitySearchChange = useCallback((value: string) => {
        setCommunitySearchQuery(value);
        setShowCommunityDropdown(true);
        setData('small_christian_community', value);
        
        if (value.trim().length === 0) {
            setCommunitySearchResults([]);
        } else {
            debouncedCommunitySearch(value);
        }
    }, [setData, debouncedCommunitySearch]);

    // Handle community selection
    const handleCommunitySelect = useCallback((community: string) => {
        setCommunitySearchQuery(community);
        setData('small_christian_community', community);
        setShowCommunityDropdown(false);
        setCommunitySearchResults([]);
    }, [setData]);

    // Handle family search input change
    const handleFamilySearchChange = useCallback((value: string) => {
        setFamilySearchQuery(value);
        setShowFamilyDropdown(true);
        
        if (value.trim().length === 0) {
            setSelectedFamily(null);
            setData('family_id', '');
            setFamilySearchResults(families.slice(0, 10));
        } else {
            debouncedFamilySearch(value);
        }
    }, [setData, families, debouncedFamilySearch]);

    // Handle family selection
    const handleFamilySelect = useCallback((family: Family) => {
        setSelectedFamily(family);
        setFamilySearchQuery(family.family_name + (family.family_code ? ` (${family.family_code})` : ''));
        setData('family_id', family.id.toString());
        setShowFamilyDropdown(false);
        setFamilySearchResults([]);
    }, [setData]);

    // Clear family selection
    const clearFamilySelection = useCallback(() => {
        setSelectedFamily(null);
        setFamilySearchQuery('');
        setData('family_id', '');
        setShowFamilyDropdown(false);
        setFamilySearchResults(families.slice(0, 10));
        familyInputRef.current?.focus();
    }, [setData, families]);

    // Handle clicking outside of dropdowns
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (familySearchRef.current && !familySearchRef.current.contains(event.target as Node)) {
                setShowFamilyDropdown(false);
            }
            if (communitySearchRef.current && !communitySearchRef.current.contains(event.target as Node)) {
                setShowCommunityDropdown(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    // Update form fields based on church group
    const updateFormFieldsByGroup = useCallback((churchGroup: string): void => {
        const baseFields = [
            'local_church', 'small_christian_community', 'church_group', 'first_name', 'middle_name', 'last_name', 
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
                visible.push('occupation', 'education_level', 'godparent', 
                           'matrimony_status', 'marriage_type', 'minister', 'tribe', 'clan', 'family_id');
                required.push('matrimony_status');
                break;
                
            case 'CMA':
                visible.push('occupation', 'education_level', 'godparent', 
                           'matrimony_status', 'marriage_type', 'minister', 'tribe', 'clan', 'family_id');
                required.push('matrimony_status');
                break;
                
            case 'Youth':
                visible.push('education_level', 'parent', 'family_id', 'godparent', 'tribe', 'clan');
                required.push('education_level');
                break;
                
            case 'Choir':
                visible.push('occupation', 'education_level', 'godparent', 'family_id', 'tribe', 'clan');
                required.push('phone');
                break;
                
            case 'Catholic Action':
                visible.push('id_number', 'occupation', 'education_level', 'godparent', 
                           'matrimony_status', 'marriage_type', 'minister', 'tribe', 'clan', 'family_id');
                break;
                
            case 'Pioneer':
                visible.push('id_number', 'occupation', 'education_level', 'godparent', 
                           'matrimony_status', 'marriage_type', 'minister', 'tribe', 'clan', 'family_id');
                break;
        }

        setVisibleFields(visible);
        setRequiredFields(required);
    }, [setVisibleFields, setRequiredFields]);

    // Validate church group gender restrictions
    const validateChurchGroupGender = useCallback((group: string, gender: string): string | null => {
        if (group === 'C.W.A' && gender !== 'Female') {
            return 'C.W.A membership is restricted to female members only.';
        }
        
        if (group === 'CMA' && gender !== 'Male') {
            return 'CMA membership is restricted to male members only.';
        }
        
        return null;
    }, []);

    // Auto-update form fields when church group changes
    useEffect(() => {
        if (data.church_group) {
            updateFormFieldsByGroup(data.church_group);
        } else {
            setVisibleFields([
                'local_church', 'church_group', 'first_name', 'middle_name', 'last_name', 
                'date_of_birth', 'gender', 'phone', 'email', 'residence', 'membership_date', 
                'membership_status', 'emergency_contact', 'emergency_phone', 'notes'
            ]);
            setRequiredFields([
                'local_church', 'church_group', 'first_name', 'last_name', 'date_of_birth', 'gender'
            ]);
        }
    }, [data.church_group, updateFormFieldsByGroup]);

    // Enhanced form submission
    const handleSubmit = useCallback(async (e: React.FormEvent<HTMLFormElement>): Promise<void> => {
        e.preventDefault();
        
        if (clearErrors) {
            clearErrors();
        }
        
        // Validate required fields
        const missingFields = requiredFields.filter((field: string) => {
            const value = data[field as keyof MemberFormData];
            return typeof value === 'string' ? !value.trim() : !value;
        });
        
        if (missingFields.length > 0) {
            const firstMissingField = missingFields[0];
            const element = document.getElementById(firstMissingField);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.focus();
            }
            return;
        }
        
        put(route('members.update', member.id), {
            onSuccess: () => {
                router.visit(route('members.show', member.id));
            },
            onError: (validationErrors: any) => {
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
                    } else if (['family_id', 'parent', 'minister', 'godparent', 'tribe', 'clan', 'baptism_date', 'confirmation_date', 'matrimony_status'].includes(firstErrorField)) {
                        setActiveTab('church_details');
                    } else if (['phone', 'email', 'residence', 'emergency_contact', 'emergency_phone', 'notes'].includes(firstErrorField)) {
                        setActiveTab('contact');
                    }
                }
            }
        });
    }, [clearErrors, requiredFields, data, put, member.id]);

    // Calculate progress percentage
    const progressPercentage = useMemo(() => {
        const totalRequiredFields = requiredFields.length;
        const completedFields = requiredFields.filter((field: string) => {
            const value = data[field as keyof MemberFormData];
            return typeof value === 'string' ? value.trim() !== '' : Boolean(value);
        }).length;
        
        return totalRequiredFields > 0 ? Math.round((completedFields / totalRequiredFields) * 100) : 0;
    }, [requiredFields, data]);

    // Tabs configuration
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

    // Small Christian Community Search Component
    const SmallChristianCommunitySearchField = useCallback(() => {
        const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
            handleCommunitySearchChange(e.target.value);
        };

        return (
            <div className="relative" ref={communitySearchRef}>
                <label htmlFor="small_christian_community" className="block text-sm font-medium text-gray-700 mb-2">
                    Small Christian Community {isFieldRequired('small_christian_community') && <span className="text-red-500">*</span>}
                </label>
                <div className="relative">
                    <input
                        ref={communityInputRef}
                        type="text"
                        id="small_christian_community"
                        value={communitySearchQuery}
                        onChange={handleInputChange}
                        onFocus={() => setShowCommunityDropdown(true)}
                        className={`w-full px-4 py-2 pr-10 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors ${
                            hasError('small_christian_community') ? 'border-red-500' : 'border-gray-300'
                        }`}
                        placeholder="Type to search or enter new community name..."
                        autoComplete="off"
                    />
                    <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                        {isSearchingCommunities ? (
                            <Loader2 className="w-4 h-4 animate-spin text-blue-500" />
                        ) : (
                            <Search className="w-4 h-4 text-gray-400" />
                        )}
                    </div>
                </div>

                {showCommunityDropdown && communitySearchResults.length > 0 && (
                    <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        {communitySearchResults.map((community, index) => (
                            <button
                                key={index}
                                type="button"
                                onClick={() => handleCommunitySelect(community)}
                                className="w-full text-left p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 focus:bg-blue-50 focus:outline-none transition-colors"
                            >
                                <div className="font-medium text-gray-900">{community}</div>
                            </button>
                        ))}
                    </div>
                )}
                
                {hasError('small_christian_community') && (
                    <p className="mt-1 text-sm text-red-600">{getErrorMessage('small_christian_community')}</p>
                )}
            </div>
        );
    }, [
        communitySearchQuery, isSearchingCommunities, communitySearchResults, 
        showCommunityDropdown, handleCommunitySearchChange, handleCommunitySelect, 
        hasError, getErrorMessage, isFieldRequired
    ]);

    // Family Search Component
    const FamilySearchField = useCallback(() => {
        const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
            handleFamilySearchChange(e.target.value);
        };

        return (
            <div className="relative" ref={familySearchRef}>
                <label htmlFor="family_search" className="block text-sm font-medium text-gray-700 mb-2">
                    Family {isFieldRequired('family_id') && <span className="text-red-500">*</span>}
                </label>
                <div className="relative">
                    <input
                        ref={familyInputRef}
                        type="text"
                        id="family_search"
                        value={familySearchQuery}
                        onChange={handleInputChange}
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
                            </div>
                        </div>
                    </div>
                )}

                {showFamilyDropdown && familySearchResults.length > 0 && (
                    <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        {familySearchResults.map((family) => (
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
                            </button>
                        ))}
                    </div>
                )}
                
                {hasError('family_id') && (
                    <p className="mt-1 text-sm text-red-600">{getErrorMessage('family_id')}</p>
                )}
            </div>
        );
    }, [
        familySearchQuery, isSearchingFamilies, selectedFamily, familySearchResults, 
        showFamilyDropdown, handleFamilySearchChange, handleFamilySelect, 
        clearFamilySelection, hasError, getErrorMessage, isFieldRequired
    ]);

    // Render tab content
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
                                hasError={hasError('local_church')}
                                errorMessage={getErrorMessage('local_church')}
                            />

                            <div className="md:col-span-2">
                                <SmallChristianCommunitySearchField />
                            </div>

                            <div>
                                <FormInput
                                    id="church_group"
                                    label="Church Group"
                                    type="select"
                                    required
                                    placeholder="Select Church Group"
                                    value={data.church_group}
                                    onChange={(value) => {
                                        const genderError = validateChurchGroupGender(String(value), data.gender);
                                        if (genderError && data.gender) {
                                            alert(genderError);
                                            return;
                                        }
                                        handleInputChange('church_group', value);
                                    }}
                                    options={churchGroups
                                        .filter(group => {
                                            if (!data.gender) return true;
                                            if (group.restriction === 'male') return data.gender === 'Male';
                                            if (group.restriction === 'female') return data.gender === 'Female';
                                            return true;
                                        })
                                        .map(group => ({ value: group.value, label: group.label }))}
                                    hasError={hasError('church_group')}
                                    errorMessage={getErrorMessage('church_group')}
                                />
                                {data.church_group && (
                                    <div className="mt-2 p-2 bg-blue-50 rounded-lg">
                                        <p className="text-sm text-blue-700">
                                            {churchGroups.find(g => g.value === data.church_group)?.description}
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Additional Church Groups - Multiple Selection */}
                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Additional Church Groups (Optional)
                                </label>
                                <div className="space-y-2">
                                    <p className="text-sm text-gray-500 mb-3">
                                        Select additional groups this member belongs to (besides their primary group)
                                    </p>
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        {churchGroups
                                            .filter(group => {
                                                if (group.value === data.church_group) return false;
                                                if (!data.gender) return true;
                                                if (group.restriction === 'male') return data.gender === 'Male';
                                                if (group.restriction === 'female') return data.gender === 'Female';
                                                return true;
                                            })
                                            .map(group => (
                                                <label key={group.value} className="flex items-center space-x-2 p-2 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedAdditionalGroups.includes(group.value)}
                                                        onChange={(e) => {
                                                            const newGroups = e.target.checked
                                                                ? [...selectedAdditionalGroups, group.value]
                                                                : selectedAdditionalGroups.filter(g => g !== group.value);
                                                            setSelectedAdditionalGroups(newGroups);
                                                            setData('additional_church_groups', newGroups);
                                                        }}
                                                        className="rounded text-blue-500 focus:ring-blue-500"
                                                    />
                                                    <span className="text-sm font-medium text-gray-700">{group.label}</span>
                                                </label>
                                            ))}
                                    </div>
                                    {selectedAdditionalGroups.length > 0 && (
                                        <div className="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <p className="text-sm text-green-700 font-medium">Selected Additional Groups:</p>
                                            <div className="flex flex-wrap gap-2 mt-2">
                                                {selectedAdditionalGroups.map(groupValue => {
                                                    const group = churchGroups.find(g => g.value === groupValue);
                                                    return (
                                                        <span key={groupValue} className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            {group?.label}
                                                        </span>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            <FormInput
                                id="membership_date"
                                label="Membership Date"
                                type="date"
                                required
                                max={new Date().toISOString().split('T')[0]}
                                value={data.membership_date}
                                onChange={(value) => handleInputChange('membership_date', value)}
                                hasError={hasError('membership_date')}
                                errorMessage={getErrorMessage('membership_date')}
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
                                hasError={hasError('membership_status')}
                                errorMessage={getErrorMessage('membership_status')}
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
                                hasError={hasError('first_name')}
                                errorMessage={getErrorMessage('first_name')}
                            />

                            <FormInput
                                id="middle_name"
                                label="Middle Name"
                                maxLength={255}
                                value={data.middle_name}
                                onChange={(value) => handleInputChange('middle_name', value)}
                                placeholder="Enter middle name"
                                hasError={hasError('middle_name')}
                                errorMessage={getErrorMessage('middle_name')}
                            />

                            <FormInput
                                id="last_name"
                                label="Last Name"
                                required
                                maxLength={255}
                                value={data.last_name}
                                onChange={(value) => handleInputChange('last_name', value)}
                                placeholder="Enter last name"
                                hasError={hasError('last_name')}
                                errorMessage={getErrorMessage('last_name')}
                            />

                            <FormInput
                                id="date_of_birth"
                                label="Date of Birth"
                                type="date"
                                required
                                max={new Date().toISOString().split('T')[0]}
                                value={data.date_of_birth}
                                onChange={(value) => handleInputChange('date_of_birth', value)}
                                hasError={hasError('date_of_birth')}
                                errorMessage={getErrorMessage('date_of_birth')}
                            />

                            <FormInput
                                id="gender"
                                label="Gender"
                                type="select"
                                required
                                placeholder="Select Gender"
                                value={data.gender}
                                onChange={(value) => {
                                    const genderError = validateChurchGroupGender(data.church_group, String(value));
                                    if (genderError && data.church_group) {
                                        alert(genderError);
                                        return;
                                    }
                                    handleInputChange('gender', value);
                                }}
                                options={[
                                    { value: 'Male', label: 'Male' },
                                    { value: 'Female', label: 'Female' }
                                ]}
                                hasError={hasError('gender')}
                                errorMessage={getErrorMessage('gender')}
                            />

                            <FormInput
                                id="id_number"
                                label="ID Number (Optional)"
                                required={false}
                                maxLength={20}
                                value={data.id_number}
                                onChange={(value) => handleInputChange('id_number', value)}
                                placeholder="National ID number (optional)"
                                hasError={hasError('id_number')}
                                errorMessage={getErrorMessage('id_number')}
                            />

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
                                    hasError={hasError('occupation')}
                                    errorMessage={getErrorMessage('occupation')}
                                />
                            )}

                            {isFieldVisible('education_level') && (
                                <FormInput
                                    id="education_level"
                                    label="Education Level"
                                    type="select"
                                    required={isFieldRequired('education_level')}
                                    placeholder="Select Education Level"
                                    value={data.education_level}
                                    onChange={(value) => handleInputChange('education_level', value)}
                                    options={educationLevels}
                                    hasError={hasError('education_level')}
                                    errorMessage={getErrorMessage('education_level')}
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
                                    hasError={hasError('parent')}
                                    errorMessage={getErrorMessage('parent')}
                                />
                            )}

                            {isFieldVisible('minister') && (
                                <FormInput
                                    id="minister"
                                    label="Baptized By (Minister)"
                                    maxLength={255}
                                    value={data.minister}
                                    onChange={(value) => handleInputChange('minister', value)}
                                    placeholder="Name of the minister who baptized"
                                    hasError={hasError('minister')}
                                    errorMessage={getErrorMessage('minister')}
                                />
                            )}

                            {isFieldVisible('godparent') && (
                                <FormInput
                                    id="godparent"
                                    label="Godparent"
                                    maxLength={255}
                                    value={data.godparent}
                                    onChange={(value) => handleInputChange('godparent', value)}
                                    placeholder="Baptism godparent"
                                    hasError={hasError('godparent')}
                                    errorMessage={getErrorMessage('godparent')}
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
                                    hasError={hasError('tribe')}
                                    errorMessage={getErrorMessage('tribe')}
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
                                    hasError={hasError('clan')}
                                    errorMessage={getErrorMessage('clan')}
                                />
                            )}
                            
                            {isFieldVisible('baptism_date') && (
                                <FormInput
                                    id="baptism_date"
                                    label="Baptism Date"
                                    type="date"
                                    value={data.baptism_date}
                                    onChange={(value) => handleInputChange('baptism_date', value)}
                                    hasError={hasError('baptism_date')}
                                    errorMessage={getErrorMessage('baptism_date')}
                                />
                            )}

                            {isFieldVisible('confirmation_date') && (
                                <FormInput
                                    id="confirmation_date"
                                    label="Confirmation Date"
                                    type="date"
                                    value={data.confirmation_date}
                                    onChange={(value) => handleInputChange('confirmation_date', value)}
                                    hasError={hasError('confirmation_date')}
                                    errorMessage={getErrorMessage('confirmation_date')}
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
                                    hasError={hasError('matrimony_status')}
                                    errorMessage={getErrorMessage('matrimony_status')}
                                />
                            )}

                            {data.matrimony_status === 'married' && (
                                <FormInput
                                    id="marriage_type"
                                    label="Marriage Type"
                                    type="select"
                                    required
                                    placeholder="Select Marriage Type"
                                    value={data.marriage_type}
                                    onChange={(value) => handleInputChange('marriage_type', value)}
                                    options={[
                                        { value: 'customary', label: 'Customary Marriage' },
                                        { value: 'church', label: 'Church Marriage' }
                                    ]}
                                    hasError={hasError('marriage_type')}
                                    errorMessage={getErrorMessage('marriage_type')}
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
                                hasError={hasError('phone')}
                                errorMessage={getErrorMessage('phone')}
                            />

                            <FormInput
                                id="email"
                                label="Email Address"
                                type="email"
                                maxLength={255}
                                value={data.email}
                                onChange={(value) => handleInputChange('email', value)}
                                placeholder="member@email.com"
                                hasError={hasError('email')}
                                errorMessage={getErrorMessage('email')}
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
                                    hasError={hasError('residence')}
                                    errorMessage={getErrorMessage('residence')}
                                />
                                {selectedFamily && selectedFamily.address && (
                                    <p className="mt-1 text-sm text-blue-600">
                                        💡 Family address: {selectedFamily.address}
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
                                hasError={hasError('emergency_contact')}
                                errorMessage={getErrorMessage('emergency_contact')}
                            />

                            <FormInput
                                id="emergency_phone"
                                label="Emergency Contact Phone"
                                type="tel"
                                maxLength={20}
                                value={data.emergency_phone}
                                onChange={(value) => handleInputChange('emergency_phone', value)}
                                placeholder="+254 700 000 000"
                                hasError={hasError('emergency_phone')}
                                errorMessage={getErrorMessage('emergency_phone')}
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
                                    hasError={hasError('notes')}
                                    errorMessage={getErrorMessage('notes')}
                                />
                            </div>
                        </div>
                    </div>
                );

            default:
                return null;
        }
    }, [activeTab, data, isFieldVisible, isFieldRequired, handleInputChange, FormInput, SmallChristianCommunitySearchField, FamilySearchField, selectedFamily, churchGroups, localChurches, educationLevels, validateChurchGroupGender, hasError, getErrorMessage, selectedAdditionalGroups, setSelectedAdditionalGroups, setData]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('members.show', member.id)}
                        className="text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div className="flex-1">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Edit Member: {member.first_name} {member.last_name}
                        </h2>
                        <p className="text-sm text-gray-600">
                            Update member information in the parish database
                        </p>
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
            <Head title={`Edit ${member.first_name} ${member.last_name}`} />

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
                                        href={route('members.show', member.id)}
                                        className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors flex items-center space-x-2"
                                    >
                                        <Eye className="w-4 h-4" />
                                        <span>View Member</span>
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? (
                                            <Loader2 className="w-4 h-4 animate-spin" />
                                        ) : (
                                            <Save className="w-4 h-4" />
                                        )}
                                        <span>{processing ? 'Updating Member...' : 'Update Member'}</span>
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
