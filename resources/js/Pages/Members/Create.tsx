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

// Form data interface with all enhanced fields including comprehensive church records
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
    
    // Comprehensive Baptism Record Fields (shown when baptism_date is filled)
    birth_village: string;
    county: string;
    baptism_location: string;
    baptized_by: string;
    sponsor: string;
    father_name: string;
    mother_name: string;
    
    // Optional Sacrament Fields
    eucharist_location: string;
    eucharist_date: string;
    confirmation_location: string;
    confirmation_register_number: string;
    confirmation_number: string;
    
    // Marriage Information (shown when matrimony_status is 'married')
    marriage_spouse: string;
    marriage_location: string;
    marriage_date: string;
    marriage_register_number: string;
    marriage_number: string;
    
    // Comprehensive Church Marriage Record Fields (shown when marriage_type is 'church')
    spouse_name: string;
    spouse_father_name: string;
    spouse_mother_name: string;
    spouse_tribe: string;
    spouse_clan: string;
    spouse_birth_place: string;
    spouse_domicile: string;
    spouse_baptized_at: string;
    spouse_baptism_date: string;
    spouse_widower_widow_of: string;
    spouse_parent_consent: string;
    
    // Banas Information
    banas_number: string;
    banas_church_1: string;
    banas_date_1: string;
    banas_church_2: string;
    banas_date_2: string;
    dispensation_from: string;
    dispensation_given_by: string;
    
    // Dispensation Information
    dispensation_impediment: string;
    dispensation_authority: string;
    dispensation_date: string;
    
    // Marriage Contract Details
    marriage_church: string;
    district: string;
    province: string;
    presence_of: string;
    delegated_by: string;
    delegation_date: string;
    
    // Witness Information
    male_witness_full_name: string;
    male_witness_father: string;
    male_witness_clan: string;
    female_witness_full_name: string;
    female_witness_father: string;
    female_witness_clan: string;
    
    // Additional Documents
    other_documents: string;
    civil_marriage_certificate_number: string;
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
    
    const { data, setData, post, processing, errors = {}, clearErrors } = useForm<MemberFormData>({
        local_church: 'Sacred Heart Kandara',
        small_christian_community: '',
        church_group: 'Catholic Action',
        additional_church_groups: [],
        first_name: '',
        middle_name: '',
        last_name: '',
        date_of_birth: '',
        gender: '',
        phone: '',
        email: '',
        id_number: '',
        godparent: '',
        occupation: 'not_employed',
        education_level: 'none',
        family_id: '',
        parent: '',
        minister: '',
        tribe: '',
        clan: '',
        baptism_date: '',
        residence: '',
        confirmation_date: '',
        matrimony_status: 'single',
        marriage_type: '',
        membership_date: new Date().toISOString().split('T')[0],
        membership_status: 'active',
        emergency_contact: '',
        emergency_phone: '',
        notes: '',
        
        // Comprehensive Baptism Record Fields
        birth_village: '',
        county: '',
        baptism_location: '',
        baptized_by: '',
        sponsor: '',
        father_name: '',
        mother_name: '',
        
        // Optional Sacrament Fields
        eucharist_location: '',
        eucharist_date: '',
        confirmation_location: '',
        confirmation_register_number: '',
        confirmation_number: '',
        
        // Marriage Information
        marriage_spouse: '',
        marriage_location: '',
        marriage_date: '',
        marriage_register_number: '',
        marriage_number: '',
        
        // Comprehensive Church Marriage Record Fields
        spouse_name: '',
        spouse_father_name: '',
        spouse_mother_name: '',
        spouse_tribe: '',
        spouse_clan: '',
        spouse_birth_place: '',
        spouse_domicile: '',
        spouse_baptized_at: '',
        spouse_baptism_date: '',
        spouse_widower_widow_of: '',
        spouse_parent_consent: '',
        
        // Banas Information
        banas_number: '',
        banas_church_1: '',
        banas_date_1: '',
        banas_church_2: '',
        banas_date_2: '',
        dispensation_from: '',
        dispensation_given_by: '',
        
        // Dispensation Information
        dispensation_impediment: '',
        dispensation_authority: '',
        dispensation_date: '',
        
        // Marriage Contract Details
        marriage_church: '',
        district: '',
        province: '',
        presence_of: '',
        delegated_by: '',
        delegation_date: '',
        
        // Witness Information
        male_witness_full_name: '',
        male_witness_father: '',
        male_witness_clan: '',
        female_witness_full_name: '',
        female_witness_father: '',
        female_witness_clan: '',
        
        // Additional Documents
        other_documents: '',
        civil_marriage_certificate_number: '',
    });

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
            'first_name', 'last_name', 'gender'
        ];

        let visible = [...baseFields];
        let required = [...baseRequired];

        switch (churchGroup) {
            case 'PMC':
                visible.push('parent', 'family_id', 'date_of_birth', 'education_level');
                // Don't make parent required - it's optional
                break;
                
            case 'C.W.A':
                visible.push('occupation', 'education_level', 'godparent', 
                           'matrimony_status', 'marriage_type', 'minister', 'tribe', 'clan', 'family_id');
                // Don't require matrimony_status - make it optional
                break;
                
            case 'CMA':
                visible.push('occupation', 'education_level', 'godparent', 
                           'matrimony_status', 'marriage_type', 'minister', 'tribe', 'clan', 'family_id');
                // Don't require matrimony_status - make it optional
                break;
                
            case 'Youth':
                visible.push('education_level', 'parent', 'family_id', 'godparent', 'tribe', 'clan', 'date_of_birth');
                // Don't require education_level - make it optional
                break;
                
            case 'Choir':
                visible.push('occupation', 'education_level', 'godparent', 'family_id', 'tribe', 'clan', 'phone');
                // Don't require phone - make it optional
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

    // Auto-inherit family data for children and auto-populate parent names
    const inheritFamilyData = useCallback(async () => {
        if (selectedFamily && data.family_id) {
            try {
                const response = await axios.get(`/api/families/${data.family_id}/head`);
                const familyHead = response.data;
                
                if (familyHead && data.date_of_birth) {
                    const age = new Date().getFullYear() - new Date(data.date_of_birth).getFullYear();
                    
                    if (age < 18) {
                        if (!data.tribe && familyHead.tribe) {
                            setData('tribe', familyHead.tribe);
                        }
                        
                        if (!data.clan && familyHead.clan) {
                            setData('clan', familyHead.clan);
                        }
                        
                        if (!data.small_christian_community && familyHead.small_christian_community) {
                            setData('small_christian_community', familyHead.small_christian_community);
                            setCommunitySearchQuery(familyHead.small_christian_community);
                        }
                    }
                    
                    // Auto-populate father/mother names for baptism record if family head is available
                    if (familyHead.gender === 'Male' && !data.father_name) {
                        setData('father_name', `${familyHead.first_name} ${familyHead.middle_name || ''} ${familyHead.last_name}`.trim());
                    }
                    
                    // Try to get spouse information for mother's name
                    try {
                        const familyMembersResponse = await axios.get(`/api/families/${data.family_id}/members`);
                        const familyMembers = familyMembersResponse.data;
                        
                        const spouse = familyMembers.find((member: any) => 
                            member.id !== familyHead.id && 
                            member.matrimony_status === 'married' &&
                            member.gender !== familyHead.gender
                        );
                        
                        if (spouse) {
                            if (spouse.gender === 'Female' && !data.mother_name) {
                                setData('mother_name', `${spouse.first_name} ${spouse.middle_name || ''} ${spouse.last_name}`.trim());
                            } else if (spouse.gender === 'Male' && !data.father_name) {
                                setData('father_name', `${spouse.first_name} ${spouse.middle_name || ''} ${spouse.last_name}`.trim());
                            }
                        }
                    } catch (spouseError) {
                        console.warn('Could not fetch family members for spouse info:', spouseError);
                    }
                }
            } catch (error) {
                console.warn('Could not inherit family data:', error);
            }
        }
    }, [selectedFamily, data.family_id, data.date_of_birth, data.tribe, data.clan, data.small_christian_community, data.father_name, data.mother_name, setData]);

    // Initialize
    useEffect(() => {
        setFamilySearchResults(families.slice(0, 10));
    }, [families]);

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
        
        // Validate only truly essential fields - don't block on optional group-specific fields
        const essentialFields = ['first_name', 'last_name', 'gender'];
        const missingEssentialFields = essentialFields.filter((field: string) => {
            const value = data[field as keyof MemberFormData];
            return typeof value === 'string' ? !value.trim() : !value;
        });
        
        if (missingEssentialFields.length > 0) {
            const firstMissingField = missingEssentialFields[0];
            const element = document.getElementById(firstMissingField);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.focus();
            }
            return;
        }
        
        // Log warnings for missing preferred fields but don't block submission
        const missingPreferredFields = requiredFields.filter((field: string) => {
            const value = data[field as keyof MemberFormData];
            return typeof value === 'string' ? !value.trim() : !value;
        });
        
        if (missingPreferredFields.length > 0) {
            console.warn('Missing preferred fields for ' + data.church_group + ':', missingPreferredFields);
        }
        
        post(route('members.store'), {
            onSuccess: () => {
                router.visit(route('members.index'));
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
    }, [clearErrors, requiredFields, data, post]);

    // Calculate progress percentage
    const progressPercentage = useMemo(() => {
        const totalRequiredFields = requiredFields.length;
        const completedFields = requiredFields.filter((field: string) => {
            const value = data[field as keyof MemberFormData];
            return typeof value === 'string' ? value.trim() !== '' : Boolean(value);
        }).length;
        
        return totalRequiredFields > 0 ? Math.round((completedFields / totalRequiredFields) * 100) : 0;
    }, [requiredFields, data]);

    // Tabs configuration with dynamic baptism and marriage sections
    const tabs = useMemo(() => {
        const baseTabs = [
            { id: 'church', name: 'Church Membership', icon: Church },
            { id: 'personal', name: 'Personal Info', icon: Users },
            { id: 'church_details', name: 'Church Details', icon: Info },
        ];
        
        // Add baptism details tab if baptism date is provided
        if (data.baptism_date) {
            baseTabs.push({ id: 'baptism_details', name: 'Baptism Record', icon: AlertCircle });
        }
        
        // Add marriage details tab if married and church marriage
        if (data.matrimony_status === 'married' && data.marriage_type === 'church') {
            baseTabs.push({ id: 'marriage_details', name: 'Marriage Record', icon: AlertCircle });
        }
        
        baseTabs.push({ id: 'contact', name: 'Contact Info', icon: AlertCircle });
        
        return baseTabs;
    }, [data.baptism_date, data.matrimony_status, data.marriage_type]);

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
                <p className="mt-1 text-sm text-gray-500">
                    This will help speed up data entry for future registrations
                </p>
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
                                onChange={(value) => {
                                    handleInputChange('date_of_birth', value);
                                    setTimeout(inheritFamilyData, 100);
                                }}
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
                                        console.warn(genderError);
                                        // Allow selection but show warning
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
                                <div>
                                    <FormInput
                                        id="baptism_date"
                                        label="Baptism Date"
                                        type="date"
                                        value={data.baptism_date}
                                        onChange={(value) => {
                                            handleInputChange('baptism_date', value);
                                            // Auto-populate baptism location with local church if not set
                                            if (value && !data.baptism_location && data.local_church) {
                                                setData('baptism_location', data.local_church);
                                            }
                                        }}
                                        hasError={hasError('baptism_date')}
                                        errorMessage={getErrorMessage('baptism_date')}
                                    />
                                    {data.baptism_date && (
                                        <p className="mt-1 text-sm text-blue-600">
                                            💡 Complete the baptism record details in the "Baptism Record" tab
                                        </p>
                                    )}
                                </div>
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

                            {/* Matrimony Status at the end as requested */}
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
                                        { value: 'divorced', label: 'Divorced' },
                                        { value: 'widowed', label: 'Widowed' },
                                        { value: 'separated', label: 'Separated' }
                                    ]}
                                    hasError={hasError('matrimony_status')}
                                    errorMessage={getErrorMessage('matrimony_status')}
                                />
                            )}

                            {data.matrimony_status === 'married' && (
                                <div>
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
                                    {data.marriage_type === 'church' && (
                                        <p className="mt-1 text-sm text-blue-600">
                                            💡 Complete the church marriage record details in the "Marriage Record" tab
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                );

            case 'baptism_details':
                return (
                    <div className="space-y-6">
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h3 className="text-lg font-semibold text-blue-900 mb-2">Baptism Card Information</h3>
                            <p className="text-sm text-blue-700">
                                Complete the comprehensive baptism record as required for the baptism card.
                                Fields marked with * are required for the official church records.
                            </p>
                        </div>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Parent Information - Required for baptism card */}
                            <FormInput
                                id="father_name"
                                label="Father's Name"
                                required
                                maxLength={255}
                                value={data.father_name}
                                onChange={(value) => handleInputChange('father_name', value)}
                                placeholder="Full name of father"
                                hasError={hasError('father_name')}
                                errorMessage={getErrorMessage('father_name')}
                            />

                            <FormInput
                                id="mother_name"
                                label="Mother's Name"
                                required
                                maxLength={255}
                                value={data.mother_name}
                                onChange={(value) => handleInputChange('mother_name', value)}
                                placeholder="Full name of mother"
                                hasError={hasError('mother_name')}
                                errorMessage={getErrorMessage('mother_name')}
                            />

                            {/* Birth Information - Required for baptism card */}
                            <FormInput
                                id="birth_village"
                                label="Birth Village"
                                required
                                maxLength={255}
                                value={data.birth_village}
                                onChange={(value) => handleInputChange('birth_village', value)}
                                placeholder="Village where born"
                                hasError={hasError('birth_village')}
                                errorMessage={getErrorMessage('birth_village')}
                            />

                            <FormInput
                                id="county"
                                label="County"
                                required
                                maxLength={255}
                                value={data.county}
                                onChange={(value) => handleInputChange('county', value)}
                                placeholder="County of birth"
                                hasError={hasError('county')}
                                errorMessage={getErrorMessage('county')}
                            />

                            {/* Baptism Details - Required */}
                            <FormInput
                                id="baptism_location"
                                label="Baptism Location"
                                required
                                maxLength={255}
                                value={data.baptism_location}
                                onChange={(value) => handleInputChange('baptism_location', value)}
                                placeholder="Church where baptized"
                                hasError={hasError('baptism_location')}
                                errorMessage={getErrorMessage('baptism_location')}
                            />

                            <FormInput
                                id="baptized_by"
                                label="Baptized By"
                                required
                                maxLength={255}
                                value={data.baptized_by}
                                onChange={(value) => handleInputChange('baptized_by', value)}
                                placeholder="Name of minister who baptized"
                                hasError={hasError('baptized_by')}
                                errorMessage={getErrorMessage('baptized_by')}
                            />

                            <FormInput
                                id="sponsor"
                                label="Sponsor (Godparent)"
                                required
                                maxLength={255}
                                value={data.sponsor}
                                onChange={(value) => handleInputChange('sponsor', value)}
                                placeholder="Baptism sponsor/godparent"
                                hasError={hasError('sponsor')}
                                errorMessage={getErrorMessage('sponsor')}
                            />

                            {/* Optional Sacrament Information */}
                            <div className="md:col-span-2">
                                <h4 className="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">
                                    Other Sacraments (Optional)
                                </h4>
                            </div>

                            {/* Eucharist Information */}
                            <FormInput
                                id="eucharist_location"
                                label="First Eucharist Location"
                                maxLength={255}
                                value={data.eucharist_location}
                                onChange={(value) => handleInputChange('eucharist_location', value)}
                                placeholder="Church where first communion received"
                                hasError={hasError('eucharist_location')}
                                errorMessage={getErrorMessage('eucharist_location')}
                            />

                            <FormInput
                                id="eucharist_date"
                                label="First Eucharist Date"
                                type="date"
                                value={data.eucharist_date}
                                onChange={(value) => handleInputChange('eucharist_date', value)}
                                hasError={hasError('eucharist_date')}
                                errorMessage={getErrorMessage('eucharist_date')}
                            />

                            {/* Confirmation Information */}
                            <FormInput
                                id="confirmation_location"
                                label="Confirmation Location"
                                maxLength={255}
                                value={data.confirmation_location}
                                onChange={(value) => handleInputChange('confirmation_location', value)}
                                placeholder="Church where confirmed"
                                hasError={hasError('confirmation_location')}
                                errorMessage={getErrorMessage('confirmation_location')}
                            />

                            <div className="grid grid-cols-2 gap-4">
                                <FormInput
                                    id="confirmation_register_number"
                                    label="Confirmation Register #"
                                    maxLength={50}
                                    value={data.confirmation_register_number}
                                    onChange={(value) => handleInputChange('confirmation_register_number', value)}
                                    placeholder="Register number"
                                    hasError={hasError('confirmation_register_number')}
                                    errorMessage={getErrorMessage('confirmation_register_number')}
                                />

                                <FormInput
                                    id="confirmation_number"
                                    label="Confirmation #"
                                    maxLength={50}
                                    value={data.confirmation_number}
                                    onChange={(value) => handleInputChange('confirmation_number', value)}
                                    placeholder="Certificate number"
                                    hasError={hasError('confirmation_number')}
                                    errorMessage={getErrorMessage('confirmation_number')}
                                />
                            </div>

                            {/* Marriage Information (for baptism card) */}
                            {data.matrimony_status === 'married' && (
                                <>
                                    <FormInput
                                        id="marriage_spouse"
                                        label="Marriage Spouse"
                                        maxLength={255}
                                        value={data.marriage_spouse}
                                        onChange={(value) => handleInputChange('marriage_spouse', value)}
                                        placeholder="Full name of spouse"
                                        hasError={hasError('marriage_spouse')}
                                        errorMessage={getErrorMessage('marriage_spouse')}
                                    />

                                    <FormInput
                                        id="marriage_location"
                                        label="Marriage Location"
                                        maxLength={255}
                                        value={data.marriage_location}
                                        onChange={(value) => handleInputChange('marriage_location', value)}
                                        placeholder="Church where married"
                                        hasError={hasError('marriage_location')}
                                        errorMessage={getErrorMessage('marriage_location')}
                                    />

                                    <FormInput
                                        id="marriage_date"
                                        label="Marriage Date"
                                        type="date"
                                        value={data.marriage_date}
                                        onChange={(value) => handleInputChange('marriage_date', value)}
                                        hasError={hasError('marriage_date')}
                                        errorMessage={getErrorMessage('marriage_date')}
                                    />

                                    <div className="grid grid-cols-2 gap-4">
                                        <FormInput
                                            id="marriage_register_number"
                                            label="Marriage Register #"
                                            maxLength={50}
                                            value={data.marriage_register_number}
                                            onChange={(value) => handleInputChange('marriage_register_number', value)}
                                            placeholder="Register number"
                                            hasError={hasError('marriage_register_number')}
                                            errorMessage={getErrorMessage('marriage_register_number')}
                                        />

                                        <FormInput
                                            id="marriage_number"
                                            label="Marriage Certificate #"
                                            maxLength={50}
                                            value={data.marriage_number}
                                            onChange={(value) => handleInputChange('marriage_number', value)}
                                            placeholder="Certificate number"
                                            hasError={hasError('marriage_number')}
                                            errorMessage={getErrorMessage('marriage_number')}
                                        />
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                );

            case 'marriage_details':
                return (
                    <div className="space-y-6">
                        <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <h3 className="text-lg font-semibold text-green-900 mb-2">Church Marriage Record</h3>
                            <p className="text-sm text-green-700">
                                Complete the comprehensive church marriage record as required for official church documentation.
                                This follows the Catholic Church marriage record format with all necessary details.
                            </p>
                        </div>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Spouse Information */}
                            <div className="md:col-span-2">
                                <h4 className="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">
                                    Spouse Information
                                </h4>
                            </div>

                            <FormInput
                                id="spouse_name"
                                label="Spouse's Full Name"
                                required
                                maxLength={255}
                                value={data.spouse_name}
                                onChange={(value) => handleInputChange('spouse_name', value)}
                                placeholder="Full name of spouse"
                                hasError={hasError('spouse_name')}
                                errorMessage={getErrorMessage('spouse_name')}
                            />

                            <FormInput
                                id="spouse_father_name"
                                label="Spouse's Father's Name"
                                required
                                maxLength={255}
                                value={data.spouse_father_name}
                                onChange={(value) => handleInputChange('spouse_father_name', value)}
                                placeholder="Father's name of spouse"
                                hasError={hasError('spouse_father_name')}
                                errorMessage={getErrorMessage('spouse_father_name')}
                            />

                            <FormInput
                                id="spouse_mother_name"
                                label="Spouse's Mother's Name"
                                required
                                maxLength={255}
                                value={data.spouse_mother_name}
                                onChange={(value) => handleInputChange('spouse_mother_name', value)}
                                placeholder="Mother's name of spouse"
                                hasError={hasError('spouse_mother_name')}
                                errorMessage={getErrorMessage('spouse_mother_name')}
                            />

                            <FormInput
                                id="spouse_tribe"
                                label="Spouse's Tribe"
                                required
                                maxLength={255}
                                value={data.spouse_tribe}
                                onChange={(value) => handleInputChange('spouse_tribe', value)}
                                placeholder="Spouse's tribe"
                                hasError={hasError('spouse_tribe')}
                                errorMessage={getErrorMessage('spouse_tribe')}
                            />

                            <FormInput
                                id="spouse_clan"
                                label="Spouse's Clan"
                                required
                                maxLength={255}
                                value={data.spouse_clan}
                                onChange={(value) => handleInputChange('spouse_clan', value)}
                                placeholder="Spouse's clan"
                                hasError={hasError('spouse_clan')}
                                errorMessage={getErrorMessage('spouse_clan')}
                            />

                            <FormInput
                                id="spouse_birth_place"
                                label="Spouse's Birth Place"
                                required
                                maxLength={255}
                                value={data.spouse_birth_place}
                                onChange={(value) => handleInputChange('spouse_birth_place', value)}
                                placeholder="Place where spouse was born"
                                hasError={hasError('spouse_birth_place')}
                                errorMessage={getErrorMessage('spouse_birth_place')}
                            />

                            <FormInput
                                id="spouse_domicile"
                                label="Spouse's Domicile"
                                required
                                maxLength={255}
                                value={data.spouse_domicile}
                                onChange={(value) => handleInputChange('spouse_domicile', value)}
                                placeholder="Spouse's current residence"
                                hasError={hasError('spouse_domicile')}
                                errorMessage={getErrorMessage('spouse_domicile')}
                            />

                            <FormInput
                                id="spouse_baptized_at"
                                label="Spouse Baptized At"
                                required
                                maxLength={255}
                                value={data.spouse_baptized_at}
                                onChange={(value) => handleInputChange('spouse_baptized_at', value)}
                                placeholder="Church where spouse was baptized"
                                hasError={hasError('spouse_baptized_at')}
                                errorMessage={getErrorMessage('spouse_baptized_at')}
                            />

                            <FormInput
                                id="spouse_baptism_date"
                                label="Spouse's Baptism Date"
                                type="date"
                                required
                                value={data.spouse_baptism_date}
                                onChange={(value) => handleInputChange('spouse_baptism_date', value)}
                                hasError={hasError('spouse_baptism_date')}
                                errorMessage={getErrorMessage('spouse_baptism_date')}
                            />

                            <FormInput
                                id="spouse_widower_widow_of"
                                label="Spouse Widower/Widow Of"
                                maxLength={255}
                                value={data.spouse_widower_widow_of}
                                onChange={(value) => handleInputChange('spouse_widower_widow_of', value)}
                                placeholder="If previously married (optional)"
                                hasError={hasError('spouse_widower_widow_of')}
                                errorMessage={getErrorMessage('spouse_widower_widow_of')}
                            />

                            <FormInput
                                id="spouse_parent_consent"
                                label="Spouse Parent Consent"
                                type="select"
                                required
                                placeholder="Select"
                                value={data.spouse_parent_consent}
                                onChange={(value) => handleInputChange('spouse_parent_consent', value)}
                                options={[
                                    { value: 'Yes', label: 'Yes' },
                                    { value: 'No', label: 'No' }
                                ]}
                                hasError={hasError('spouse_parent_consent')}
                                errorMessage={getErrorMessage('spouse_parent_consent')}
                            />

                            {/* Banas Information */}
                            <div className="md:col-span-2 mt-6">
                                <h4 className="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">
                                    Banas Information
                                </h4>
                            </div>

                            <FormInput
                                id="banas_number"
                                label="Banas Number"
                                required
                                maxLength={255}
                                value={data.banas_number}
                                onChange={(value) => handleInputChange('banas_number', value)}
                                placeholder="Banas proclamation number"
                                hasError={hasError('banas_number')}
                                errorMessage={getErrorMessage('banas_number')}
                            />

                            <FormInput
                                id="banas_church_1"
                                label="First Banas Church"
                                required
                                maxLength={255}
                                value={data.banas_church_1}
                                onChange={(value) => handleInputChange('banas_church_1', value)}
                                placeholder="First church where banas was proclaimed"
                                hasError={hasError('banas_church_1')}
                                errorMessage={getErrorMessage('banas_church_1')}
                            />

                            <FormInput
                                id="banas_date_1"
                                label="First Banas Date"
                                type="date"
                                required
                                value={data.banas_date_1}
                                onChange={(value) => handleInputChange('banas_date_1', value)}
                                hasError={hasError('banas_date_1')}
                                errorMessage={getErrorMessage('banas_date_1')}
                            />

                            <FormInput
                                id="banas_church_2"
                                label="Second Banas Church"
                                maxLength={255}
                                value={data.banas_church_2}
                                onChange={(value) => handleInputChange('banas_church_2', value)}
                                placeholder="Second church (optional)"
                                hasError={hasError('banas_church_2')}
                                errorMessage={getErrorMessage('banas_church_2')}
                            />

                            <FormInput
                                id="banas_date_2"
                                label="Second Banas Date"
                                type="date"
                                value={data.banas_date_2}
                                onChange={(value) => handleInputChange('banas_date_2', value)}
                                hasError={hasError('banas_date_2')}
                                errorMessage={getErrorMessage('banas_date_2')}
                            />

                            {/* Marriage Contract Details */}
                            <div className="md:col-span-2 mt-6">
                                <h4 className="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">
                                    Marriage Contract Details
                                </h4>
                            </div>

                            <FormInput
                                id="marriage_church"
                                label="Marriage Church"
                                required
                                maxLength={255}
                                value={data.marriage_church}
                                onChange={(value) => handleInputChange('marriage_church', value)}
                                placeholder="Church where marriage was conducted"
                                hasError={hasError('marriage_church')}
                                errorMessage={getErrorMessage('marriage_church')}
                            />

                            <FormInput
                                id="district"
                                label="District"
                                required
                                maxLength={255}
                                value={data.district}
                                onChange={(value) => handleInputChange('district', value)}
                                placeholder="District"
                                hasError={hasError('district')}
                                errorMessage={getErrorMessage('district')}
                            />

                            <FormInput
                                id="province"
                                label="Province"
                                required
                                maxLength={255}
                                value={data.province}
                                onChange={(value) => handleInputChange('province', value)}
                                placeholder="Province"
                                hasError={hasError('province')}
                                errorMessage={getErrorMessage('province')}
                            />

                            <FormInput
                                id="presence_of"
                                label="In the Presence Of"
                                required
                                maxLength={255}
                                value={data.presence_of}
                                onChange={(value) => handleInputChange('presence_of', value)}
                                placeholder="Officiant name"
                                hasError={hasError('presence_of')}
                                errorMessage={getErrorMessage('presence_of')}
                            />

                            <FormInput
                                id="delegated_by"
                                label="Delegated By"
                                maxLength={255}
                                value={data.delegated_by}
                                onChange={(value) => handleInputChange('delegated_by', value)}
                                placeholder="Authority who delegated (optional)"
                                hasError={hasError('delegated_by')}
                                errorMessage={getErrorMessage('delegated_by')}
                            />

                            <FormInput
                                id="delegation_date"
                                label="Delegation Date"
                                type="date"
                                value={data.delegation_date}
                                onChange={(value) => handleInputChange('delegation_date', value)}
                                hasError={hasError('delegation_date')}
                                errorMessage={getErrorMessage('delegation_date')}
                            />

                            {/* Witness Information */}
                            <div className="md:col-span-2 mt-6">
                                <h4 className="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">
                                    Witness Information
                                </h4>
                            </div>

                            <FormInput
                                id="male_witness_full_name"
                                label="Male Witness Full Name"
                                required
                                maxLength={255}
                                value={data.male_witness_full_name}
                                onChange={(value) => handleInputChange('male_witness_full_name', value)}
                                placeholder="Full name of male witness"
                                hasError={hasError('male_witness_full_name')}
                                errorMessage={getErrorMessage('male_witness_full_name')}
                            />

                            <FormInput
                                id="male_witness_father"
                                label="Male Witness Father"
                                required
                                maxLength={255}
                                value={data.male_witness_father}
                                onChange={(value) => handleInputChange('male_witness_father', value)}
                                placeholder="Male witness father's name"
                                hasError={hasError('male_witness_father')}
                                errorMessage={getErrorMessage('male_witness_father')}
                            />

                            <FormInput
                                id="male_witness_clan"
                                label="Male Witness Clan"
                                required
                                maxLength={255}
                                value={data.male_witness_clan}
                                onChange={(value) => handleInputChange('male_witness_clan', value)}
                                placeholder="Male witness clan"
                                hasError={hasError('male_witness_clan')}
                                errorMessage={getErrorMessage('male_witness_clan')}
                            />

                            <FormInput
                                id="female_witness_full_name"
                                label="Female Witness Full Name"
                                required
                                maxLength={255}
                                value={data.female_witness_full_name}
                                onChange={(value) => handleInputChange('female_witness_full_name', value)}
                                placeholder="Full name of female witness"
                                hasError={hasError('female_witness_full_name')}
                                errorMessage={getErrorMessage('female_witness_full_name')}
                            />

                            <FormInput
                                id="female_witness_father"
                                label="Female Witness Father"
                                required
                                maxLength={255}
                                value={data.female_witness_father}
                                onChange={(value) => handleInputChange('female_witness_father', value)}
                                placeholder="Female witness father's name"
                                hasError={hasError('female_witness_father')}
                                errorMessage={getErrorMessage('female_witness_father')}
                            />

                            <FormInput
                                id="female_witness_clan"
                                label="Female Witness Clan"
                                required
                                maxLength={255}
                                value={data.female_witness_clan}
                                onChange={(value) => handleInputChange('female_witness_clan', value)}
                                placeholder="Female witness clan"
                                hasError={hasError('female_witness_clan')}
                                errorMessage={getErrorMessage('female_witness_clan')}
                            />

                            {/* Additional Documents */}
                            <div className="md:col-span-2 mt-6">
                                <h4 className="text-md font-medium text-gray-700 mb-4 border-b border-gray-200 pb-2">
                                    Additional Documents
                                </h4>
                            </div>

                            <FormInput
                                id="civil_marriage_certificate_number"
                                label="Civil Marriage Certificate Number"
                                maxLength={255}
                                value={data.civil_marriage_certificate_number}
                                onChange={(value) => handleInputChange('civil_marriage_certificate_number', value)}
                                placeholder="Civil certificate number (if applicable)"
                                hasError={hasError('civil_marriage_certificate_number')}
                                errorMessage={getErrorMessage('civil_marriage_certificate_number')}
                            />

                            <div className="md:col-span-2">
                                <FormInput
                                    id="other_documents"
                                    label="Other Documents"
                                    type="textarea"
                                    rows={3}
                                    value={data.other_documents}
                                    onChange={(value) => handleInputChange('other_documents', value)}
                                    placeholder="Any other relevant documents or notes..."
                                    hasError={hasError('other_documents')}
                                    errorMessage={getErrorMessage('other_documents')}
                                />
                            </div>
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
    }, [activeTab, data, isFieldVisible, isFieldRequired, handleInputChange, FormInput, SmallChristianCommunitySearchField, FamilySearchField, selectedFamily, churchGroups, localChurches, educationLevels, validateChurchGroupGender, inheritFamilyData, hasError, getErrorMessage, selectedAdditionalGroups, setSelectedAdditionalGroups, setData]);

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
                                        disabled={processing}
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
