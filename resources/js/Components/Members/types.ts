// Re-export shared types from main types file to avoid duplication
import { PageProps, Member, PaginatedData } from '@/types';

export type { Member, PageProps };
export type PaginatedMembers = PaginatedData<Member>;

// Enhanced Stats interface specifically for Members
export interface Stats {
    total_members: number;
    active_members: number;
    new_this_month: number;
    by_church: Record<string, number>;
    by_group: Record<string, number>;
    by_status: Record<string, number>;
    by_gender: Record<string, number>;
    statistics?: {
        total_members: number;
        active_members: number;
        inactive_members: number;
        transferred_members: number;
        deceased_members: number;
        active_percentage: number;
        new_this_month: number;
        male_members: number;
        female_members: number;
    };
}

export interface FilterOption {
    value: string;
    label: string;
}

export interface FilterOptions {
    local_churches?: string[];
    church_groups?: FilterOption[];
    membership_statuses?: FilterOption[];
    genders?: FilterOption[];
    age_groups?: FilterOption[];
}

export interface Filters {
    search?: string;
    local_church?: string;
    church_group?: string;
    membership_status?: string;
    gender?: string;
    age_group?: string;
    sort?: string;
    direction?: string;
    per_page?: number;
}

export interface MembersIndexProps extends PageProps {
    members: PaginatedMembers;
    stats: Stats;
    filters: Filters;
    filterOptions: FilterOptions;
}