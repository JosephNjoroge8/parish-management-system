import React, { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Users, AlertCircle, Info, Church, Search, X, ChevronDown, Loader2 } from 'lucide-react';
import { PageProps } from '@/types';
import { debounce } from 'lodash';
import axios from 'axios';

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
    // Baptism Record Fields
    father_name: string;
    mother_name: string;
    birth_village: string;
    county: string;
    baptism_location: string;
    baptized_by: string;
    eucharist_location: string;
    eucharist_date: string;
    confirmation_location: string;
    confirmation_reg_no: string;
    confirmation_no: string;
    marriage_spouse: string;
    marriage_location: string;
    marriage_date: string;
    marriage_reg_no: string;
    marriage_no: string;
    // Marriage Record Fields
    record_number: string;
    husband_name: string;
    husband_father_name: string;
    husband_mother_name: string;
    husband_tribe: string;
    husband_clan: string;
    husband_birth_place: string;
    husband_domicile: string;
    husband_baptized_at: string;
    husband_baptism_date: string;
    husband_widower_of: string;
    husband_parent_consent: boolean;
    wife_name: string;
    wife_father_name: string;
    wife_mother_name: string;
    wife_tribe: string;
    wife_clan: string;
    wife_birth_place: string;
    wife_domicile: string;
    wife_baptized_at: string;
    wife_baptism_date: string;
    wife_widow_of: string;
    wife_parent_consent: boolean;
    banas_number: string;
    banas_church_1: string;
    banas_date_1: string;
    banas_church_2: string;
    banas_date_2: string;
    dispensation_from: string;
    dispensation_given_by: string;
    dispensation_impediment: string;
    dispensation_date: string;
    marriage_church: string;
    district: string;
    province: string;
    presence_of: string;
    delegated_by: string;
    delegation_date: string;
    male_witness_name: string;
    male_witness_father: string;
    male_witness_clan: string;
    female_witness_name: string;
    female_witness_father: string;
    female_witness_clan: string;
    civil_marriage_certificate: string;
    other_documents: string;
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
        // Baptism Record Fields
        father_name: '',
        mother_name: '',
        birth_village: '',
        county: '',
        baptism_location: '',
        baptized_by: '',
        eucharist_location: '',
        eucharist_date: '',
        confirmation_location: '',
        confirmation_reg_no: '',
        confirmation_no: '',
        marriage_spouse: '',
        marriage_location: '',
        marriage_date: '',
        marriage_reg_no: '',
        marriage_no: '',
        // Marriage Record Fields
        record_number: '',
        husband_name: '',
        husband_father_name: '',
        husband_mother_name: '',
        husband_tribe: '',
        husband_clan: '',
        husband_birth_place: '',
        husband_domicile: '',
        husband_baptized_at: '',
        husband_baptism_date: '',
        husband_widower_of: '',
        husband_parent_consent: false,
        wife_name: '',
        wife_father_name: '',
        wife_mother_name: '',
        wife_tribe: '',
        wife_clan: '',
        wife_birth_place: '',
        wife_domicile: '',
        wife_baptized_at: '',
        wife_baptism_date: '',
        wife_widow_of: '',
        wife_parent_consent: false,
        banas_number: '',
        banas_church_1: '',
        banas_date_1: '',
        banas_church_2: '',
        banas_date_2: '',
        dispensation_from: '',
        dispensation_given_by: '',
        dispensation_impediment: '',
        dispensation_date: '',
        marriage_church: '',
        district: '',
        province: '',
        presence_of: '',
        delegated_by: '',
        delegation_date: '',
        male_witness_name: '',
        male_witness_father: '',
        male_witness_clan: '',
        female_witness_name: '',
        female_witness_father: '',
        female_witness_clan: '',
        civil_marriage_certificate: '',
        other_documents: '',
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
                
                // Then, make API call for more comprehensive search using axios
                const response = await axios.get(`/api/families/search`, {
                    params: { q: query, limit: 10 },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const apiResults = response.data?.data || response.data || [];
                
                // Merge and deduplicate results
                const mergedResults = [...localResults];
                apiResults.forEach((apiFamily: Family) => {
                    if (!mergedResults.some(local => local.id === apiFamily.id)) {
                        mergedResults.push(apiFamily);
                    }
                });
                
                setFamilySearchResults(mergedResults.slice(0, 10));
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
    const handleInputChange = useCallback((field: keyof MemberFormData, value: string | boolean) => {
        setData((prev: any) => ({
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
    }, [setVisibleFields, setRequiredFields]);

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
    }, [data.church_group, updateFormFieldsByGroup]);
    
    // Show marriage record tab when matrimony status is 'married'
    useEffect(() => {
        if (data.matrimony_status === 'married') {
            // Auto-navigate to marriage record tab with helpful message
            setActiveTab('marriage_record');
            // Could show a toast notification here instead of confirm dialog
        }
    }, [data.matrimony_status]);

    // Enhanced form submission with better error handling
    const handleSubmit = useCallback(async (e: React.FormEvent<HTMLFormElement>): Promise<void> => {
        e.preventDefault();
        
        if (clearErrors) {
            clearErrors();
        }
        
        // Validate required fields before submission
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
        
        post(route('members.store'), {
            onSuccess: async (page: any) => {
                const response = page.props as any;
                const memberId = response.member?.id;
                
                if (memberId) {
                    // If baptism information is filled out, submit baptism record
                    if (data.father_name && data.mother_name && data.baptism_location && data.baptism_date) {
                        try {
                            await axios.post(route('sacramental-records.store-baptism'), {
                                member_id: memberId,
                                father_name: data.father_name,
                                mother_name: data.mother_name,
                                tribe: data.tribe,
                                birth_village: data.birth_village,
                                county: data.county,
                                birth_date: data.date_of_birth,
                                residence: data.residence,
                                baptism_location: data.baptism_location,
                                baptism_date: data.baptism_date,
                                baptized_by: data.baptized_by,
                                sponsor: data.sponsor,
                                eucharist_location: data.eucharist_location,
                                eucharist_date: data.eucharist_date,
                                confirmation_location: data.confirmation_location,
                                confirmation_date: data.confirmation_date,
                                confirmation_number: data.confirmation_no,
                                confirmation_register_number: data.confirmation_reg_no,
                                marriage_spouse: data.marriage_spouse,
                                marriage_location: data.marriage_location,
                                marriage_date: data.marriage_date,
                                marriage_register_number: data.marriage_reg_no,
                                marriage_number: data.marriage_no,
                            }, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                        } catch (error) {
                            console.error('Error saving baptism record:', error);
                            // Could show a toast notification here instead of console.error
                        }
                    }
                    
                    // If matrimony status is married, submit marriage record
                    if (data.matrimony_status === 'married' && data.husband_name && data.wife_name && data.marriage_church && data.marriage_date) {
                        try {
                            await axios.post(route('sacramental-records.store-marriage'), {
                                husband_id: data.gender === 'Male' ? memberId : null,
                                wife_id: data.gender === 'Female' ? memberId : null,
                                record_number: data.record_number,
                                husband_name: data.husband_name,
                                husband_father_name: data.husband_father_name,
                                husband_mother_name: data.husband_mother_name,
                                husband_tribe: data.husband_tribe,
                                husband_clan: data.husband_clan,
                                husband_birth_place: data.husband_birth_place,
                                husband_domicile: data.husband_domicile,
                                husband_baptized_at: data.husband_baptized_at,
                                husband_baptism_date: data.husband_baptism_date,
                                husband_widower_of: data.husband_widower_of,
                                husband_parent_consent: data.husband_parent_consent,
                                wife_name: data.wife_name,
                                wife_father_name: data.wife_father_name,
                                wife_mother_name: data.wife_mother_name,
                                wife_tribe: data.wife_tribe,
                                wife_clan: data.wife_clan,
                                wife_birth_place: data.wife_birth_place,
                                wife_domicile: data.wife_domicile,
                                wife_baptized_at: data.wife_baptized_at,
                                wife_baptism_date: data.wife_baptism_date,
                                wife_widow_of: data.wife_widow_of,
                                wife_parent_consent: data.wife_parent_consent,
                                banas_number: data.banas_number,
                                banas_church_1: data.banas_church_1,
                                banas_date_1: data.banas_date_1,
                                banas_church_2: data.banas_church_2,
                                banas_date_2: data.banas_date_2,
                                dispensation_from: data.dispensation_from,
                                dispensation_given_by: data.dispensation_given_by,
                                dispensation_impediment: data.dispensation_impediment,
                                dispensation_date: data.dispensation_date,
                                marriage_date: data.marriage_date,
                                marriage_church: data.marriage_church,
                                district: data.district,
                                province: data.province,
                                presence_of: data.presence_of,
                                delegated_by: data.delegated_by,
                                delegation_date: data.delegation_date,
                                male_witness_name: data.male_witness_name,
                                male_witness_father: data.male_witness_father,
                                male_witness_clan: data.male_witness_clan,
                                female_witness_name: data.female_witness_name,
                                female_witness_father: data.female_witness_father,
                                female_witness_clan: data.female_witness_clan,
                                civil_marriage_certificate_number: data.civil_marriage_certificate,
                                other_documents: data.other_documents,
                            }, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                        } catch (error) {
                            console.error('Error saving marriage record:', error);
                            // Could show a toast notification here instead of console.error
                        }
                    }
                
                    // Navigate to members list with success message
                    router.visit(route('members.index'), {
                        onFinish: () => {
                            // This will be handled by the backend flash message
                        }
                    });
                }
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
                    } else if (['family_id', 'parent', 'minister', 'sponsor', 'tribe', 'clan', 'baptism_date', 'confirmation_date', 
                              'matrimony_status', 'father_name', 'mother_name', 'birth_village', 'county', 'baptism_location',
                              'baptized_by', 'eucharist_location', 'eucharist_date', 'confirmation_location', 
                              'confirmation_no', 'confirmation_reg_no'].includes(firstErrorField)) {
                        setActiveTab('church_details');
                    } else if (['phone', 'email', 'residence', 'emergency_contact', 'emergency_phone', 'notes'].includes(firstErrorField)) {
                        setActiveTab('contact');
                    } else if (['record_number', 'husband_name', 'wife_name', 'husband_father_name', 'wife_father_name',
                               'marriage_date', 'marriage_church'].includes(firstErrorField)) {
                        setActiveTab('marriage_record');
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
        { id: 'marriage_record', name: 'Marriage Record', icon: Church },
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
                        aria-expanded={showFamilyDropdown}
                        aria-haspopup="listbox"
                        role="combobox"
                    />
                    <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                        {isSearchingFamilies ? (
                            <Loader2 className="w-4 h-4 animate-spin text-blue-500" />
                        ) : selectedFamily ? (
                            <button
                                type="button"
                                onClick={clearFamilySelection}
                                className="text-gray-400 hover:text-gray-600 transition-colors"
                                aria-label="Clear family selection"
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
                                aria-label="Remove family selection"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                )}

                {/* Search Results Dropdown */}
                {showFamilyDropdown && (
                    <div 
                        className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        role="listbox"
                        aria-label="Family search results"
                    >
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
                                    role="option"
                                    aria-selected={selectedFamily?.id === family.id}
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
                    <p className="mt-1 text-sm text-red-600" role="alert">{getErrorMessage('family_id')}</p>
                )}
                {!selectedFamily && familySearchQuery.length === 0 && (
                    <p className="mt-1 text-sm text-gray-500">
                        Start typing to search for a family, or leave empty if not applicable
                    </p>
                )}
            </div>
        );
    }, [
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
                        </div>

                        {/* Baptism Record Information */}
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Baptism Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="father_name"
                                label="Father's Name"
                                maxLength={255}
                                value={data.father_name}
                                onChange={(value) => handleInputChange('father_name', value)}
                                placeholder="Enter father's full name"
                            />
                            
                            <FormInput
                                id="mother_name"
                                label="Mother's Name"
                                maxLength={255}
                                value={data.mother_name}
                                onChange={(value) => handleInputChange('mother_name', value)}
                                placeholder="Enter mother's full name"
                            />
                            
                            <FormInput
                                id="birth_village"
                                label="Born In (Village)"
                                maxLength={255}
                                value={data.birth_village}
                                onChange={(value) => handleInputChange('birth_village', value)}
                                placeholder="Enter birth village"
                            />
                            
                            <FormInput
                                id="county"
                                label="County"
                                maxLength={255}
                                value={data.county}
                                onChange={(value) => handleInputChange('county', value)}
                                placeholder="Enter county"
                            />

                            <FormInput
                                id="baptism_location"
                                label="Baptism At"
                                maxLength={255}
                                value={data.baptism_location}
                                onChange={(value) => handleInputChange('baptism_location', value)}
                                placeholder="Location of baptism"
                            />
                            
                            <FormInput
                                id="baptism_date"
                                label="Baptism Date"
                                type="date"
                                value={data.baptism_date}
                                onChange={(value) => handleInputChange('baptism_date', value)}
                            />
                            
                            <FormInput
                                id="baptized_by"
                                label="Baptized By"
                                maxLength={255}
                                value={data.baptized_by}
                                onChange={(value) => handleInputChange('baptized_by', value)}
                                placeholder="Name of officiant"
                            />
                            
                            <FormInput
                                id="sponsor"
                                label="Sponsor"
                                maxLength={255}
                                value={data.sponsor}
                                onChange={(value) => handleInputChange('sponsor', value)}
                                placeholder="Name of sponsor"
                            />
                        </div>
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Eucharist & Confirmation Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="eucharist_location"
                                label="Eucharist At"
                                maxLength={255}
                                value={data.eucharist_location}
                                onChange={(value) => handleInputChange('eucharist_location', value)}
                                placeholder="Location of first communion"
                            />
                            
                            <FormInput
                                id="eucharist_date"
                                label="Eucharist Date"
                                type="date"
                                value={data.eucharist_date}
                                onChange={(value) => handleInputChange('eucharist_date', value)}
                            />

                            <FormInput
                                id="confirmation_location"
                                label="Confirmation At"
                                maxLength={255}
                                value={data.confirmation_location}
                                onChange={(value) => handleInputChange('confirmation_location', value)}
                                placeholder="Location of confirmation"
                            />
                            
                            <FormInput
                                id="confirmation_date"
                                label="Confirmation Date"
                                type="date"
                                value={data.confirmation_date}
                                onChange={(value) => handleInputChange('confirmation_date', value)}
                            />
                            
                            <FormInput
                                id="confirmation_reg_no"
                                label="Register No."
                                maxLength={50}
                                value={data.confirmation_reg_no}
                                onChange={(value) => handleInputChange('confirmation_reg_no', value)}
                                placeholder="Register number"
                            />
                            
                            <FormInput
                                id="confirmation_no"
                                label="Confirmation No."
                                maxLength={50}
                                value={data.confirmation_no}
                                onChange={(value) => handleInputChange('confirmation_no', value)}
                                placeholder="Confirmation certificate number"
                            />
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
                


            case 'marriage_record':
                // Only show if matrimony status is 'married'
                if (data.matrimony_status !== 'married') {
                    return (
                        <div className="flex flex-col items-center justify-center py-12">
                            <div className="text-center">
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Marriage Record</h3>
                                <p className="text-gray-600">
                                    To add marriage record details, first select "Married" as the matrimony status in the Church Details tab.
                                </p>
                            </div>
                        </div>
                    );
                }
                
                return (
                    <div className="space-y-6">
                        <div className="bg-blue-50 p-4 rounded-lg mb-4">
                            <h3 className="font-medium text-blue-800">Church Marriage Record</h3>
                            <p className="text-sm text-blue-600">This information will be used for official church marriage records.</p>
                        </div>
                        
                        <FormInput
                            id="record_number"
                            label="Record Number"
                            maxLength={50}
                            value={data.record_number}
                            onChange={(value) => handleInputChange('record_number', value)}
                            placeholder="Official marriage record number"
                        />
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Husband's Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="husband_name"
                                label="Husband's Name"
                                required
                                maxLength={255}
                                value={data.husband_name}
                                onChange={(value) => handleInputChange('husband_name', value)}
                                placeholder="Enter husband's full name"
                                hasError={hasError('husband_name')}
                                errorMessage={getErrorMessage('husband_name')}
                            />
                            
                            <FormInput
                                id="husband_father_name"
                                label="Son of (Father's Name)"
                                required
                                maxLength={255}
                                value={data.husband_father_name}
                                onChange={(value) => handleInputChange('husband_father_name', value)}
                                placeholder="Enter husband's father's name"
                                hasError={hasError('husband_father_name')}
                                errorMessage={getErrorMessage('husband_father_name')}
                            />
                            
                            <FormInput
                                id="husband_mother_name"
                                label="Mother's Name"
                                required
                                maxLength={255}
                                value={data.husband_mother_name}
                                onChange={(value) => handleInputChange('husband_mother_name', value)}
                                placeholder="Enter husband's mother's name"
                                hasError={hasError('husband_mother_name')}
                                errorMessage={getErrorMessage('husband_mother_name')}
                            />
                            
                            <FormInput
                                id="husband_tribe"
                                label="Tribe"
                                maxLength={255}
                                value={data.husband_tribe}
                                onChange={(value) => handleInputChange('husband_tribe', value)}
                                placeholder="Enter husband's tribe"
                            />
                            
                            <FormInput
                                id="husband_clan"
                                label="Clan"
                                maxLength={255}
                                value={data.husband_clan}
                                onChange={(value) => handleInputChange('husband_clan', value)}
                                placeholder="Enter husband's clan"
                            />
                            
                            <FormInput
                                id="husband_birth_place"
                                label="Born In"
                                maxLength={255}
                                value={data.husband_birth_place}
                                onChange={(value) => handleInputChange('husband_birth_place', value)}
                                placeholder="Enter husband's birth place"
                            />
                            
                            <FormInput
                                id="husband_domicile"
                                label="Domicile"
                                maxLength={255}
                                value={data.husband_domicile}
                                onChange={(value) => handleInputChange('husband_domicile', value)}
                                placeholder="Enter husband's current residence"
                            />
                            
                            <FormInput
                                id="husband_baptized_at"
                                label="Baptized At"
                                maxLength={255}
                                value={data.husband_baptized_at}
                                onChange={(value) => handleInputChange('husband_baptized_at', value)}
                                placeholder="Location of baptism"
                            />
                            
                            <FormInput
                                id="husband_baptism_date"
                                label="Baptism Date"
                                type="date"
                                value={data.husband_baptism_date}
                                onChange={(value) => handleInputChange('husband_baptism_date', value)}
                            />
                            
                            <FormInput
                                id="husband_widower_of"
                                label="Widower of"
                                maxLength={255}
                                value={data.husband_widower_of}
                                onChange={(value) => handleInputChange('husband_widower_of', value)}
                                placeholder="If applicable"
                            />
                            
                            <div className="flex items-center space-x-4 py-2">
                                <label htmlFor="husband_parent_consent" className="text-sm font-medium text-gray-700">
                                    Parent Consent Obtained
                                </label>
                                <input
                                    type="checkbox"
                                    id="husband_parent_consent"
                                    checked={data.husband_parent_consent}
                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleInputChange('husband_parent_consent', e.target.checked)}
                                    className="rounded text-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Wife's Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="wife_name"
                                label="Wife's Name"
                                required
                                maxLength={255}
                                value={data.wife_name}
                                onChange={(value) => handleInputChange('wife_name', value)}
                                placeholder="Enter wife's full name"
                            />
                            
                            <FormInput
                                id="wife_father_name"
                                label="Daughter of (Father's Name)"
                                required
                                maxLength={255}
                                value={data.wife_father_name}
                                onChange={(value) => handleInputChange('wife_father_name', value)}
                                placeholder="Enter wife's father's name"
                            />
                            
                            <FormInput
                                id="wife_mother_name"
                                label="Mother's Name"
                                required
                                maxLength={255}
                                value={data.wife_mother_name}
                                onChange={(value) => handleInputChange('wife_mother_name', value)}
                                placeholder="Enter wife's mother's name"
                            />
                            
                            <FormInput
                                id="wife_tribe"
                                label="Tribe"
                                maxLength={255}
                                value={data.wife_tribe}
                                onChange={(value) => handleInputChange('wife_tribe', value)}
                                placeholder="Enter wife's tribe"
                            />
                            
                            <FormInput
                                id="wife_clan"
                                label="Clan"
                                maxLength={255}
                                value={data.wife_clan}
                                onChange={(value) => handleInputChange('wife_clan', value)}
                                placeholder="Enter wife's clan"
                            />
                            
                            <FormInput
                                id="wife_birth_place"
                                label="Born In"
                                maxLength={255}
                                value={data.wife_birth_place}
                                onChange={(value) => handleInputChange('wife_birth_place', value)}
                                placeholder="Enter wife's birth place"
                            />
                            
                            <FormInput
                                id="wife_domicile"
                                label="Domicile"
                                maxLength={255}
                                value={data.wife_domicile}
                                onChange={(value) => handleInputChange('wife_domicile', value)}
                                placeholder="Enter wife's current residence"
                            />
                            
                            <FormInput
                                id="wife_baptized_at"
                                label="Baptized At"
                                maxLength={255}
                                value={data.wife_baptized_at}
                                onChange={(value) => handleInputChange('wife_baptized_at', value)}
                                placeholder="Location of baptism"
                            />
                            
                            <FormInput
                                id="wife_baptism_date"
                                label="Baptism Date"
                                type="date"
                                value={data.wife_baptism_date}
                                onChange={(value) => handleInputChange('wife_baptism_date', value)}
                            />
                            
                            <FormInput
                                id="wife_widow_of"
                                label="Widow of"
                                maxLength={255}
                                value={data.wife_widow_of}
                                onChange={(value) => handleInputChange('wife_widow_of', value)}
                                placeholder="If applicable"
                            />
                            
                            <div className="flex items-center space-x-4 py-2">
                                <label htmlFor="wife_parent_consent" className="text-sm font-medium text-gray-700">
                                    Parent Consent Obtained
                                </label>
                                <input
                                    type="checkbox"
                                    id="wife_parent_consent"
                                    checked={data.wife_parent_consent}
                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleInputChange('wife_parent_consent', e.target.checked)}
                                    className="rounded text-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Banas Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="banas_number"
                                label="Banas Number"
                                maxLength={50}
                                value={data.banas_number}
                                onChange={(value) => handleInputChange('banas_number', value)}
                                placeholder="Enter banas number"
                            />
                            
                            <FormInput
                                id="banas_church_1"
                                label="In the Church of (1)"
                                maxLength={255}
                                value={data.banas_church_1}
                                onChange={(value) => handleInputChange('banas_church_1', value)}
                                placeholder="Enter church name"
                            />
                            
                            <FormInput
                                id="banas_date_1"
                                label="Dates (1)"
                                type="date"
                                value={data.banas_date_1}
                                onChange={(value) => handleInputChange('banas_date_1', value)}
                            />
                            
                            <FormInput
                                id="banas_church_2"
                                label="In the Church of (2)"
                                maxLength={255}
                                value={data.banas_church_2}
                                onChange={(value) => handleInputChange('banas_church_2', value)}
                                placeholder="Enter church name (if applicable)"
                            />
                            
                            <FormInput
                                id="banas_date_2"
                                label="Dates (2)"
                                type="date"
                                value={data.banas_date_2}
                                onChange={(value) => handleInputChange('banas_date_2', value)}
                            />
                        </div>
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Dispensation Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="dispensation_from"
                                label="Dispensation From"
                                maxLength={255}
                                value={data.dispensation_from}
                                onChange={(value) => handleInputChange('dispensation_from', value)}
                                placeholder="Enter dispensation source"
                            />
                            
                            <FormInput
                                id="dispensation_given_by"
                                label="Given By"
                                maxLength={255}
                                value={data.dispensation_given_by}
                                onChange={(value) => handleInputChange('dispensation_given_by', value)}
                                placeholder="Enter name"
                            />
                            
                            <FormInput
                                id="dispensation_impediment"
                                label="Dispensation from the Impediment(s) of"
                                maxLength={255}
                                value={data.dispensation_impediment}
                                onChange={(value) => handleInputChange('dispensation_impediment', value)}
                                placeholder="Enter impediments"
                            />
                            
                            <FormInput
                                id="dispensation_date"
                                label="Dispensation Date"
                                type="date"
                                value={data.dispensation_date}
                                onChange={(value) => handleInputChange('dispensation_date', value)}
                            />
                        </div>
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Marriage Ceremony Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="marriage_date"
                                label="Marriage Date"
                                type="date"
                                required
                                value={data.marriage_date}
                                onChange={(value) => handleInputChange('marriage_date', value)}
                            />
                            
                            <FormInput
                                id="marriage_church"
                                label="In the Church of"
                                required
                                maxLength={255}
                                value={data.marriage_church}
                                onChange={(value) => handleInputChange('marriage_church', value)}
                                placeholder="Enter church name"
                            />
                            
                            <FormInput
                                id="district"
                                label="District of"
                                maxLength={255}
                                value={data.district}
                                onChange={(value) => handleInputChange('district', value)}
                                placeholder="Enter district"
                            />
                            
                            <FormInput
                                id="province"
                                label="Province of"
                                maxLength={255}
                                value={data.province}
                                onChange={(value) => handleInputChange('province', value)}
                                placeholder="Enter province"
                            />
                            
                            <FormInput
                                id="presence_of"
                                label="In the Presence of"
                                maxLength={255}
                                value={data.presence_of}
                                onChange={(value) => handleInputChange('presence_of', value)}
                                placeholder="Enter officiating clergy name"
                            />
                            
                            <FormInput
                                id="delegated_by"
                                label="Delegated By"
                                maxLength={255}
                                value={data.delegated_by}
                                onChange={(value) => handleInputChange('delegated_by', value)}
                                placeholder="Enter name if applicable"
                            />
                            
                            <FormInput
                                id="delegation_date"
                                label="Delegation Date"
                                type="date"
                                value={data.delegation_date}
                                onChange={(value) => handleInputChange('delegation_date', value)}
                            />
                        </div>
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Witness Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="male_witness_name"
                                label="Male Witness Full Name"
                                required
                                maxLength={255}
                                value={data.male_witness_name}
                                onChange={(value) => handleInputChange('male_witness_name', value)}
                                placeholder="Enter name"
                            />
                            
                            <FormInput
                                id="male_witness_father"
                                label="Son of"
                                maxLength={255}
                                value={data.male_witness_father}
                                onChange={(value) => handleInputChange('male_witness_father', value)}
                                placeholder="Enter father's name"
                            />
                            
                            <FormInput
                                id="male_witness_clan"
                                label="Clan"
                                maxLength={255}
                                value={data.male_witness_clan}
                                onChange={(value) => handleInputChange('male_witness_clan', value)}
                                placeholder="Enter clan"
                            />
                            
                            <FormInput
                                id="female_witness_name"
                                label="Female Witness Full Name"
                                required
                                maxLength={255}
                                value={data.female_witness_name}
                                onChange={(value) => handleInputChange('female_witness_name', value)}
                                placeholder="Enter name"
                            />
                            
                            <FormInput
                                id="female_witness_father"
                                label="Daughter of"
                                maxLength={255}
                                value={data.female_witness_father}
                                onChange={(value) => handleInputChange('female_witness_father', value)}
                                placeholder="Enter father's name"
                            />
                            
                            <FormInput
                                id="female_witness_clan"
                                label="Clan"
                                maxLength={255}
                                value={data.female_witness_clan}
                                onChange={(value) => handleInputChange('female_witness_clan', value)}
                                placeholder="Enter clan"
                            />
                        </div>
                        
                        <h3 className="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mt-8">Additional Information</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <FormInput
                                id="civil_marriage_certificate"
                                label="Civil Marriage Certificate Number"
                                maxLength={100}
                                value={data.civil_marriage_certificate}
                                onChange={(value) => handleInputChange('civil_marriage_certificate', value)}
                                placeholder="Enter certificate number"
                            />
                            
                            <FormInput
                                id="other_documents"
                                label="Other Documents"
                                maxLength={255}
                                value={data.other_documents}
                                onChange={(value) => handleInputChange('other_documents', value)}
                                placeholder="List any other documents"
                            />
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