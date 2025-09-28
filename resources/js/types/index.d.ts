export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    roles?: string[];
    permissions?: {
        can_manage_users: boolean;
        can_access_members: boolean;
        can_manage_members: boolean;
        can_create_members: boolean;
        can_edit_members: boolean;
        can_delete_members: boolean;
        can_export_members: boolean;
        can_access_families: boolean;
        can_manage_families: boolean;
        can_access_sacraments: boolean;
        can_manage_sacraments: boolean;
        can_access_tithes: boolean;
        can_manage_tithes: boolean;
        can_view_financial_reports: boolean;
        can_access_community_groups: boolean;
        can_manage_community_groups: boolean;
        can_access_reports: boolean;
        can_access_dashboard: boolean;
    };
}

export interface Member {
    id: number;
    member_number?: string;
    first_name: string;
    middle_name?: string;
    last_name: string;
    full_name?: string;
    date_of_birth?: string;
    gender: 'Male' | 'Female';
    id_number?: string;
    phone?: string;
    email?: string;
    residence?: string;
    local_church: string;
    small_christian_community?: string;
    church_group: string;
    additional_church_groups?: string[];
    membership_status?: 'active' | 'inactive' | 'transferred' | 'deceased';
    membership_date?: string;
    baptism_date?: string;
    confirmation_date?: string;
    matrimony_status: 'single' | 'married' | 'widowed' | 'separated';
    marriage_type?: 'customary' | 'church' | 'civil';
    occupation: 'employed' | 'self_employed' | 'not_employed';
    education_level: 'none' | 'primary' | 'kcpe' | 'secondary' | 'kcse' | 'certificate' | 'diploma' | 'degree' | 'masters' | 'phd';
    family_id?: number;
    family?: Family;
    parent?: string;
    godparent?: string;
    minister?: string;
    tribe?: string;
    clan?: string;
    is_differently_abled?: boolean;
    disability_description?: string;
    notes?: string;
    
    // Comprehensive Baptism Record Fields
    birth_village?: string;
    county?: string;
    baptism_location?: string;
    baptized_by?: string;
    sponsor?: string;
    father_name?: string;
    mother_name?: string;
    
    // Optional Sacrament Fields
    eucharist_location?: string;
    eucharist_date?: string;
    confirmation_location?: string;
    confirmation_register_number?: string;
    confirmation_number?: string;
    
    // Marriage Record Fields
    marriage_date?: string;
    marriage_location?: string;
    married_by?: string;
    spouse_name?: string;
    witness_1_name?: string;
    witness_2_name?: string;
    marriage_register_number?: string;
    marriage_certificate_number?: string;
    
    // Additional Fields
    godfather_name?: string;
    godmother_name?: string;
    
    created_at: string;
    updated_at: string;
}

export interface Family {
    id: number;
    family_name: string;
    head_of_family: string;
    address?: string;
    phone?: string;
    email?: string;
    status: 'active' | 'inactive' | 'moved';
    registration_date: string;
    notes?: string;
    members?: Member[];
    members_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Sacrament {
    id: number;
    member_id: number;
    member?: Member;
    sacrament_type: 'baptism' | 'confirmation' | 'marriage' | 'ordination' | 'anointing';
    sacrament_date: string;
    officiant: string;
    location: string;
    certificate_number?: string;
    notes?: string;
    witnesses?: string;
    created_at: string;
    updated_at: string;
}

export interface Tithe {
    id: number;
    member_id?: number;
    member?: Member;
    amount: number;
    date_given: string;
    type: 'tithe' | 'offering' | 'special_offering' | 'donation';
    payment_method: 'cash' | 'check' | 'bank_transfer' | 'mobile_money';
    reference_number?: string;
    notes?: string;
    received_by?: string;
    created_at: string;
    updated_at: string;
}

export interface CommunityGroup {
    id: number;
    name: string;
    description?: string;
    leader_id?: number;
    leader?: Member;
    meeting_day?: string;
    meeting_time?: string;
    meeting_location?: string;
    status: 'active' | 'inactive' | 'suspended';
    created_at: string;
    updated_at: string;
    members_count?: number;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
};

// Pagination types
export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

// Form data types
export interface MemberFormData {
    first_name: string;
    middle_name?: string;
    last_name: string;
    date_of_birth?: string;
    gender: 'Male' | 'Female';
    id_number?: string;
    phone?: string;
    email?: string;
    residence?: string;
    local_church: string;
    small_christian_community?: string;
    church_group: string;
    additional_church_groups?: string[];
    membership_status?: 'active' | 'inactive' | 'transferred' | 'deceased';
    membership_date?: string;
    baptism_date?: string;
    confirmation_date?: string;
    matrimony_status: 'single' | 'married' | 'widowed' | 'separated';
    marriage_type?: 'customary' | 'church' | 'civil';
    occupation: 'employed' | 'self_employed' | 'not_employed';
    education_level: 'none' | 'primary' | 'kcpe' | 'secondary' | 'kcse' | 'certificate' | 'diploma' | 'degree' | 'masters' | 'phd';
    family_id?: number;
    parent?: string;
    godparent?: string;
    minister?: string;
    tribe?: string;
    clan?: string;
    is_differently_abled?: boolean;
    disability_description?: string;
    notes?: string;
    
    // Comprehensive Baptism Record Fields
    birth_village?: string;
    county?: string;
    baptism_location?: string;
    baptized_by?: string;
    sponsor?: string;
    father_name?: string;
    mother_name?: string;
    
    // Optional Sacrament Fields
    eucharist_location?: string;
    eucharist_date?: string;
    confirmation_location?: string;
    confirmation_register_number?: string;
    confirmation_number?: string;
    
    // Marriage Record Fields
    marriage_date?: string;
    marriage_location?: string;
    married_by?: string;
    spouse_name?: string;
    witness_1_name?: string;
    witness_2_name?: string;
    marriage_register_number?: string;
    marriage_certificate_number?: string;
    
    // Additional Fields
    godfather_name?: string;
    godmother_name?: string;
}

export interface FamilyFormData {
    family_name: string;
    head_of_family: string;
    address?: string;
    phone?: string;
    email?: string;
    status: 'active' | 'inactive' | 'moved';
    registration_date: string;
    notes?: string;
}
