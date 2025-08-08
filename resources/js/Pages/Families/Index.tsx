// resources/js/Pages/Members/Create.jsx
import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Plus, 
    Search, 
    Filter, 
    Users, 
    MapPin, 
    Phone, 
    Mail, 
    Eye, 
    Edit, 
    Trash2,
    Calendar,
    TrendingUp,
    Home,
    ChevronDown,
    X,
    Download,
    RefreshCw
} from 'lucide-react';

interface HeadOfFamily {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    full_name: string;
    phone?: string;
    email?: string;
    membership_status?: string;
}

interface Creator {
    id: number;
    name: string;
    email: string;
}

interface Family {
    id: number;
    family_name: string;
    family_code?: string;
    address?: string;
    phone?: string;
    email?: string;
    deanery?: string;
    parish?: string;
    parish_section?: string;
    head_of_family_id?: number;
    created_by?: number;
    created_at: string;
    updated_at: string;
    members_count: number;
    active_members_count: number;
    head_of_family?: HeadOfFamily | null;
    creator?: Creator | null;
}

interface Stats {
    total_families: number;
    families_with_members: number;
    families_with_active_members: number;
    new_this_month: number;
    new_this_year: number;
    total_members_in_families: number;
    average_family_size: number;
    by_parish_section: Record<string, number>;
    by_deanery: Record<string, number>;
}

interface FilterOptions {
    parish_sections: string[];
    deaneries: string[];
    parishes: string[];
    years: number[];
}

interface Filters {
    search?: string;
    parish_section?: string;
    deanery?: string;
    parish?: string;
    year?: number;
    min_members?: number;
    max_members?: number;
    sort?: string;
    direction?: string;
    per_page?: number;
}

interface FamiliesIndexProps {
    families: {
        data: Family[];
        links: any;
        meta: any;
    };
    stats: Stats;
    filters: Filters;
    filterOptions: FilterOptions;
    success?: string;
    error?: string;
    auth: {
        user: any;
    };
}

