import React, { useState, useEffect, useCallback, useMemo, useRef, memo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import StatsCard from '@/Components/StatsCard';
import { 
    Plus, 
    Download, 
    Upload, 
    Trash2, 
    Search,
    Filter,
    Eye,
    Edit,
    Phone,
    Mail,
    MapPin,
    Calendar,
    Users,
    UserCheck,
    UserX,
    Clock,
    X,
    RefreshCw,
    Loader2,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';

// Types
interface Member {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    full_name: string;
    email?: string;
    phone?: string;
    date_of_birth?: string;
    gender?: string;
    id_number?: string;
    matrimony_status?: string;
    marital_status?: string;
    marriage_type?: string;
    residence?: string;
    local_church?: string;
    church_group?: string;
    membership_status: string;
    member_number?: string;
}

interface Filters {
    search?: string;
    membership_status?: string;
    local_church?: string;
    church_group?: string;
    age_group?: string;
    page?: number;
    per_page?: number;
}

interface FilterOptions {
    membership_statuses: Array<{ value: string; label: string }>;
    local_churches: Array<{ value: string; label: string }>;
    church_groups: Array<{ value: string; label: string }>;
    age_groups: Array<{ value: string; label: string }>;
}

interface Stats {
    total_members: number;
    active_members: number;
    inactive_members: number;
    transferred_members: number;
    deceased_members: number;
    recent_registrations: number;
}

interface PaginatedMembers {
    data: Member[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface MembersIndexProps {
    auth: any;
    members: PaginatedMembers;
    stats: Stats;
    filters?: Filters;
    filterOptions: FilterOptions;
    flash?: {
        success?: string;
        error?: string;
    };
}

// Constants
const MEMBER_STATUS_CONFIG = {
    active: { 
        label: 'Active', 
        color: 'bg-green-100 text-green-800 border border-green-200',
        icon: UserCheck 
    },
    inactive: { 
        label: 'Inactive', 
        color: 'bg-red-100 text-red-800 border border-red-200',
        icon: UserX 
    },
    transferred: { 
        label: 'Transferred', 
        color: 'bg-blue-100 text-blue-800 border border-blue-200',
        icon: Users 
    },
    deceased: { 
        label: 'Deceased', 
        color: 'bg-gray-100 text-gray-800 border border-gray-200',
        icon: X 
    }
} as const;

const STATS_CARDS_CONFIG = [
    {
        key: 'total_members' as keyof Stats,
        title: 'Total Members',
        icon: Users,
        bgColor: 'bg-blue-500',
        textColor: 'text-blue-600'
    },
    {
        key: 'active_members' as keyof Stats,
        title: 'Active Members',
        icon: UserCheck,
        bgColor: 'bg-green-500',
        textColor: 'text-green-600'
    },
    {
        key: 'inactive_members' as keyof Stats,
        title: 'Inactive Members',
        icon: UserX,
        bgColor: 'bg-red-500',
        textColor: 'text-red-600'
    },
    {
        key: 'transferred_members' as keyof Stats,
        title: 'Transferred Members',
        icon: Users,
        bgColor: 'bg-blue-500',
        textColor: 'text-blue-600'
    },
    {
        key: 'deceased_members' as keyof Stats,
        title: 'Deceased Members',
        icon: X,
        bgColor: 'bg-gray-500',
        textColor: 'text-gray-600'
    },
    {
        key: 'recent_registrations' as keyof Stats,
        title: 'Recent Registrations',
        icon: Clock,
        bgColor: 'bg-purple-500',
        textColor: 'text-purple-600'
    }
];

// Utility functions
const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
    // Enhanced toast notification
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    
    toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-2 transform transition-all duration-300 translate-x-full`;
    toast.textContent = message;
    
    toastContainer.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 10);
    
    // Remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
};

const createToastContainer = (): HTMLElement => {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-50 flex flex-col';
    document.body.appendChild(container);
    return container;
};

// Debounce utility
const debounce = <T extends (...args: any[]) => void>(func: T, delay: number) => {
    let timeoutId: NodeJS.Timeout;
    return (...args: Parameters<T>) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func(...args), delay);
    };
};

// Stats Component
const MembersStats = memo<{ stats: Stats; isLoading: boolean }>(({ stats, isLoading }) => {
    const formatNumber = useCallback((num: number): string => {
        return new Intl.NumberFormat('en-US').format(num || 0);
    }, []);

    const statsData = useMemo(() => 
        STATS_CARDS_CONFIG.map(config => ({
            ...config,
            value: stats[config.key] || 0,
            formattedValue: formatNumber(stats[config.key] || 0)
        })), [stats, formatNumber]
    );

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
            {statsData.map(({ key, title, icon: Icon, bgColor, textColor, formattedValue }) => (
                <div key={key} className="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow duration-200">
                    <div className="p-5">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className={`${bgColor} rounded-md p-3`}>
                                    <Icon className="h-6 w-6 text-white" aria-hidden="true" />
                                </div>
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">
                                        {title}
                                    </dt>
                                    <dd>
                                        {isLoading ? (
                                            <div className="flex items-center space-x-2">
                                                <div className="animate-pulse bg-gray-200 h-8 w-16 rounded"></div>
                                                <Loader2 className="h-4 w-4 animate-spin text-gray-400" />
                                            </div>
                                        ) : (
                                            <div className={`text-lg font-medium ${textColor}`}>
                                                {formattedValue}
                                            </div>
                                        )}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
});

MembersStats.displayName = 'MembersStats';

// Search and Filters Component
const MembersSearchAndFilters = memo<{
    filters: Filters;
    filterOptions: FilterOptions;
    showFilters: boolean;
    isLoading: boolean;
    searchInputRef: React.RefObject<HTMLInputElement>;
    onSearchChange: (value: string) => void;
    onFilterChange: (key: string, value: string) => void;
    onToggleFilters: () => void;
    onClearFilters: () => void;
    onRefresh: () => void;
}>(({ 
    filters, 
    filterOptions, 
    showFilters, 
    isLoading, 
    searchInputRef,
    onSearchChange, 
    onFilterChange, 
    onToggleFilters, 
    onClearFilters, 
    onRefresh 
}) => {
    const [searchValue, setSearchValue] = useState(filters.search || '');

    const handleSearchInput = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setSearchValue(value);
        onSearchChange(value);
    }, [onSearchChange]);

    const hasActiveFilters = useMemo(() => {
        return Object.entries(filters).some(([key, value]) => {
            if (key === 'page' || key === 'per_page') return false;
            return value && value !== '' && value !== 'all';
        });
    }, [filters]);

    return (
        <div className="bg-white shadow-lg rounded-lg mb-6">
            <div className="p-6">
                <div className="flex flex-col sm:flex-row gap-4 mb-4">
                    <div className="flex-1 relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search className="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            ref={searchInputRef}
                            type="text"
                            placeholder="Search members by name, email, phone..."
                            value={searchValue}
                            onChange={handleSearchInput}
                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        />
                        {isLoading && (
                            <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <Loader2 className="h-4 w-4 animate-spin text-gray-400" />
                            </div>
                        )}
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <button
                            type="button"
                            onClick={onToggleFilters}
                            className={`inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors ${
                                showFilters || hasActiveFilters
                                    ? 'bg-indigo-50 text-indigo-700 border-indigo-300'
                                    : 'bg-white text-gray-700 hover:bg-gray-50'
                            }`}
                        >
                            <Filter className="h-4 w-4 mr-2" />
                            Filters
                            {hasActiveFilters && (
                                <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    Active
                                </span>
                            )}
                        </button>
                        
                        <button
                            type="button"
                            onClick={onRefresh}
                            disabled={isLoading}
                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors"
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
                            Refresh
                        </button>
                    </div>
                </div>

                {showFilters && (
                    <div className="border-t border-gray-200 pt-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select
                                    value={filters.membership_status || ''}
                                    onChange={(e) => onFilterChange('membership_status', e.target.value)}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-md transition-colors"
                                >
                                    <option value="">All Statuses</option>
                                    {filterOptions.membership_statuses?.map((status) => (
                                        <option key={status.value} value={status.value}>
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Local Church
                                </label>
                                <select
                                    value={filters.local_church || ''}
                                    onChange={(e) => onFilterChange('local_church', e.target.value)}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-md transition-colors"
                                >
                                    <option value="">All Churches</option>
                                    {filterOptions.local_churches?.map((church) => (
                                        <option key={church.value} value={church.value}>
                                            {church.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Church Group
                                </label>
                                <select
                                    value={filters.church_group || ''}
                                    onChange={(e) => onFilterChange('church_group', e.target.value)}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-md transition-colors"
                                >
                                    <option value="">All Groups</option>
                                    {filterOptions.church_groups?.map((group) => (
                                        <option key={group.value} value={group.value}>
                                            {group.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Age Group
                                </label>
                                <select
                                    value={filters.age_group || ''}
                                    onChange={(e) => onFilterChange('age_group', e.target.value)}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-md transition-colors"
                                >
                                    <option value="">All Ages</option>
                                    {filterOptions.age_groups?.map((ageGroup) => (
                                        <option key={ageGroup.value} value={ageGroup.value}>
                                            {ageGroup.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {hasActiveFilters && (
                            <div className="mt-4 flex justify-end">
                                <button
                                    type="button"
                                    onClick={onClearFilters}
                                    className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                >
                                    <X className="h-4 w-4 mr-1" />
                                    Clear Filters
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
});

MembersSearchAndFilters.displayName = 'MembersSearchAndFilters';

// Member Card Component
const MemberCard = memo<{
    member: Member;
    isSelected: boolean;
    onToggleSelection: (id: number) => void;
    onDelete: (member: Member) => void;
    onStatusChange?: (id: number, status: string) => void;
}>(({ 
    member, 
    isSelected, 
    onToggleSelection, 
    onDelete, 
    onStatusChange
}) => {
    const [isChangingStatus, setIsChangingStatus] = useState(false);
    const [showStatusDropdown, setShowStatusDropdown] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setShowStatusDropdown(false);
            }
        };

        if (showStatusDropdown) {
            document.addEventListener('mousedown', handleClickOutside);
            return () => document.removeEventListener('mousedown', handleClickOutside);
        }
    }, [showStatusDropdown]);
    
    const memberName = useMemo(() => {
        if (member.full_name) return member.full_name;
        const parts = [member.first_name, member.middle_name, member.last_name].filter(Boolean);
        return parts.join(' ') || 'Unknown Member';
    }, [member.first_name, member.middle_name, member.last_name, member.full_name]);

    const memberAge = useMemo(() => {
        if (!member.date_of_birth) return null;
        try {
            const birthDate = new Date(member.date_of_birth);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                return age - 1;
            }
            return age;
        } catch {
            return null;
        }
    }, [member.date_of_birth]);

    const statusConfig = useMemo(() => {
        return MEMBER_STATUS_CONFIG[member.membership_status as keyof typeof MEMBER_STATUS_CONFIG] || 
               MEMBER_STATUS_CONFIG.active;
    }, [member.membership_status]);

    const handleToggleSelection = useCallback(() => {
        onToggleSelection(member.id);
    }, [onToggleSelection, member.id]);

    const handleDelete = useCallback(() => {
        onDelete(member);
    }, [onDelete, member]);

    const handleStatusChange = useCallback(async (newStatus: string) => {
        if (!onStatusChange || isChangingStatus) return;
        
        setIsChangingStatus(true);
        setShowStatusDropdown(false);
        
        try {
            await onStatusChange(member.id, newStatus);
        } catch (error) {
            console.error('Status change failed:', error);
            showToast('Failed to update member status', 'error');
        } finally {
            setIsChangingStatus(false);
        }
    }, [onStatusChange, member.id, isChangingStatus]);

    return (
        <div className={`
            bg-white rounded-lg shadow-sm border transition-all duration-200 hover:shadow-lg
            ${isSelected ? 'ring-2 ring-indigo-500 border-indigo-200 shadow-md' : 'border-gray-200 hover:border-gray-300'}
        `}>
            <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-3">
                        <input
                            type="checkbox"
                            checked={isSelected}
                            onChange={handleToggleSelection}
                            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-colors"
                            aria-label={`Select ${memberName}`}
                        />
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 truncate" title={memberName}>
                                {memberName}
                            </h3>
                            <p className="text-sm text-gray-500">
                                {member.member_number ? `#${member.member_number}` : `ID: ${member.id}`}
                            </p>
                        </div>
                    </div>
                    
                    <div className="relative" ref={dropdownRef}>
                        <button
                            onClick={() => setShowStatusDropdown(!showStatusDropdown)}
                            disabled={isChangingStatus || !onStatusChange}
                            className={`text-xs px-3 py-1 rounded-full font-medium transition-all duration-200 ${statusConfig.color} ${
                                onStatusChange ? 'hover:shadow-md cursor-pointer hover:scale-105' : 'cursor-default'
                            } ${isChangingStatus ? 'opacity-50' : ''}`}
                            title={onStatusChange ? 'Click to change status' : member.membership_status}
                        >
                            {isChangingStatus ? (
                                <div className="flex items-center space-x-1">
                                    <Loader2 className="w-3 h-3 animate-spin" />
                                    <span>Updating...</span>
                                </div>
                            ) : (
                                <div className="flex items-center space-x-1">
                                    <statusConfig.icon className="w-3 h-3" />
                                    <span>{statusConfig.label}</span>
                                </div>
                            )}
                        </button>

                        {showStatusDropdown && onStatusChange && !isChangingStatus && (
                            <div className="absolute right-0 top-full mt-1 w-40 bg-white rounded-md shadow-lg border border-gray-200 z-50">
                                <div className="py-1">
                                    {Object.entries(MEMBER_STATUS_CONFIG).map(([status, config]) => (
                                        <button
                                            key={status}
                                            onClick={() => handleStatusChange(status)}
                                            className={`
                                                w-full text-left px-3 py-2 text-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 flex items-center space-x-2 transition-colors
                                                ${member.membership_status === status ? 'bg-gray-100 font-medium' : ''}
                                            `}
                                        >
                                            <config.icon className="w-4 h-4" />
                                            <span>{config.label}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="space-y-2 mb-4">
                    {member.phone && (
                        <div className="flex items-center text-sm text-gray-600">
                            <Phone className="w-4 h-4 mr-2 text-gray-400" />
                            <span className="truncate">{member.phone}</span>
                        </div>
                    )}
                    
                    {member.email && (
                        <div className="flex items-center text-sm text-gray-600">
                            <Mail className="w-4 h-4 mr-2 text-gray-400" />
                            <span className="truncate">{member.email}</span>
                        </div>
                    )}
                    
                    {member.residence && (
                        <div className="flex items-center text-sm text-gray-600">
                            <MapPin className="w-4 h-4 mr-2 text-gray-400" />
                            <span className="truncate">{member.residence}</span>
                        </div>
                    )}
                    
                    {memberAge && (
                        <div className="flex items-center text-sm text-gray-600">
                            <Calendar className="w-4 h-4 mr-2 text-gray-400" />
                            <span>{memberAge} years old{member.gender ? ` • ${member.gender}` : ''}</span>
                        </div>
                    )}

                    {member.marital_status && (
                        <div className="flex items-center text-sm text-gray-600">
                            <Users className="w-4 h-4 mr-2 text-gray-400" />
                            <span className="capitalize">{member.marital_status.replace('_', ' ')}</span>
                        </div>
                    )}
                </div>

                <div className="bg-gray-50 rounded-lg p-3 mb-4">
                    <div className="text-sm space-y-1">
                        <p className="font-medium text-gray-900 truncate" title={member.local_church}>
                            {member.local_church || 'No church assigned'}
                        </p>
                        <p className="text-gray-600 truncate" title={member.church_group}>
                            {member.church_group || 'No group assigned'}
                        </p>
                        {member.id_number && (
                            <p className="text-xs text-gray-500">
                                ID: {member.id_number}
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div className="flex space-x-2">
                        <Link
                            href={`/members/${member.id}`}
                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            <Eye className="w-3 h-3 mr-1" />
                            View
                        </Link>
                        
                        <Link
                            href={`/members/${member.id}/edit`}
                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            <Edit className="w-3 h-3 mr-1" />
                            Edit
                        </Link>
                    </div>
                    
                    <button
                        onClick={handleDelete}
                        className="inline-flex items-center px-3 py-1.5 border border-red-300 rounded-md text-xs font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                        title={`Delete ${memberName}`}
                    >
                        <Trash2 className="w-3 h-3 mr-1" />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    );
});

MemberCard.displayName = 'MemberCard';

// Main Component with Direct Fetch API
export default function MembersIndex({ 
    auth, 
    members: initialMembers, 
    stats: initialStats, 
    filters: initialFilters = {}, 
    filterOptions: initialFilterOptions,
    flash 
}: MembersIndexProps) {
    // State management
    const [members, setMembers] = useState<PaginatedMembers>(initialMembers);
    const [stats, setStats] = useState<Stats>(initialStats);
    const [filters, setFilters] = useState<Filters>(initialFilters);
    const [filterOptions, setFilterOptions] = useState<FilterOptions>(initialFilterOptions);
    const [selectedMembers, setSelectedMembers] = useState<number[]>([]);
    const [showFilters, setShowFilters] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [isImporting, setIsImporting] = useState(false);
    const [memberToDelete, setMemberToDelete] = useState<Member | null>(null);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);

    const searchInputRef = useRef<HTMLInputElement>(null);

    // Debounced search using Inertia router
    const debouncedSearch = useMemo(() => 
        debounce((query: string) => {
            const searchParams = { ...filters, search: query, page: 1 };
            setFilters(searchParams);
            
            router.get('/members', searchParams, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => setIsLoading(true),
                onFinish: () => setIsLoading(false),
            });
        }, 300), [filters]
    );

    // Event handlers using Inertia router
    const handleSearchChange = useCallback((value: string) => {
        debouncedSearch(value);
    }, [debouncedSearch]);

    const handleFilterChange = useCallback((key: string, value: string) => {
        const newFilters = { ...filters, [key]: value, page: 1 };
        setFilters(newFilters);
        
        router.get('/members', newFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    }, [filters]);

    const handleClearFilters = useCallback(() => {
        const clearedFilters = { page: 1, per_page: filters.per_page || 25 };
        setFilters(clearedFilters);
        
        router.get('/members', clearedFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    }, [filters.per_page]);

    const handleRefresh = useCallback(() => {
        router.reload({
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    }, []);

    const handlePageChange = useCallback((page: number) => {
        const newFilters = { ...filters, page };
        setFilters(newFilters);
        
        router.get('/members', newFilters, {
            preserveState: true,
            preserveScroll: false,
            replace: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    }, [filters]);

    // Per page change handler
    const handlePerPageChange = useCallback((perPage: number) => {
        const newFilters = { ...filters, per_page: perPage, page: 1 };
        setFilters(newFilters);
        
        router.get('/members', newFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    }, [filters]);

    // Status change using Inertia router
    const handleStatusChange = useCallback(async (memberId: number, newStatus: string) => {
        try {
            router.patch(`/members/${memberId}/update-status`, 
                { membership_status: newStatus },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onStart: () => setIsLoading(true),
                    onFinish: () => setIsLoading(false),
                    onSuccess: (page) => {
                        const member = members.data.find(m => m.id === memberId);
                        const memberName = member ? `${member.first_name} ${member.last_name}` : 'Member';
                        showToast(`${memberName} status updated successfully`, 'success');
                    },
                    onError: (errors) => {
                        const errorMessage = Object.values(errors)[0] as string || 'Failed to update status';
                        showToast(errorMessage, 'error');
                    },
                }
            );
        } catch (error) {
            console.error('Status change error:', error);
            showToast('Failed to update status', 'error');
        }
    }, [members.data]);

    // Delete with direct fetch
    const handleDeleteMember = useCallback((member: Member) => {
        setMemberToDelete(member);
        setShowDeleteModal(true);
    }, []);

    const confirmDelete = useCallback(() => {
        if (!memberToDelete) return;

        router.delete(`/members/${memberToDelete.id}`, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: () => {
                setShowDeleteModal(false);
                setMemberToDelete(null);
                showToast('Member deleted successfully', 'success');
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0] as string || 'Failed to delete member';
                showToast(errorMessage, 'error');
            },
        });
    }, [memberToDelete]);

    // Bulk delete handler
    const handleBulkDelete = useCallback(() => {
        if (selectedMembers.length === 0) return;

        router.delete('/members/bulk-delete', {
            data: { member_ids: selectedMembers },
            preserveState: true,
            preserveScroll: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: () => {
                setShowBulkDeleteModal(false);
                setSelectedMembers([]);
                showToast(`${selectedMembers.length} members deleted successfully`, 'success');
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0] as string || 'Failed to delete members';
                showToast(errorMessage, 'error');
            },
        });
    }, [selectedMembers]);

    // Export function
    const handleExport = useCallback((format: 'excel' | 'pdf') => {
        try {
            const params = new URLSearchParams();
            
            // Add filters to params
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== '' && value !== null) {
                    params.append(key, String(value));
                }
            });
            
            // Add format
            params.append('format', format);

            const exportUrl = `/members/export?${params.toString()}`;
            window.open(exportUrl, '_blank');
            showToast('Export started - download will begin shortly', 'success');
        } catch (error) {
            console.error('Export error:', error);
            showToast('Export failed', 'error');
        }
    }, [filters]);

    // Selection handlers
    const handleToggleSelection = useCallback((id: number) => {
        setSelectedMembers(prev => 
            prev.includes(id) 
                ? prev.filter(memberId => memberId !== id)
                : [...prev, id]
        );
    }, []);

    const handleSelectAll = useCallback(() => {
        if (selectedMembers.length === members.data.length) {
            setSelectedMembers([]);
        } else {
            setSelectedMembers(members.data.map(member => member.id));
        }
    }, [selectedMembers.length, members.data]);

    // Show flash messages and handle initial data
    useEffect(() => {
        // Handle the case where we might receive JSON instead of proper Inertia response
        if (typeof initialMembers === 'object' && initialMembers !== null) {
            // If the response has a "members" property, it's likely a JSON response
            if ('members' in initialMembers && typeof initialMembers.members === 'object') {
                // Extract the actual members data from the JSON response
                const jsonResponse = initialMembers as any;
                if (jsonResponse.success && jsonResponse.members) {
                    setMembers(jsonResponse.members);
                    if (jsonResponse.stats) {
                        setStats(jsonResponse.stats);
                    }
                    if (jsonResponse.filterOptions) {
                        setFilterOptions(jsonResponse.filterOptions);
                    }
                    showToast('Data loaded successfully', 'info');
                }
            }
        }

        if (flash?.success) {
            showToast(flash.success, 'success');
        }
        if (flash?.error) {
            showToast(flash.error, 'error');
        }
    }, [flash, initialMembers]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Parish Members
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage and organize parish member information
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        {/* Import Button */}
                        <button
                            type="button"
                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                            onClick={() => setShowImportModal(true)}
                        >
                            <Upload className="h-4 w-4 mr-2" />
                            Import
                        </button>

                        {/* Export Button */}
                        <button
                            type="button"
                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                            onClick={() => handleExport('excel')}
                        >
                            <Download className="h-4 w-4 mr-2" />
                            Export Excel
                        </button>

                        {/* Add Member Button */}
                        <Link
                            href="/members/create"
                            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            Add Member
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Members" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <MembersStats stats={stats} isLoading={isLoading} />

                    {/* Search and Filters */}
                    <MembersSearchAndFilters
                        filters={filters}
                        filterOptions={filterOptions}
                        showFilters={showFilters}
                        isLoading={isLoading}
                        searchInputRef={searchInputRef}
                        onSearchChange={handleSearchChange}
                        onFilterChange={handleFilterChange}
                        onToggleFilters={() => setShowFilters(!showFilters)}
                        onClearFilters={handleClearFilters}
                        onRefresh={handleRefresh}
                    />

                    {/* Selection Bar */}
                    {members.data.length > 0 && (
                        <div className="bg-white rounded-lg shadow-lg p-4 mb-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={selectedMembers.length === members.data.length && members.data.length > 0}
                                            onChange={handleSelectAll}
                                            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-colors"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            Select all ({members.data.length})
                                        </span>
                                    </label>
                                    {selectedMembers.length > 0 && (
                                        <span className="text-sm text-gray-500">
                                            {selectedMembers.length} of {members.data.length} selected
                                        </span>
                                    )}
                                </div>
                                
                                <div className="flex items-center space-x-2">
                                    {selectedMembers.length > 0 && (
                                        <>
                                            <button
                                                type="button"
                                                onClick={() => handleExport('excel')}
                                                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                            >
                                                <Download className="h-4 w-4 mr-2" />
                                                Export Selected
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setShowBulkDeleteModal(true)}
                                                className="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" />
                                                Delete Selected
                                            </button>
                                        </>
                                    )}
                                    
                                    {selectedMembers.length > 0 && (
                                        <button
                                            type="button"
                                            onClick={() => setSelectedMembers([])}
                                            className="text-sm text-gray-500 hover:text-gray-700 transition-colors"
                                        >
                                            Clear selection
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Members Grid */}
                    <div className="mb-6">
                        {isLoading ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {[...Array(6)].map((_, index) => (
                                    <div key={index} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                        <div className="animate-pulse">
                                            <div className="flex items-center space-x-3 mb-4">
                                                <div className="h-4 w-4 bg-gray-200 rounded"></div>
                                                <div className="space-y-2 flex-1">
                                                    <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                                                    <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                                                </div>
                                            </div>
                                            <div className="space-y-2 mb-4">
                                                <div className="h-3 bg-gray-200 rounded"></div>
                                                <div className="h-3 bg-gray-200 rounded"></div>
                                                <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                                            </div>
                                            <div className="h-16 bg-gray-100 rounded mb-4"></div>
                                            <div className="flex justify-between pt-4 border-t border-gray-100">
                                                <div className="flex space-x-2">
                                                    <div className="h-6 bg-gray-200 rounded w-16"></div>
                                                    <div className="h-6 bg-gray-200 rounded w-16"></div>
                                                </div>
                                                <div className="h-6 bg-gray-200 rounded w-16"></div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : !members.data || members.data.length === 0 ? (
                            <div className="text-center py-12">
                                <Users className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No members found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Get started by adding a new member to your parish.
                                </p>
                                <div className="mt-6">
                                    <Link
                                        href="/members/create"
                                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Member
                                    </Link>
                                </div>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {members.data.map((member) => (
                                    <MemberCard
                                        key={member.id}
                                        member={member}
                                        isSelected={selectedMembers.includes(member.id)}
                                        onToggleSelection={handleToggleSelection}
                                        onDelete={handleDeleteMember}
                                        onStatusChange={handleStatusChange}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {members.last_page > 1 && (
                        <div className="bg-white px-4 py-3 sm:px-6 border border-gray-200 rounded-lg">
                            <div className="flex items-center justify-between">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    <button
                                        onClick={() => handlePageChange(members.current_page - 1)}
                                        disabled={members.current_page <= 1}
                                        className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        onClick={() => handlePageChange(members.current_page + 1)}
                                        disabled={members.current_page >= members.last_page}
                                        className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Next
                                    </button>
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div className="flex items-center space-x-4">
                                        <p className="text-sm text-gray-700">
                                            Showing{' '}
                                            <span className="font-medium">{members.from}</span>
                                            {' '}to{' '}
                                            <span className="font-medium">{members.to}</span>
                                            {' '}of{' '}
                                            <span className="font-medium">{members.total}</span>
                                            {' '}results
                                        </p>
                                        <div className="flex items-center space-x-2">
                                            <label className="text-sm text-gray-700">Per page:</label>
                                            <select
                                                value={members.per_page}
                                                onChange={(e) => handlePerPageChange(Number(e.target.value))}
                                                className="block w-auto text-sm border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value={10}>10</option>
                                                <option value={25}>25</option>
                                                <option value={50}>50</option>
                                                <option value={100}>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <button
                                                onClick={() => handlePageChange(members.current_page - 1)}
                                                disabled={members.current_page <= 1}
                                                className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <span className="sr-only">Previous</span>
                                                <ChevronLeft className="h-5 w-5" />
                                            </button>
                                            
                                            {/* Page numbers */}
                                            {Array.from({ length: Math.min(7, members.last_page) }, (_, i) => {
                                                let pageNumber;
                                                if (members.last_page <= 7) {
                                                    pageNumber = i + 1;
                                                } else if (members.current_page <= 4) {
                                                    pageNumber = i + 1;
                                                } else if (members.current_page >= members.last_page - 3) {
                                                    pageNumber = members.last_page - 6 + i;
                                                } else {
                                                    pageNumber = members.current_page - 3 + i;
                                                }
                                                
                                                if (pageNumber < 1 || pageNumber > members.last_page) return null;
                                                
                                                const isCurrentPage = pageNumber === members.current_page;
                                                
                                                return (
                                                    <button
                                                        key={pageNumber}
                                                        onClick={() => handlePageChange(pageNumber)}
                                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                            isCurrentPage
                                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        {pageNumber}
                                                    </button>
                                                );
                                            })}
                                            
                                            <button
                                                onClick={() => handlePageChange(members.current_page + 1)}
                                                disabled={members.current_page >= members.last_page}
                                                className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <span className="sr-only">Next</span>
                                                <ChevronRight className="h-5 w-5" />
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Modal */}
            {showDeleteModal && memberToDelete && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 transition-opacity" onClick={() => setShowDeleteModal(false)}>
                            <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>

                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <Trash2 className="h-6 w-6 text-red-600" />
                                    </div>
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Delete Member
                                        </h3>
                                        <div className="mt-2">
                                            <p className="text-sm text-gray-500">
                                                Are you sure you want to delete <strong>{memberToDelete.full_name || `${memberToDelete.first_name} ${memberToDelete.last_name}`}</strong>? 
                                                This action cannot be undone.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={confirmDelete}
                                    disabled={isDeleting}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 transition-colors"
                                >
                                    {isDeleting ? (
                                        <>
                                            <Loader2 className="animate-spin h-4 w-4 mr-2" />
                                            Deleting...
                                        </>
                                    ) : (
                                        'Delete'
                                    )}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowDeleteModal(false)}
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Bulk Delete Modal */}
            {showBulkDeleteModal && selectedMembers.length > 0 && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 transition-opacity" onClick={() => setShowBulkDeleteModal(false)}>
                            <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>

                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <Trash2 className="h-6 w-6 text-red-600" />
                                    </div>
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Delete Selected Members
                                        </h3>
                                        <div className="mt-2">
                                            <p className="text-sm text-gray-500">
                                                Are you sure you want to delete <strong>{selectedMembers.length}</strong> selected members? 
                                                This action cannot be undone.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={handleBulkDelete}
                                    disabled={isDeleting}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 transition-colors"
                                >
                                    {isDeleting ? (
                                        <>
                                            <Loader2 className="animate-spin h-4 w-4 mr-2" />
                                            Deleting...
                                        </>
                                    ) : (
                                        'Delete All'
                                    )}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowBulkDeleteModal(false)}
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Import Modal */}
            {showImportModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 transition-opacity" onClick={() => setShowImportModal(false)}>
                            <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>

                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <Upload className="h-6 w-6 text-indigo-600" />
                                    </div>
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Import Members
                                        </h3>
                                        <div className="mt-2">
                                            <p className="text-sm text-gray-500 mb-4">
                                                Upload a CSV or Excel file to import multiple members at once.
                                            </p>
                                            <div className="space-y-4">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">
                                                        Choose file
                                                    </label>
                                                    <input
                                                        type="file"
                                                        accept=".csv,.xlsx,.xls"
                                                        onChange={(e) => setImportFile(e.target.files?.[0] || null)}
                                                        className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                                    />
                                                </div>
                                                {importFile && (
                                                    <div className="text-sm text-gray-600">
                                                        Selected: {importFile.name}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (importFile) {
                                            // Handle import logic here
                                            setIsImporting(true);
                                            // You would typically send this to your backend
                                            setTimeout(() => {
                                                setIsImporting(false);
                                                setShowImportModal(false);
                                                setImportFile(null);
                                                showToast('Import completed successfully', 'success');
                                            }, 2000);
                                        }
                                    }}
                                    disabled={!importFile || isImporting}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 transition-colors"
                                >
                                    {isImporting ? (
                                        <>
                                            <Loader2 className="animate-spin h-4 w-4 mr-2" />
                                            Importing...
                                        </>
                                    ) : (
                                        'Import'
                                    )}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowImportModal(false);
                                        setImportFile(null);
                                    }}
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}