import { useState, useEffect, useCallback, useMemo, useRef, memo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { debounce } from 'lodash';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Plus, 
    Download, 
    Upload, 
    Trash2, 
    FileText, 
    FileSpreadsheet, 
    Loader2,
    X,
    Search,
    Filter,
    RefreshCw,
    Eye,
    Edit,
    Phone,
    Mail,
    MapPin,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Users,
    UserCheck,
    UserX,
    Clock
} from 'lucide-react';
import { showNotification } from '@/Utils/notifications';

// Types
interface Member {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email?: string;
    phone?: string;
    date_of_birth?: string;
    residence?: string;
    local_church?: string;
    church_group?: string;
    membership_status: string;
    member_number?: string;
}

// Type for Inertia errors
interface InertiaErrors {
    [key: string]: string | string[];
}

interface Filters {
    search?: string;
    membership_status?: string;
    local_church?: string;
    church_group?: string;
    age_group?: string;
    page?: number;
    per_page?: number;
    [key: string]: string | number | undefined;
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
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
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
        color: 'bg-green-100 text-green-800',
        icon: UserCheck 
    },
    inactive: { 
        label: 'Inactive', 
        color: 'bg-red-100 text-red-800',
        icon: UserX 
    },
    pending: { 
        label: 'Pending', 
        color: 'bg-yellow-100 text-yellow-800',
        icon: Clock 
    },
    transferred: { 
        label: 'Transferred', 
        color: 'bg-blue-100 text-blue-800',
        icon: Users 
    },
    deceased: { 
        label: 'Deceased', 
        color: 'bg-gray-100 text-gray-800',
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
        key: 'recent_registrations' as keyof Stats,
        title: 'Recent Registrations',
        icon: Clock,
        bgColor: 'bg-purple-500',
        textColor: 'text-purple-600'
    }
];

// Safe route helper
const safeRoute = (routeName: string, params?: any): string => {
    try {
        if (typeof route === 'function') {
            return route(routeName, params);
        }
        
        // Fallback URL generation
        const baseRoutes: Record<string, string> = {
            'members.index': '/members',
            'members.create': '/members/create',
            'members.show': '/members',
            'members.edit': '/members',
            'members.destroy': '/members',
            'members.export': '/members/export',
            'members.import': '/members/import',
            'members.bulk-delete': '/members/bulk-delete',
            'members.update-status': '/members'
        };
        
        const baseUrl = baseRoutes[routeName] || '/members';
        
        if (params && typeof params === 'object' && !Array.isArray(params)) {
            if (routeName.includes('show') || routeName.includes('edit') || routeName.includes('destroy') || routeName.includes('update-status')) {
                const id = params.id || params;
                return `${baseUrl}/${id}${routeName.includes('edit') ? '/edit' : ''}${routeName.includes('update-status') ? '/update-status' : ''}`;
            }
            
            const queryString = Object.entries(params)
                .filter(([_, value]) => value !== null && value !== undefined && value !== '')
                .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
                .join('&');
            
            return queryString ? `${baseUrl}?${queryString}` : baseUrl;
        }
        
        if (params && (typeof params === 'string' || typeof params === 'number')) {
            return `${baseUrl}/${params}`;
        }
        
        return baseUrl;
    } catch (error) {
        console.warn('Route generation failed:', error);
        return '/members';
    }
};