export default function FamiliesIndex({ 
    families, 
    stats, 
    filters, 
    filterOptions, 
    success, 
    error,
    auth 
}: FamiliesIndexProps) {
    const [showFilters, setShowFilters] = useState(false);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedFilters, setSelectedFilters] = useState({
        parish_section: filters.parish_section || '',
        deanery: filters.deanery || '',
        parish: filters.parish || '',
        year: filters.year || '',
        min_members: filters.min_members || '',
        max_members: filters.max_members || '',
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('families.index'), {
            ...filters,
            search: searchTerm,
            page: 1, // Reset to first page when searching
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (key: string, value: string) => {
        setSelectedFilters(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const applyFilters = () => {
        router.get(route('families.index'), {
            ...filters,
            ...selectedFilters,
            search: searchTerm,
            page: 1, // Reset to first page when filtering
        }, {
            preserveState: true,
            replace: true,
        });
        setShowFilters(false);
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedFilters({
            parish_section: '',
            deanery: '',
            parish: '',
            year: '',
            min_members: '',
            max_members: '',
        });
        router.get(route('families.index'), {}, {
            preserveState: true,
            replace: true,
        });
        setShowFilters(false);
    };

    const handleSort = (field: string) => {
        const direction = filters.sort === field && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(route('families.index'), {
            ...filters,
            sort: field,
            direction: direction,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (familyId: number) => {
        if (confirm('Are you sure you want to delete this family? All members will be removed from the family.')) {
            router.delete(route('families.destroy', familyId), {
                onSuccess: () => {
                    // Success message will be handled by the flash message
                },
                onError: () => {
                    // Error message will be handled by the flash message
                }
            });
        }
    };

    const formatDate = (dateString: string) => {
        try {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (error) {
            return 'Invalid date';
        }
    };

    const getSortIcon = (field: string) => {
        if (filters.sort !== field) return null;
        return filters.direction === 'asc' ? '↑' : '↓';
    };

    // Safe route helper
    const safeRoute = (name: string, params?: any) => {
        try {
            return route(name, params);
        } catch (error) {
            console.warn(`Route '${name}' not found`);
            return '#';
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <Home className="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h2 className="font-bold text-2xl text-gray-800 leading-tight">
                                Families Management
                            </h2>
                            <p className="text-sm text-gray-600">
                                Manage parish family records and relationships
                            </p>
                        </div>
                    </div>
                    <Link
                        href={safeRoute('families.create')}
                        className="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2.5 rounded-lg flex items-center space-x-2 transition-all duration-200 shadow-sm hover:shadow-md"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Add Family</span>
                    </Link>
                </div>
            }
        >
            <Head title="Families Management" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Success/Error Messages */}
                    {success && (
                        <div className="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                            {success}
                        </div>
                    )}
                    
                    {error && (
                        <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                            {error}
                        </div>
                    )}

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <Home className="w-6 h-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Families</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.total_families}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <Users className="w-6 h-6 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">With Active Members</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.families_with_active_members}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-purple-100 rounded-lg">
                                    <TrendingUp className="w-6 h-6 text-purple-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">New This Year</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.new_this_year}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-amber-100 rounded-lg">
                                    <Users className="w-6 h-6 text-amber-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Avg. Family Size</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.average_family_size}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
                        <div className="p-6">
                            <div className="flex flex-col lg:flex-row gap-4">
                                {/* Search */}
                                <form onSubmit={handleSearch} className="flex-1">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                        <input
                                            type="text"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            placeholder="Search families by name, code, address, phone..."
                                            className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </form>

                                {/* Filter Toggle */}
                                <button
                                    onClick={() => setShowFilters(!showFilters)}
                                    className="flex items-center space-x-2 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    <Filter className="w-5 h-5" />
                                    <span>Filters</span>
                                    <ChevronDown className={`w-4 h-4 transition-transform ${showFilters ? 'rotate-180' : ''}`} />
                                </button>
                            </div>

                            {/* Expanded Filters */}
                            {showFilters && (
                                <div className="mt-6 pt-6 border-t border-gray-200">
                                    <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Parish Section
                                            </label>
                                            <select
                                                value={selectedFilters.parish_section}
                                                onChange={(e) => handleFilterChange('parish_section', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Sections</option>
                                                {filterOptions.parish_sections.map((section) => (
                                                    <option key={section} value={section}>
                                                        {section}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Deanery
                                            </label>
                                            <select
                                                value={selectedFilters.deanery}
                                                onChange={(e) => handleFilterChange('deanery', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Deaneries</option>
                                                {filterOptions.deaneries.map((deanery) => (
                                                    <option key={deanery} value={deanery}>
                                                        {deanery}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Parish
                                            </label>
                                            <select
                                                value={selectedFilters.parish}
                                                onChange={(e) => handleFilterChange('parish', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Parishes</option>
                                                {filterOptions.parishes.map((parish) => (
                                                    <option key={parish} value={parish}>
                                                        {parish}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Year
                                            </label>
                                            <select
                                                value={selectedFilters.year}
                                                onChange={(e) => handleFilterChange('year', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Years</option>
                                                {filterOptions.years.map((year) => (
                                                    <option key={year} value={year}>
                                                        {year}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Min Members
                                            </label>
                                            <input
                                                type="number"
                                                value={selectedFilters.min_members}
                                                onChange={(e) => handleFilterChange('min_members', e.target.value)}
                                                placeholder="0"
                                                min="0"
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Max Members
                                            </label>
                                            <input
                                                type="number"
                                                value={selectedFilters.max_members}
                                                onChange={(e) => handleFilterChange('max_members', e.target.value)}
                                                placeholder="∞"
                                                min="0"
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center space-x-4 mt-6">
                                        <button
                                            onClick={applyFilters}
                                            className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                                        >
                                            <Filter className="w-4 h-4" />
                                            <span>Apply Filters</span>
                                        </button>
                                        <button
                                            onClick={clearFilters}
                                            className="border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg flex items-center space-x-2"
                                        >
                                            <X className="w-4 h-4" />
                                            <span>Clear All</span>
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Families Table */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th
                                            onClick={() => handleSort('family_name')}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            <div className="flex items-center space-x-1">
                                                <span>Family Name</span>
                                                <span className="text-blue-500">{getSortIcon('family_name')}</span>
                                            </div>
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Head of Family
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contact
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Parish Section
                                        </th>
                                        <th
                                            onClick={() => handleSort('members_count')}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            <div className="flex items-center space-x-1">
                                                <span>Members</span>
                                                <span className="text-blue-500">{getSortIcon('members_count')}</span>
                                            </div>
                                        </th>
                                        <th
                                            onClick={() => handleSort('created_at')}
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                        >
                                            <div className="flex items-center space-x-1">
                                                <span>Created</span>
                                                <span className="text-blue-500">{getSortIcon('created_at')}</span>
                                            </div>
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {families.data && families.data.length > 0 ? (
                                        families.data.map((family) => (
                                            <tr key={family.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {family.family_name || 'Unknown Family'}
                                                        </div>
                                                        {family.family_code && (
                                                            <div className="text-sm text-gray-500">
                                                                Code: {family.family_code}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {family.head_of_family ? (
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {family.head_of_family.full_name || 
                                                                 `${family.head_of_family.first_name || ''} ${family.head_of_family.last_name || ''}`.trim() ||
                                                                 'Unknown'}
                                                            </div>
                                                            {family.head_of_family.phone && (
                                                                <div className="text-sm text-gray-500">
                                                                    {family.head_of_family.phone}
                                                                </div>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-gray-500">No head assigned</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="space-y-1">
                                                        {family.phone && (
                                                            <div className="flex items-center text-sm text-gray-900">
                                                                <Phone className="w-4 h-4 mr-2 text-gray-400" />
                                                                <a href={`tel:${family.phone}`} className="hover:text-blue-600">
                                                                    {family.phone}
                                                                </a>
                                                            </div>
                                                        )}
                                                        {family.email && (
                                                            <div className="flex items-center text-sm text-gray-900">
                                                                <Mail className="w-4 h-4 mr-2 text-gray-400" />
                                                                <a href={`mailto:${family.email}`} className="hover:text-blue-600">
                                                                    {family.email}
                                                                </a>
                                                            </div>
                                                        )}
                                                        {family.address && (
                                                            <div className="flex items-center text-sm text-gray-500">
                                                                <MapPin className="w-4 h-4 mr-2 text-gray-400" />
                                                                <span className="truncate max-w-xs">{family.address}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="text-sm text-gray-900">
                                                        {family.parish_section || 'Not specified'}
                                                    </span>
                                                    {family.deanery && (
                                                        <div className="text-sm text-gray-500">
                                                            {family.deanery}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center space-x-2">
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            {family.members_count || 0} total
                                                        </span>
                                                        {family.active_members_count > 0 && (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                {family.active_members_count} active
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <div className="flex items-center">
                                                        <Calendar className="w-4 h-4 mr-1" />
                                                        {formatDate(family.created_at)}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end space-x-2">
                                                        <Link
                                                            href={safeRoute('families.show', family.id)}
                                                            className="text-blue-600 hover:text-blue-900 p-1 rounded"
                                                            title="View Family"
                                                        >
                                                            <Eye className="w-4 h-4" />
                                                        </Link>
                                                        <Link
                                                            href={safeRoute('families.edit', family.id)}
                                                            className="text-amber-600 hover:text-amber-900 p-1 rounded"
                                                            title="Edit Family"
                                                        >
                                                            <Edit className="w-4 h-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => handleDelete(family.id)}
                                                            className="text-red-600 hover:text-red-900 p-1 rounded"
                                                            title="Delete Family"
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
                                                <div className="flex flex-col items-center justify-center space-y-4">
                                                    <Home className="w-12 h-12 text-gray-400" />
                                                    <div>
                                                        <h3 className="text-sm font-medium text-gray-900">No families found</h3>
                                                        <p className="text-sm text-gray-500 mt-1">
                                                            {Object.values(filters).some(Boolean) 
                                                                ? 'Try adjusting your search or filters'
                                                                : 'Get started by adding your first family'
                                                            }
                                                        </p>
                                                    </div>
                                                    {!Object.values(filters).some(Boolean) && (
                                                        <Link
                                                            href={safeRoute('families.create')}
                                                            className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                                                        >
                                                            <Plus className="w-4 h-4" />
                                                            <span>Add First Family</span>
                                                        </Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {families.data && families.data.length > 0 && families.links && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {families.links.prev && (
                                            <Link
                                                href={families.links.prev}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {families.links.next && (
                                            <Link
                                                href={families.links.next}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing{' '}
                                                <span className="font-medium">{families.meta?.from || 0}</span> to{' '}
                                                <span className="font-medium">{families.meta?.to || 0}</span> of{' '}
                                                <span className="font-medium">{families.meta?.total || 0}</span> results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                                {families.links?.map((link: any, index: number) => (
                                                    <Link
                                                        key={index}
                                                        href={link.url || '#'}
                                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                            link.active
                                                                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        } ${
                                                            index === 0 ? 'rounded-l-md' : ''
                                                        } ${
                                                            index === families.links.length - 1 ? 'rounded-r-md' : ''
                                                        }`}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
