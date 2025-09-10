// resources/js/Pages/Members/Index.jsx
import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Search, 
    Plus, 
    Filter, 
    Download, 
    Upload, 
    Eye, 
    Edit, 
    Trash2,
    Users,
    UserCheck,
    UserPlus,
    Calendar,
    MapPin,
    Phone,
    Mail,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    RefreshCw,
    X,
    FileText,
    FileSpreadsheet,
    Loader2,
    AlertCircle,
    CheckCircle,
    Info
} from 'lucide-react';
import { PageProps } from '@/types';

// Define interfaces for type safety
interface Member {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    full_name: string;
    date_of_birth: string;
    age: number;
    gender: string;
    phone?: string;
    email?: string;
    id_number?: string;
    local_church: string;
    church_group: string;
    membership_status: string;
    membership_date: string;
    residence?: string;
    occupation?: string;
    family?: {
        id: number;
        family_name: string;
        head_of_family: string;
    };
    created_at: string;
    updated_at: string;
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

interface Stats {
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

interface FilterOption {
    value: string;
    label: string;
}

interface FilterOptions {
    local_churches?: string[];
    church_groups?: FilterOption[];
    membership_statuses?: FilterOption[];
    genders?: FilterOption[];
    age_groups?: FilterOption[];
}

interface Filters {
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

interface MembersIndexProps extends PageProps {
    members: PaginatedMembers;
    stats: Stats;
    filters: Filters;
    filterOptions: FilterOptions;
}

export default function MembersIndex({ 
    auth, 
    members, 
    stats, 
    filters = {}, 
    filterOptions,
    flash 
}: MembersIndexProps) {
    const [selectedMembers, setSelectedMembers] = useState<number[]>([]);
    const [showFilters, setShowFilters] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [isImporting, setIsImporting] = useState(false);
    const [isExporting, setIsExporting] = useState(false);
    const [importProgress, setImportProgress] = useState(0);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [memberToDelete, setMemberToDelete] = useState<Member | null>(null);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    // Handle flash messages
    useEffect(() => {
        if (flash?.success) {
            window.dispatchEvent(new CustomEvent('flash-message', {
                detail: { type: 'success', message: flash.success }
            }));
        }
        if (flash?.error) {
            window.dispatchEvent(new CustomEvent('flash-message', {
                detail: { type: 'error', message: flash.error }
            }));
        }
    }, [flash]);

    // Form for handling filters
    const { data, setData, get, processing } = useForm({
        search: filters.search || '',
        local_church: filters.local_church || '',
        church_group: filters.church_group || '',
        membership_status: filters.membership_status || '',
        gender: filters.gender || '',
        age_group: filters.age_group || '',
        sort: filters.sort || 'last_name',
        direction: filters.direction || 'asc',
        per_page: filters.per_page || 15,
    });

    // Safe access to data with fallbacks and better performance
    const membersData = useMemo(() => members?.data || [], [members?.data]);
    const currentPage = useMemo(() => members?.current_page || 1, [members?.current_page]);
    const lastPage = useMemo(() => members?.last_page || 1, [members?.last_page]);
    const total = useMemo(() => members?.total || 0, [members?.total]);
    const from = useMemo(() => members?.from || 0, [members?.from]);
    const to = useMemo(() => members?.to || 0, [members?.to]);

    const safeStats = useMemo(() => ({
        total_members: stats?.total_members || 0,
        active_members: stats?.active_members || (stats?.by_status?.active || 0),
        new_this_month: stats?.new_this_month || 0,
        by_church: stats?.by_church || {},
        by_group: stats?.by_group || {},
        by_status: {
            active: stats?.by_status?.active || 0,
            inactive: stats?.by_status?.inactive || 0,
            transferred: stats?.by_status?.transferred || 0,
            deceased: stats?.by_status?.deceased || 0,
            ...stats?.by_status,
        },
        by_gender: stats?.by_gender || {},
        statistics: {
            total_members: stats?.statistics?.total_members || stats?.total_members || 0,
            active_members: stats?.statistics?.active_members || stats?.active_members || (stats?.by_status?.active || 0),
            inactive_members: stats?.statistics?.inactive_members || (stats?.by_status?.inactive || 0),
            transferred_members: stats?.statistics?.transferred_members || (stats?.by_status?.transferred || 0),
            deceased_members: stats?.statistics?.deceased_members || (stats?.by_status?.deceased || 0),
            active_percentage: stats?.statistics?.active_percentage || 0,
            new_this_month: stats?.statistics?.new_this_month || stats?.new_this_month || 0,
            male_members: stats?.statistics?.male_members || (stats?.by_gender?.male || stats?.by_gender?.Male || 0),
            female_members: stats?.statistics?.female_members || (stats?.by_gender?.female || stats?.by_gender?.Female || 0),
            ...stats?.statistics,
        },
    }), [stats]);

    const safeFilterOptions = useMemo(() => ({
        local_churches: filterOptions?.local_churches || [],
        church_groups: filterOptions?.church_groups || [],
        membership_statuses: filterOptions?.membership_statuses || [],
        genders: filterOptions?.genders || [],
        age_groups: filterOptions?.age_groups || [],
    }), [filterOptions]);

    // Optimized debounced search function
    const debouncedSearch = useCallback(
        debounce((query: string) => {
            setData('search', query);
            setIsLoading(true);
            get(route('members.index'), {
                preserveState: true,
                preserveScroll: true,
                only: ['members', 'stats'],
                onFinish: () => setIsLoading(false),
            });
        }, 500), // Increased to 500ms for better performance
        [setData, get]
    );

    // Handle search input change
    const handleSearchChange = useCallback((value: string) => {
        setSearchQuery(value);
        debouncedSearch(value);
    }, [debouncedSearch]);

    // Handle search form submission
    const handleSearch = useCallback((e: React.FormEvent) => {
        e.preventDefault();
        setData('search', searchQuery);
        setIsLoading(true);
        get(route('members.index'), {
            preserveState: true,
            onFinish: () => setIsLoading(false),
        });
    }, [searchQuery, setData, get]);

    // Optimized filter changes
    const handleFilterChange = useCallback((key: string, value: string) => {
        setData(key as keyof typeof data, value);
        setIsLoading(true);
        get(route('members.index'), {
            preserveState: true,
            preserveScroll: true,
            only: ['members', 'stats'],
            onFinish: () => setIsLoading(false),
        });
    }, [setData, get]);

    // Handle sorting
    const handleSort = useCallback((field: string) => {
        const newDirection = data.sort === field && data.direction === 'asc' ? 'desc' : 'asc';
        setData({
            ...data,
            sort: field,
            direction: newDirection,
        });
        setIsLoading(true);
        get(route('members.index'), {
            preserveState: true,
            onFinish: () => setIsLoading(false),
        });
    }, [data, setData, get]);

    // Handle pagination
    const handlePageChange = useCallback((page: number) => {
        if (page >= 1 && page <= lastPage) {
            setIsLoading(true);
            get(route('members.index', { ...data, page }), {
                preserveState: true,
                onFinish: () => setIsLoading(false),
            });
        }
    }, [data, lastPage, get]);

    // Handle member selection
    const handleSelectMember = useCallback((memberId: number) => {
        setSelectedMembers(prev => 
            prev.includes(memberId) 
                ? prev.filter(id => id !== memberId)
                : [...prev, memberId]
        );
    }, []);

    const handleSelectAll = useCallback(() => {
        if (selectedMembers.length === membersData.length && membersData.length > 0) {
            setSelectedMembers([]);
        } else {
            setSelectedMembers(membersData.map(member => member.id));
        }
    }, [selectedMembers.length, membersData]);

    // Clear filters
    const clearFilters = useCallback(() => {
        setSearchQuery('');
        setData({
            search: '',
            local_church: '',
            church_group: '',
            membership_status: '',
            gender: '',
            age_group: '',
            sort: 'last_name',
            direction: 'asc',
            per_page: 15,
        });
        setIsLoading(true);
        get(route('members.index'), {
            preserveState: true,
            onFinish: () => setIsLoading(false),
        });
    }, [setData, get]);

    // Export functionality
    const handleExport = useCallback(async (format: 'csv' | 'excel' | 'pdf') => {
        setIsExporting(true);
        
        try {
            // Create clean parameters object
            const exportParams = {
                format,
                search: filters?.search || '',
                local_church: filters?.local_church || '',
                church_group: filters?.church_group || '',
                membership_status: filters?.membership_status || '',
                gender: filters?.gender || '',
                age_group: filters?.age_group || '',
                // Use proper sort field names instead of the sort function
                sort_by: filters?.sort || 'last_name',
                sort_direction: filters?.direction || 'asc',
                selected_members: selectedMembers.join(','),
            };

            // Build query string manually to ensure proper encoding
            const queryString = Object.entries(exportParams)
                .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
                .join('&');

            const response = await fetch(`/members/export?${queryString}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/octet-stream',
                },
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `members_export_${new Date().toISOString().split('T')[0]}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                const errorText = await response.text();
                console.error('Export failed:', errorText);
                throw new Error('Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            alert('Export failed. Please try again.');
        } finally {
            setIsExporting(false);
        }
    }, [filters, selectedMembers]);

    // Import functionality
    const handleImport = useCallback(async () => {
        if (!importFile) return;

        setIsImporting(true);
        setImportProgress(0);

        const formData = new FormData();
        formData.append('file', importFile);

        try {
            const response = await fetch(route('members.import'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const result = await response.json();

            if (response.ok) {
                setImportProgress(100);
                setTimeout(() => {
                    setShowImportModal(false);
                    setImportFile(null);
                    setImportProgress(0);
                    refreshData();
                    window.dispatchEvent(new CustomEvent('flash-message', {
                        detail: { 
                            type: 'success', 
                            message: `Successfully imported ${result.imported || 0} members!` 
                        }
                    }));
                }, 1000);
            } else {
                throw new Error(result.message || 'Import failed');
            }
        } catch (error) {
            window.dispatchEvent(new CustomEvent('flash-message', {
                detail: { 
                    type: 'error', 
                    message: error instanceof Error ? error.message : 'Import failed. Please try again.' 
                }
            }));
        } finally {
            setIsImporting(false);
        }
    }, [importFile]);

    // Handle status change
    const handleStatusChange = useCallback(async (memberId: number, newStatus: string) => {
        router.post(route('quick.member-status-toggle'), {
            member_id: memberId,
            status: newStatus
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                // Refresh the members data
                router.reload({ only: ['members'] });
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0] || 'Failed to update member status';
                window.dispatchEvent(new CustomEvent('flash-message', {
                    detail: { 
                        type: 'error', 
                        message: errorMessage
                    }
                }));
            }
        });
    }, []);

    // Delete member
    const handleDeleteMember = useCallback((member: Member) => {
        router.delete(route('members.destroy', member.id), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setMemberToDelete(null);
                window.dispatchEvent(new CustomEvent('flash-message', {
                    detail: { type: 'success', message: 'Member deleted successfully!' }
                }));
            },
            onError: () => {
                window.dispatchEvent(new CustomEvent('flash-message', {
                    detail: { type: 'error', message: 'Failed to delete member. Please try again.' }
                }));
            }
        });
    }, []);

    // Bulk delete
    const handleBulkDelete = useCallback(() => {
        if (selectedMembers.length === 0) return;

        if (!confirm(`Are you sure you want to delete ${selectedMembers.length} selected members?`)) {
            return;
        }

        router.post(route('members.bulk-delete'), 
            { member_ids: selectedMembers },
            {
                onSuccess: () => {
                    setSelectedMembers([]);
                    window.dispatchEvent(new CustomEvent('flash-message', {
                        detail: { type: 'success', message: `Successfully deleted ${selectedMembers.length} members!` }
                    }));
                },
                onError: () => {
                    window.dispatchEvent(new CustomEvent('flash-message', {
                        detail: { type: 'error', message: 'Failed to delete selected members. Please try again.' }
                    }));
                }
            }
        );
    }, [selectedMembers]);

    // Utility functions
    const getStatusBadgeColor = useCallback((status: string) => {
        switch (status) {
            case 'active': return 'bg-green-100 text-green-800 border-green-200';
            case 'inactive': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'transferred': return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'deceased': return 'bg-gray-100 text-gray-800 border-gray-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    }, []);

    const getGroupBadgeColor = useCallback((group: string) => {
        switch (group) {
            case 'PMC': return 'bg-purple-100 text-purple-800 border-purple-200';
            case 'Youth': return 'bg-indigo-100 text-indigo-800 border-indigo-200';
            case 'Young Parents': return 'bg-pink-100 text-pink-800 border-pink-200';
            case 'C.W.A': return 'bg-rose-100 text-rose-800 border-rose-200';
            case 'CMA': return 'bg-orange-100 text-orange-800 border-orange-200';
            case 'Choir': return 'bg-teal-100 text-teal-800 border-teal-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    }, []);

    // Refresh data
    const refreshData = useCallback(() => {
        setIsLoading(true);
        router.reload({
            onFinish: () => setIsLoading(false),
        });
    }, []);

    // Download import template
    const downloadTemplate = useCallback(async () => {
        try {
            const response = await fetch(route('members.import-template'), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'members_import_template.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }
        } catch (error) {
            console.error('Failed to download template:', error);
        }
    }, []);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Parish Members
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage and view all parish members ({total.toLocaleString()} total)
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={refreshData}
                            disabled={isLoading}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
                        >
                            <RefreshCw className={`w-4 h-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
                            Refresh
                        </button>
                        <Link
                            href={route('members.create')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition-colors"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Add Member
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Members" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Enhanced Statistics Cards with Status Breakdown */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-blue-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Members
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {safeStats.total_members.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UserCheck className="h-8 w-8 text-green-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Active Members
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {(safeStats.by_status?.active || safeStats.active_members || 0).toLocaleString()}
                                            </dd>
                                            <dd className="text-xs text-green-600 font-medium">
                                                {safeStats.total_members > 0 
                                                    ? `${Math.round(((safeStats.by_status?.active || safeStats.active_members || 0) / safeStats.total_members) * 100)}% active` 
                                                    : '0% active'
                                                }
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-yellow-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Inactive Members
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {(safeStats.by_status?.inactive || 0).toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-blue-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Transferred
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {(safeStats.by_status?.transferred || 0).toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-gray-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Deceased
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {(safeStats.by_status?.deceased || 0).toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Additional Quick Stats Row */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UserPlus className="h-8 w-8 text-purple-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                New This Month
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {safeStats.new_this_month.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Calendar className="h-8 w-8 text-orange-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Church Groups
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {Object.keys(safeStats.by_group).length}
                                            </dd>
                                            <dd className="text-xs text-orange-600 font-medium">
                                                Active groups
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-indigo-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Churches
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {Object.keys(safeStats.by_church).length}
                                            </dd>
                                            <dd className="text-xs text-indigo-600 font-medium">
                                                Local churches
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Main Content */}
                    <div className="bg-white shadow-sm rounded-lg border border-gray-200">
                        {/* Search and Filters */}
                        <div className="p-6 border-b border-gray-200 bg-gray-50">
                            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                                {/* Enhanced Search */}
                                <div className="flex-1 max-w-lg">
                                    <form onSubmit={handleSearch} className="flex">
                                        <div className="relative flex-grow">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <Search className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input
                                                type="text"
                                                value={searchQuery}
                                                onChange={(e) => handleSearchChange(e.target.value)}
                                                placeholder="Search by name, phone, email, ID..."
                                                className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-l-lg focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            />
                                            {searchQuery && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleSearchChange('')}
                                                    className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                                >
                                                    <X className="h-4 w-4 text-gray-400 hover:text-gray-600" />
                                                </button>
                                            )}
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={processing || isLoading}
                                            className="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center"
                                        >
                                            {isLoading ? (
                                                <Loader2 className="w-4 h-4 animate-spin" />
                                            ) : (
                                                'Search'
                                            )}
                                        </button>
                                    </form>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex items-center space-x-3">
                                    <button
                                        onClick={() => setShowFilters(!showFilters)}
                                        className={`inline-flex items-center px-3 py-2 border rounded-lg text-sm font-medium transition-colors ${
                                            showFilters 
                                                ? 'border-blue-500 text-blue-700 bg-blue-50' 
                                                : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
                                        }`}
                                    >
                                        <Filter className="w-4 h-4 mr-2" />
                                        Filters
                                        {Object.values(data).some(val => val && val !== 'last_name' && val !== 'asc' && val !== 15) && (
                                            <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Active
                                            </span>
                                        )}
                                    </button>
                                    
                                    {/* Export Dropdown */}
                                    <div className="relative group">
                                        <button
                                            disabled={isExporting}
                                            className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
                                        >
                                            {isExporting ? (
                                                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                            ) : (
                                                <Download className="w-4 h-4 mr-2" />
                                            )}
                                            Export
                                        </button>
                                        <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                                            <div className="py-1">
                                                <button
                                                    onClick={() => handleExport('csv')}
                                                    className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                                >
                                                    <FileSpreadsheet className="w-4 h-4 mr-2" />
                                                    Export as CSV
                                                </button>
                                                <button
                                                    onClick={() => handleExport('excel')}
                                                    className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                                >
                                                    <FileSpreadsheet className="w-4 h-4 mr-2" />
                                                    Export as Excel
                                                </button>
                                                <button
                                                    onClick={() => handleExport('pdf')}
                                                    className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                                >
                                                    <FileText className="w-4 h-4 mr-2" />
                                                    Export as PDF
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        onClick={() => setShowImportModal(true)}
                                        className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                                    >
                                        <Upload className="w-4 h-4 mr-2" />
                                        Import
                                    </button>

                                    {selectedMembers.length > 0 && (
                                        <button
                                            onClick={handleBulkDelete}
                                            className="inline-flex items-center px-3 py-2 border border-red-300 rounded-lg text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition-colors"
                                        >
                                            <Trash2 className="w-4 h-4 mr-2" />
                                            Delete ({selectedMembers.length})
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Enhanced Filters Panel */}
                            {showFilters && (
                                <div className="mt-6 p-4 bg-white rounded-lg border border-gray-200">
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Local Church
                                            </label>
                                            <select
                                                value={data.local_church}
                                                onChange={(e) => handleFilterChange('local_church', e.target.value)}
                                                className="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            >
                                                <option value="">All Churches</option>
                                                {safeFilterOptions.local_churches.map(church => (
                                                    <option key={church} value={church}>
                                                        {church}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Church Group
                                            </label>
                                            <select
                                                value={data.church_group}
                                                onChange={(e) => handleFilterChange('church_group', e.target.value)}
                                                className="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            >
                                                <option value="">All Groups</option>
                                                {safeFilterOptions.church_groups.map(group => (
                                                    <option key={group.value} value={group.value}>
                                                        {group.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Membership Status
                                            </label>
                                            <select
                                                value={data.membership_status}
                                                onChange={(e) => handleFilterChange('membership_status', e.target.value)}
                                                className="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            >
                                                <option value="">All Statuses</option>
                                                {safeFilterOptions.membership_statuses.map(status => (
                                                    <option key={status.value} value={status.value}>
                                                        {status.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Gender
                                            </label>
                                            <select
                                                value={data.gender}
                                                onChange={(e) => handleFilterChange('gender', e.target.value)}
                                                className="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            >
                                                <option value="">All Genders</option>
                                                {safeFilterOptions.genders.map(gender => (
                                                    <option key={gender.value} value={gender.value}>
                                                        {gender.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    <div className="mt-4 flex justify-between items-center">
                                        <div className="text-sm text-gray-500">
                                            {Object.values(data).some(val => val && val !== 'last_name' && val !== 'asc' && val !== 15) && 
                                                'Filters applied - showing filtered results'
                                            }
                                        </div>
                                        <button
                                            onClick={clearFilters}
                                            className="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
                                        >
                                            Clear All Filters
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Results Summary */}
                        {(searchQuery || Object.values(data).some(val => val && val !== 'last_name' && val !== 'asc' && val !== 15)) && (
                            <div className="px-6 py-3 bg-blue-50 border-b border-blue-200">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center text-sm text-blue-700">
                                        <Info className="w-4 h-4 mr-2" />
                                        <span>
                                            {searchQuery && `Searching for "${searchQuery}" • `}
                                            Showing {from.toLocaleString()} to {to.toLocaleString()} of {total.toLocaleString()} results
                                        </span>
                                    </div>
                                    {selectedMembers.length > 0 && (
                                        <span className="text-sm text-blue-600 font-medium">
                                            {selectedMembers.length} selected
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Enhanced Members Table */}
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                checked={selectedMembers.length === membersData.length && membersData.length > 0}
                                                onChange={handleSelectAll}
                                                className="rounded border-gray-300 focus:ring-blue-500"
                                            />
                                        </th>
                                        <th 
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                                            onClick={() => handleSort('last_name')}
                                        >
                                            <div className="flex items-center">
                                                Name
                                                {data.sort === 'last_name' && (
                                                    <span className="ml-1">
                                                        {data.direction === 'asc' ? '↑' : '↓'}
                                                    </span>
                                                )}
                                            </div>
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contact & Location
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Church Info
                                        </th>
                                        <th 
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                                            onClick={() => handleSort('membership_date')}
                                        >
                                            <div className="flex items-center">
                                                Membership
                                                {data.sort === 'membership_date' && (
                                                    <span className="ml-1">
                                                        {data.direction === 'asc' ? '↑' : '↓'}
                                                    </span>
                                                )}
                                            </div>
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {membersData.length > 0 ? (
                                        membersData.map((member) => (
                                            <tr key={member.id} className="hover:bg-gray-50 transition-colors">
                                                <td className="px-6 py-4">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedMembers.includes(member.id)}
                                                        onChange={() => handleSelectMember(member.id)}
                                                        className="rounded border-gray-300 focus:ring-blue-500"
                                                    />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {member.full_name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            Age: {member.age} • {member.gender}
                                                            {member.id_number && (
                                                                <span className="ml-2">ID: {member.id_number}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        {member.phone && (
                                                            <div className="flex items-center text-sm text-gray-600">
                                                                <Phone className="w-3 h-3 mr-1 flex-shrink-0" />
                                                                <a href={`tel:${member.phone}`} className="hover:text-blue-600 transition-colors">
                                                                    {member.phone}
                                                                </a>
                                                            </div>
                                                        )}
                                                        {member.email && (
                                                            <div className="flex items-center text-sm text-gray-600">
                                                                <Mail className="w-3 h-3 mr-1 flex-shrink-0" />
                                                                <a href={`mailto:${member.email}`} className="hover:text-blue-600 transition-colors">
                                                                    {member.email}
                                                                </a>
                                                            </div>
                                                        )}
                                                        {member.residence && (
                                                            <div className="flex items-center text-sm text-gray-500">
                                                                <MapPin className="w-3 h-3 mr-1 flex-shrink-0" />
                                                                <span className="truncate">{member.residence}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-2">
                                                        <div className="flex items-center text-sm text-gray-600">
                                                            <MapPin className="w-3 h-3 mr-1 flex-shrink-0" />
                                                            {member.local_church}
                                                        </div>
                                                        <span className={`inline-flex px-2 py-1 text-xs rounded-full border ${getGroupBadgeColor(member.church_group)}`}>
                                                            {member.church_group}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm text-gray-900">
                                                        {new Date(member.membership_date).toLocaleDateString()}
                                                    </div>
                                                    {member.family && (
                                                        <div className="text-sm text-gray-500">
                                                            Family: {member.family.family_name}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex px-2 py-1 text-xs rounded-full border ${getStatusBadgeColor(member.membership_status)}`}>
                                                        {member.membership_status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end space-x-2">
                                                        {/* Status Change Dropdown */}
                                                        <div className="relative">
                                                            <select
                                                                value={member.membership_status}
                                                                onChange={(e) => handleStatusChange(member.id, e.target.value)}
                                                                className="text-xs px-2 py-1 rounded border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                                title="Change Status"
                                                            >
                                                                <option value="active">Active</option>
                                                                <option value="inactive">Inactive</option>
                                                                <option value="transferred">Transferred</option>
                                                                <option value="deceased">Deceased</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <Link
                                                            href={route('members.show', member.id)}
                                                            className="text-blue-600 hover:text-blue-900 transition-colors p-1"
                                                            title="View Member"
                                                        >
                                                            <Eye className="w-4 h-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('members.edit', member.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 transition-colors p-1"
                                                            title="Edit Member"
                                                        >
                                                            <Edit className="w-4 h-4" />
                                                        </Link>
                                                        <button 
                                                            onClick={() => {
                                                                setMemberToDelete(member);
                                                                setShowDeleteModal(true);
                                                            }}
                                                            className="text-red-600 hover:text-red-900 transition-colors p-1"
                                                            title="Delete Member"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={7} className="px-6 py-12 text-center">
                                                <div className="flex flex-col items-center justify-center">
                                                    <Users className="w-16 h-16 text-gray-400 mb-4" />
                                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                                        {searchQuery || Object.values(data).some(val => val && val !== 'last_name' && val !== 'asc' && val !== 15)
                                                            ? 'No members found'
                                                            : 'No members yet'
                                                        }
                                                    </h3>
                                                    <p className="text-sm text-gray-500 mb-6 max-w-sm">
                                                        {searchQuery || Object.values(data).some(val => val && val !== 'last_name' && val !== 'asc' && val !== 15)
                                                            ? 'Try adjusting your search criteria or filters to find different results'
                                                            : 'Get started by adding your first parish member to the system'
                                                        }
                                                    </p>
                                                    {(!searchQuery && !Object.values(data).some(val => val && val !== 'last_name' && val !== 'asc' && val !== 15)) && (
                                                        <Link
                                                            href={route('members.create')}
                                                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition-colors"
                                                        >
                                                            <Plus className="w-4 h-4 mr-2" />
                                                            Add Your First Member
                                                        </Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Enhanced Pagination */}
                        {total > 0 && (
                            <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <div className="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
                                    <div className="flex items-center space-x-4">
                                        <div className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{from.toLocaleString()}</span> to{' '}
                                            <span className="font-medium">{to.toLocaleString()}</span> of{' '}
                                            <span className="font-medium">{total.toLocaleString()}</span> results
                                        </div>
                                        
                                        <div className="flex items-center space-x-2">
                                            <span className="text-sm text-gray-700">Show:</span>
                                            <select
                                                value={data.per_page}
                                                onChange={(e) => handleFilterChange('per_page', e.target.value)}
                                                className="border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="10">10</option>
                                                <option value="15">15</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>
                                    </div>

                                    {/* Pagination controls */}
                                    <div className="flex items-center space-x-1">
                                        <button
                                            onClick={() => handlePageChange(1)}
                                            disabled={currentPage === 1 || isLoading}
                                            className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                            title="First page"
                                        >
                                            <ChevronsLeft className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={() => handlePageChange(currentPage - 1)}
                                            disabled={currentPage === 1 || isLoading}
                                            className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                            title="Previous page"
                                        >
                                            <ChevronLeft className="w-4 h-4" />
                                        </button>
                                        
                                        <div className="flex items-center space-x-1">
                                            {Array.from({ length: Math.min(5, lastPage) }, (_, i) => {
                                                let pageNum;
                                                if (lastPage <= 5) {
                                                    pageNum = i + 1;
                                                } else if (currentPage <= 3) {
                                                    pageNum = i + 1;
                                                } else if (currentPage >= lastPage - 2) {
                                                    pageNum = lastPage - 4 + i;
                                                } else {
                                                    pageNum = currentPage - 2 + i;
                                                }
                                                
                                                return (
                                                    <button
                                                        key={pageNum}
                                                        onClick={() => handlePageChange(pageNum)}
                                                        disabled={isLoading}
                                                        className={`px-3 py-2 text-sm font-medium rounded transition-colors ${
                                                            pageNum === currentPage
                                                                ? 'bg-blue-600 text-white'
                                                                : 'text-gray-700 hover:bg-gray-100'
                                                        } disabled:opacity-50 disabled:cursor-not-allowed`}
                                                    >
                                                        {pageNum}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        
                                        <button
                                            onClick={() => handlePageChange(currentPage + 1)}
                                            disabled={currentPage === lastPage || isLoading}
                                            className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                            title="Next page"
                                        >
                                            <ChevronRight className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={() => handlePageChange(lastPage)}
                                            disabled={currentPage === lastPage || isLoading}
                                            className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                            title="Last page"
                                        >
                                            <ChevronsRight className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Import Modal */}
            {showImportModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                    <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900">Import Members</h3>
                                <button
                                    onClick={() => setShowImportModal(false)}
                                    className="text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                            
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Select CSV File
                                    </label>
                                    <input
                                        type="file"
                                        accept=".csv"
                                        onChange={(e) => setImportFile(e.target.files?.[0] || null)}
                                        className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors"
                                    />
                                </div>
                                
                                {importProgress > 0 && (
                                    <div>
                                        <div className="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Importing...</span>
                                            <span>{importProgress}%</span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${importProgress}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                )}
                                
                                <div className="text-sm text-gray-500">
                                    <p className="mb-2">📋 Import Guidelines:</p>
                                    <ul className="list-disc list-inside space-y-1">
                                        <li>File must be in CSV format</li>
                                        <li>First row should contain column headers</li>
                                        <li>Required fields: first_name, last_name, local_church, church_group</li>
                                    </ul>
                                </div>
                                
                                <div className="flex items-center space-x-2">
                                    <button
                                        onClick={downloadTemplate}
                                        className="text-sm text-blue-600 hover:text-blue-800 underline transition-colors"
                                    >
                                        Download Template
                                    </button>
                                </div>
                            </div>
                            
                            <div className="flex space-x-3 mt-6">
                                <button
                                    onClick={() => setShowImportModal(false)}
                                    className="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={handleImport}
                                    disabled={!importFile || isImporting}
                                    className="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
                                >
                                    {isImporting ? (
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                    ) : (
                                        'Import Members'
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {showDeleteModal && memberToDelete && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                    <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                        <div className="p-6">
                            <div className="flex items-center mb-4">
                                <div className="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <AlertCircle className="w-6 h-6 text-red-600" />
                                </div>
                                <div className="ml-4">
                                    <h3 className="text-lg font-medium text-gray-900">Delete Member</h3>
                                    <p className="text-sm text-gray-500">This action cannot be undone.</p>
                                </div>
                            </div>
                            
                            <p className="text-sm text-gray-700 mb-6">
                                Are you sure you want to delete <strong>{memberToDelete.full_name}</strong>? 
                                This will permanently remove all their information from the system.
                            </p>
                            
                            <div className="flex space-x-3">
                                <button
                                    onClick={() => {
                                        setShowDeleteModal(false);
                                        setMemberToDelete(null);
                                    }}
                                    className="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={() => handleDeleteMember(memberToDelete)}
                                    className="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                                >
                                    Delete Member
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

// Utility function for debouncing
function debounce<T extends (...args: any[]) => void>(func: T, wait: number): T {
    let timeout: NodeJS.Timeout;
    return ((...args: any[]) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    }) as T;
}