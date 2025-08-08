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
    member_number: string;
    first_name: string;
    last_name: string;
    full_name?: string;
    date_of_birth: string;
    gender: 'male' | 'female';
    phone?: string;
    email?: string;
    address?: string;
    occupation?: string;
    marital_status: 'single' | 'married' | 'divorced' | 'widowed';
    membership_status: 'active' | 'inactive' | 'transferred' | 'deceased';
    joining_date: string;
    family_id?: number;
    family?: Family;
    relationship_to_head?: string;
    emergency_contact?: string;
    emergency_phone?: string;
    notes?: string;
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
    member_number?: string;
    first_name: string;
    last_name: string;
    date_of_birth: string;
    gender: 'male' | 'female';
    phone?: string;
    email?: string;
    address?: string;
    occupation?: string;
    marital_status: 'single' | 'married' | 'divorced' | 'widowed';
    membership_status: 'active' | 'inactive' | 'transferred' | 'deceased';
    joining_date: string;
    family_id?: number;
    relationship_to_head?: string;
    emergency_contact?: string;
    emergency_phone?: string;
    notes?: string;
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