const memberRoute = (action: string, id?: number): string => {
    const routeMap: Record<string, string> = {
        'show': 'members.show',
        'edit': 'members.edit',
        'destroy': 'members.destroy'
    };
    
    const routeName = routeMap[action] || 'members.index';
    return safeRoute(routeName, id);
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
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {statsData.map(({ key, title, icon: Icon, bgColor, textColor, formattedValue }) => (
                <div key={key} className="bg-white overflow-hidden shadow rounded-lg">
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
    onFilterChange: (key: string) => (e: React.ChangeEvent<HTMLSelectElement>) => void;
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
        <div className="bg-white shadow rounded-lg mb-6">
            <div className="p-6">
                {/* Search Bar */}
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
                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
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
                            className={`inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ${
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
                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
                            Refresh
                        </button>
                    </div>
                </div>

                {/* Advanced Filters Panel */}
                {showFilters && (
                    <div className="border-t border-gray-200 pt-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select
                                    value={filters.membership_status || ''}
                                    onChange={onFilterChange('membership_status')}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md"
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
                                    onChange={onFilterChange('local_church')}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md"
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
                                    onChange={onFilterChange('church_group')}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md"
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
                                    onChange={onFilterChange('age_group')}
                                    className="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md"
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
                                    className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
    canChangeStatus?: boolean;
}>(({ 
    member, 
    isSelected, 
    onToggleSelection, 
    onDelete, 
    onStatusChange,
    canChangeStatus = true 
}) => {
    const [isChangingStatus, setIsChangingStatus] = useState(false);
    const [showStatusDropdown, setShowStatusDropdown] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    
    // Close dropdown when clicking outside
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
    
    // Memoize computed values
    const memberName = useMemo(() => {
        return `${member.first_name || ''} ${member.last_name || ''}`.trim() || 'Unknown Member';
    }, [member.first_name, member.last_name]);

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
        } finally {
            setIsChangingStatus(false);
        }
    }, [onStatusChange, member.id, isChangingStatus]);

    return (
        <div className={`
            bg-white rounded-lg shadow-sm border transition-all duration-200 hover:shadow-md
            ${isSelected ? 'ring-2 ring-indigo-500 border-indigo-200' : 'border-gray-200 hover:border-gray-300'}
        `}>
            <div className="p-6">
                {/* Header with selection */}
                <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-3">
                        <input
                            type="checkbox"
                            checked={isSelected}
                            onChange={handleToggleSelection}
                            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
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
                    
                    {/* Status Badge with Dropdown */}
                    <div className="relative" ref={dropdownRef}>
                        <button
                            onClick={() => setShowStatusDropdown(!showStatusDropdown)}
                            disabled={isChangingStatus}
                            className={`text-xs px-3 py-1 rounded-full font-medium transition-all duration-200 ${statusConfig.color} ${
                                canChangeStatus ? 'hover:shadow-md cursor-pointer' : 'cursor-default'
                            } ${isChangingStatus ? 'opacity-50' : ''}`}
                            title={canChangeStatus ? 'Click to change status' : member.membership_status}
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

                        {/* Status Dropdown */}
                        {showStatusDropdown && canChangeStatus && !isChangingStatus && (
                            <div className="absolute right-0 top-full mt-1 w-40 bg-white rounded-md shadow-lg border border-gray-200 z-50">
                                <div className="py-1">
                                    {Object.entries(MEMBER_STATUS_CONFIG).map(([status, config]) => (
                                        <button
                                            key={status}
                                            onClick={() => handleStatusChange(status)}
                                            className={`
                                                w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center space-x-2
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

                {/* Member Details */}
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
                            <span>{memberAge} years old</span>
                        </div>
                    )}
                </div>

                {/* Church Information */}
                <div className="bg-gray-50 rounded-lg p-3 mb-4">
                    <div className="text-sm">
                        <p className="font-medium text-gray-900 truncate" title={member.local_church}>
                            {member.local_church || 'No church assigned'}
                        </p>
                        <p className="text-gray-600 truncate" title={member.church_group}>
                            {member.church_group || 'No group assigned'}
                        </p>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div className="flex space-x-2">
                        <Link
                            href={memberRoute('show', member.id)}
                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            title={`View ${memberName} details`}
                        >
                            <Eye className="w-3 h-3 mr-1" />
                            View
                        </Link>
                        
                        <Link
                            href={memberRoute('edit', member.id)}
                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            title={`Edit ${memberName}`}
                        >
                            <Edit className="w-3 h-3 mr-1" />
                            Edit
                        </Link>
                    </div>
                    
                    <button
                        onClick={handleDelete}
                        className="inline-flex items-center px-3 py-1.5 border border-red-300 rounded-md text-xs font-medium text-red-700 bg-white hover:bg-red-50 transition-colors"
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

// Members Grid Component
const MembersGrid = memo<{
    members: Member[];
    selectedMembers: number[];
    isLoading: boolean;
    onToggleSelection: (id: number) => void;
    onDelete: (member: Member) => void;
    onStatusChange?: (id: number, status: string) => void;
}>(({ 
    members, 
    selectedMembers, 
    isLoading, 
    onToggleSelection, 
    onDelete, 
    onStatusChange 
}) => {
    if (isLoading) {
        return (
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
        );
    }

    if (!members || members.length === 0) {
        return (
            <div className="text-center py-12">
                <Users className="mx-auto h-12 w-12 text-gray-400" />
                <h3 className="mt-2 text-sm font-medium text-gray-900">No members found</h3>
                <p className="mt-1 text-sm text-gray-500">
                    Get started by adding a new member to your parish.
                </p>
                <div className="mt-6">
                    <Link
                        href={safeRoute('members.create')}
                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Add Member
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {members.map((member) => (
                <MemberCard
                    key={member.id}
                    member={member}
                    isSelected={selectedMembers.includes(member.id)}
                    onToggleSelection={onToggleSelection}
                    onDelete={onDelete}
                    onStatusChange={onStatusChange}
                />
            ))}
        </div>
    );
});

MembersGrid.displayName = 'MembersGrid';

// Pagination Component
const MembersPagination = memo<{
    members: PaginatedMembers;
    filters: Filters;
}>(({ members, filters }) => {
    const buildPaginationUrl = useCallback((page: number) => {
        const params = { ...filters, page };
        return safeRoute('members.index', params);
    }, [filters]);

    const handlePageChange = useCallback((page: number) => {
        if (page < 1 || page > members.last_page) return;
        
        const url = buildPaginationUrl(page);
        router.get(url, undefined, {
            preserveState: true,
            preserveScroll: true,
            only: ['members']
        });
    }, [buildPaginationUrl, members.last_page]);

    const handlePerPageChange = useCallback((e: React.ChangeEvent<HTMLSelectElement>) => {
        const perPage = parseInt(e.target.value);
        const params = { ...filters, per_page: perPage, page: 1 };
        const url = safeRoute('members.index', params);
        
        router.get(url, undefined, {
            preserveState: true,
            preserveScroll: true,
            only: ['members']
        });
    }, [filters]);

    if (!members.data.length) {
        return null;
    }

    const startItem = members.from || 0;
    const endItem = members.to || 0;
    const totalItems = members.total || 0;

    const getVisiblePageNumbers = () => {
        const current = members.current_page;
        const last = members.last_page;
        const delta = 2;
        const range = [];
        
        for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
            range.push(i);
        }
        
        if (current - delta > 2) {
            range.unshift('...');
        }
        if (current + delta < last - 1) {
            range.push('...');
        }
        
        range.unshift(1);
        if (last !== 1) {
            range.push(last);
        }
        
        return range;
    };

    const visiblePages = getVisiblePageNumbers();

    return (
        <div className="bg-white px-4 py-3 border border-gray-200 rounded-lg">
            <div className="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
                {/* Results info and per-page selector */}
                <div className="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div className="text-sm text-gray-700">
                        Showing{' '}
                        <span className="font-medium">{startItem}</span>
                        {' '}to{' '}
                        <span className="font-medium">{endItem}</span>
                        {' '}of{' '}
                        <span className="font-medium">{totalItems}</span>
                        {' '}results
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <label htmlFor="per-page-select" className="text-sm text-gray-700">
                            Per page:
                        </label>
                        <select
                            id="per-page-select"
                            value={members.per_page}
                            onChange={handlePerPageChange}
                            className="border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value={10}>10</option>
                            <option value={25}>25</option>
                            <option value={50}>50</option>
                            <option value={100}>100</option>
                        </select>
                    </div>
                </div>

                {/* Pagination controls */}
                {members.last_page > 1 && (
                    <div className="flex items-center space-x-1">
                        {/* Previous button */}
                        <button
                            onClick={() => handlePageChange(members.current_page - 1)}
                            disabled={members.current_page <= 1}
                            className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed rounded-l-md"
                            aria-label="Previous page"
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </button>

                        {/* Page numbers */}
                        {visiblePages.map((page, index) => {
                            if (page === '...') {
                                return (
                                    <span 
                                        key={`ellipsis-${index}`}
                                        className="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                    >
                                        ...
                                    </span>
                                );
                            }

                            const pageNumber = page as number;
                            const isCurrentPage = pageNumber === members.current_page;

                            return (
                                <button
                                    key={pageNumber}
                                    onClick={() => handlePageChange(pageNumber)}
                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 ${
                                        isCurrentPage
                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                    }`}
                                    aria-label={`Page ${pageNumber}`}
                                    aria-current={isCurrentPage ? 'page' : undefined}
                                >
                                    {pageNumber}
                                </button>
                            );
                        })}

                        {/* Next button */}
                        <button
                            onClick={() => handlePageChange(members.current_page + 1)}
                            disabled={members.current_page >= members.last_page}
                            className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed rounded-r-md"
                            aria-label="Next page"
                        >
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
});

MembersPagination.displayName = 'MembersPagination';

// Enhanced debounced search function optimized for UX
const createDebouncedSearch = () => {
    return debounce((query: string, currentFilters: Filters, setLoadingFunction: (loading: boolean) => void) => {
        try {
            const cleanQuery = typeof query === 'string' ? query.trim() : '';
            
            const searchParams = {
                ...currentFilters,
                search: cleanQuery,
                page: 1 // Reset to first page when searching
            };
            
            // Filter and clean parameters
            const cleanParams = Object.entries(searchParams)
                .filter(([key, value]) => {
                    if (value === null || value === undefined) return false;
                    if (typeof value === 'string' && value.trim() === '') return false;
                    return true;
                })
                .reduce((acc, [key, value]) => {
                    acc[key] = String(value).trim();
                    return acc;
                }, {} as Record<string, string>);
            
            // Safe route generation with fallback
            const getRouteUrl = () => {
                try {
                    return typeof route === 'function' ? route('members.index', cleanParams) : '/members';
                } catch (error) {
                    console.warn('Route helper not available, using fallback URL');
                    const params = new URLSearchParams(cleanParams);
                    return `/members${params.toString() ? `?${params.toString()}` : ''}`;
                }
            };

            router.get(safeRoute('members.index', cleanParams), undefined, {
                preserveScroll: true,
                preserveState: true,
                only: ['members', 'stats'],
                onStart: () => setLoadingFunction(true),
                onFinish: () => setLoadingFunction(false),
                onError: (errors: InertiaErrors) => {
                    console.error('Search error:', errors);
                    setLoadingFunction(false);
                    if (typeof showNotification === 'function') {
                        showNotification('Search Error', 'Search failed. Please try again.', 'error', 4000);
                    }
                }
            });
        } catch (error) {
            console.error('Search error:', error);
            setLoadingFunction(false);
            if (typeof showNotification === 'function') {
                showNotification('Search Error', 'Search error occurred. Please try again.', 'error', 4000);
            }
        }
    }, 300);
};

export default function MembersIndex({ 
    auth, 
    members, 
    stats, 
    filters = {}, 
    filterOptions,
    flash 
}: MembersIndexProps) {
    // State management
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

    // Memoized debounced search function
    const debouncedSearch = useMemo(() => createDebouncedSearch(), []);

    // Search handler
    const handleSearchChange = useCallback((value: string) => {
        debouncedSearch(value, filters, setIsLoading);
    }, [debouncedSearch, filters]);

    // Filter change handler
    const handleFilterChange = useCallback((key: string) => {
        return (e: React.ChangeEvent<HTMLSelectElement>) => {
            const value = e.target.value;
            const newFilters = { ...filters, [key]: value, page: 1 };
            
            // Clean empty values
            const cleanParams = Object.entries(newFilters)
                .filter(([_, val]) => val && val !== '')
                .reduce((acc, [k, val]) => {
                    acc[k] = String(val).trim();
                    return acc;
                }, {} as Record<string, string>);

            // Safe route generation
            const getRouteUrl = () => {
                try {
                    return typeof route === 'function' ? route('members.index', cleanParams) : '/members';
                } catch (error) {
                    console.warn('Route helper not available, using fallback URL');
                    const params = new URLSearchParams(cleanParams);
                    return `/members${params.toString() ? `?${params.toString()}` : ''}`;
                }
            };

            router.get(getRouteUrl(), undefined, {
                preserveScroll: true,
                preserveState: true,
                only: ['members', 'stats'],
                onStart: () => setIsLoading(true),
                onFinish: () => setIsLoading(false),
            });
        };
    }, [filters]);

    // Clear filters
    const handleClearFilters = useCallback(() => {
        const getRouteUrl = () => {
            try {
                return typeof route === 'function' ? route('members.index') : '/members';
            } catch (error) {
                console.warn('Route helper not available, using fallback URL');
                return '/members';
            }
        };

        router.get(getRouteUrl(), undefined, {
            preserveScroll: true,
            preserveState: true,
            only: ['members', 'stats'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    }, []);

    // Refresh data
    const handleRefresh = useCallback(() => {
        router.reload({
            only: ['members', 'stats'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    }, []);

    // Toggle filters panel
    const handleToggleFilters = useCallback(() => {
        setShowFilters(prev => !prev);
    }, []);

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

    // Delete handlers
    const handleDeleteMember = useCallback((member: Member) => {
        setMemberToDelete(member);
        setShowDeleteModal(true);
    }, []);

    const confirmDelete = useCallback(() => {
        if (!memberToDelete) return;

        setIsDeleting(true);
        
        // Safe route generation
        const getRouteUrl = () => {
            try {
                                        return safeRoute('members.destroy', memberToDelete.id);
            } catch (error) {
                console.warn('Route helper not available, using fallback URL');
                return `/members/${memberToDelete.id}`;
            }
        };

        router.delete(getRouteUrl(), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setMemberToDelete(null);
                if (typeof showNotification === 'function') {
                    showNotification('Success', 'Member deleted successfully', 'success');
                }
            },
            onError: () => {
                if (typeof showNotification === 'function') {
                    showNotification('Error', 'Failed to delete member', 'error');
                }
            },
            onFinish: () => setIsDeleting(false)
        });
    }, [memberToDelete]);

    // Status change handler with real-time updates
    const handleStatusChange = useCallback(async (memberId: number, newStatus: string) => {
        try {
            // Optimistically update the member in the local state
            const currentMembers = members.data.map(member => 
                member.id === memberId 
                    ? { ...member, membership_status: newStatus }
                    : member
            );

            // Get the member for notification
            const member = members.data.find(m => m.id === memberId);
            const memberName = member ? `${member.first_name} ${member.last_name}` : 'Member';

            await new Promise((resolve, reject) => {
                router.patch(safeRoute('members.update-status', memberId), 
                    { membership_status: newStatus },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        only: ['members', 'stats'], // Refresh both members and stats
                        onSuccess: () => {
                            if (typeof showNotification === 'function') {
                                showNotification('Success', `${memberName} status updated to ${newStatus} successfully`, 'success');
                            }
                            resolve(true);
                        },
                        onError: (errors) => {
                            console.error('Status update errors:', errors);
                            if (typeof showNotification === 'function') {
                                showNotification('Error', 'Failed to update member status', 'error');
                            }
                            reject(errors);
                        }
                    }
                );
            });
        } catch (error) {
            console.error('Status change error:', error);
            throw error;
        }
    }, [members.data]);

    // Bulk delete handler
    const handleBulkDelete = useCallback(() => {
        if (selectedMembers.length === 0) return;
        setShowBulkDeleteModal(true);
    }, [selectedMembers.length]);

    const confirmBulkDelete = useCallback(() => {
        // Safe route generation
        const getRouteUrl = () => {
            try {
                return safeRoute('members.bulk-delete');
            } catch (error) {
                console.warn('Route helper not available, using fallback URL');
                return '/members/bulk-delete';
            }
        };

        router.post(getRouteUrl(), {
            member_ids: selectedMembers
        }, {
            onSuccess: () => {
                setSelectedMembers([]);
                setShowBulkDeleteModal(false);
                if (typeof showNotification === 'function') {
                    showNotification('Success', `${selectedMembers.length} members deleted successfully`, 'success');
                }
            },
            onError: () => {
                if (typeof showNotification === 'function') {
                    showNotification('Error', 'Failed to delete selected members', 'error');
                }
            }
        });
    }, [selectedMembers]);

    // Export handlers
    const handleExport = useCallback((format: 'excel' | 'pdf') => {
        const exportParams = new URLSearchParams();
        
        // Add current filters to export
        Object.entries(filters).forEach(([key, value]) => {
            if (value && value !== '') {
                exportParams.append(key, String(value));
            }
        });
        
        exportParams.append('format', format);
        
        // Safe route generation
        const getRouteUrl = () => {
            try {
                return safeRoute('members.export');
            } catch (error) {
                console.warn('Route helper not available, using fallback URL');
                return '/members/export';
            }
        };
        
        const url = getRouteUrl() + '?' + exportParams.toString();
        window.open(url, '_blank');
    }, [filters]);

    // Import handlers
    const handleImport = useCallback(() => {
        if (!importFile) return;

        const formData = new FormData();
        formData.append('file', importFile);

        setIsImporting(true);
        
        // Safe route generation
        const getRouteUrl = () => {
            try {
                return safeRoute('members.import');
            } catch (error) {
                console.warn('Route helper not available, using fallback URL');
                return '/members/import';
            }
        };
        
        router.post(getRouteUrl(), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setImportFile(null);
                if (typeof showNotification === 'function') {
                    showNotification('Success', 'Members imported successfully', 'success');
                }
            },
            onError: (errors) => {
                console.error('Import errors:', errors);
                if (typeof showNotification === 'function') {
                    showNotification('Error', 'Failed to import members', 'error');
                }
            },
            onFinish: () => setIsImporting(false)
        });
    }, [importFile]);

    // Show flash messages
    useEffect(() => {
        if (flash?.success && typeof showNotification === 'function') {
            showNotification('Success', flash.success, 'success');
        }
        if (flash?.error && typeof showNotification === 'function') {
            showNotification('Error', flash.error, 'error');
        }
    }, [flash]);

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
                        {/* Bulk Actions */}
                        {selectedMembers.length > 0 && (
                            <div className="flex items-center space-x-2">
                                <span className="text-sm text-gray-600">
                                    {selectedMembers.length} selected
                                </span>
                                <button
                                    type="button"
                                    onClick={handleBulkDelete}
                                    className="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    <Trash2 className="h-4 w-4 mr-1" />
                                    Delete Selected
                                </button>
                            </div>
                        )}

                        {/* Export Dropdown */}
                        <div className="relative inline-block text-left">
                            <button
                                type="button"
                                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                onClick={() => handleExport('excel')}
                            >
                                <Download className="h-4 w-4 mr-2" />
                                Export
                            </button>
                        </div>

                        {/* Import Button */}
                        <button
                            type="button"
                            onClick={() => setShowImportModal(true)}
                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Upload className="h-4 w-4 mr-2" />
                            Import
                        </button>

                        {/* Add Member Button */}
                        <Link
                            href={(() => {
                                try {
                                    return safeRoute('members.create');
                                } catch (error) {
                                    console.warn('Route helper not available, using fallback URL');
                                    return '/members/create';
                                }
                            })()}
                            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
                        onToggleFilters={handleToggleFilters}
                        onClearFilters={handleClearFilters}
                        onRefresh={handleRefresh}
                    />

                    {/* Selection Bar */}
                    {members.data.length > 0 && (
                        <div className="bg-white rounded-lg shadow p-4 mb-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={selectedMembers.length === members.data.length && members.data.length > 0}
                                            onChange={handleSelectAll}
                                            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
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
                                {selectedMembers.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={() => setSelectedMembers([])}
                                        className="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Clear selection
                                    </button>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Members Grid */}
                    <div className="mb-6">
                        <MembersGrid
                            members={members.data}
                            selectedMembers={selectedMembers}
                            isLoading={isLoading}
                            onToggleSelection={handleToggleSelection}
                            onDelete={handleDeleteMember}
                            onStatusChange={handleStatusChange}
                        />
                    </div>

                    {/* Pagination */}
                    <MembersPagination members={members} filters={filters} />
                </div>
            </div>

            {/* Delete Member Modal */}
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
                                                Are you sure you want to delete <strong>{memberToDelete.full_name}</strong>? 
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
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
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
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Bulk Delete Modal */}
            {showBulkDeleteModal && (
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
                                                Are you sure you want to delete {selectedMembers.length} selected members? 
                                                This action cannot be undone.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={confirmBulkDelete}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Delete {selectedMembers.length} Members
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowBulkDeleteModal(false)}
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
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
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Import Members
                                        </h3>
                                        <div className="mt-2">
                                            <p className="text-sm text-gray-500 mb-4">
                                                Upload an Excel file (.xlsx) containing member data.
                                            </p>
                                            <input
                                                type="file"
                                                accept=".xlsx,.xls"
                                                onChange={(e) => setImportFile(e.target.files?.[0] || null)}
                                                className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    onClick={handleImport}
                                    disabled={!importFile || isImporting}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
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
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
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