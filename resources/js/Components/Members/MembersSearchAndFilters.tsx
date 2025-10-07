 import { memo, useCallback, useState, useEffect } from 'react';
import { Search, Filter, X, RefreshCw } from 'lucide-react';
import { Filters, FilterOptions } from './types';

interface MembersSearchAndFiltersProps {
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
}

const MembersSearchAndFilters = memo<MembersSearchAndFiltersProps>(({
    filters,
    filterOptions,
    showFilters,
    isLoading,
    searchInputRef,
    onSearchChange,
    onFilterChange,
    onToggleFilters,
    onClearFilters,
    onRefresh,
}) => {
    // Local search state for immediate UI feedback
    const [searchValue, setSearchValue] = useState(filters.search || '');
    
    // Sync local state with prop changes
    useEffect(() => {
        setSearchValue(filters.search || '');
    }, [filters.search]);

    const handleSearchInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setSearchValue(value);
        onSearchChange(value);
    }, [onSearchChange]);

    const clearSearch = useCallback(() => {
        setSearchValue('');
        onSearchChange('');
        if (searchInputRef.current) {
            searchInputRef.current.focus();
        }
    }, [onSearchChange, searchInputRef]);

    const hasActiveFilters = Object.entries(filters).some(([key, value]) => 
        key !== 'search' && value && value !== '' && key !== 'sort' && key !== 'direction' && key !== 'per_page'
    );

    return (
        <div className="bg-white rounded-lg shadow p-6 mb-6">
            {/* Search and Filter Toggle Row */}
            <div className="flex flex-col sm:flex-row gap-4 mb-4">
                {/* Search Input */}
                <div className="flex-1 relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search className="h-5 w-5 text-gray-400" />
                    </div>
                    <input
                        ref={searchInputRef}
                        type="text"
                        placeholder="Search members by name, phone, email, or ID..."
                        value={searchValue}
                        onChange={handleSearchInputChange}
                        className="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        disabled={isLoading}
                    />
                    <div className="absolute inset-y-0 right-0 flex items-center">
                        {searchValue && !isLoading && (
                            <button
                                type="button"
                                onClick={clearSearch}
                                className="p-1 mr-2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                title="Clear search"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                        {isLoading && (
                            <div className="pr-3 flex items-center">
                                <RefreshCw className="h-4 w-4 text-gray-400 animate-spin" />
                            </div>
                        )}
                    </div>
                </div>

                {/* Filter and Refresh Buttons */}
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={onToggleFilters}
                        className={`inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ${
                            showFilters || hasActiveFilters ? 'text-indigo-700 border-indigo-300 bg-indigo-50' : 'text-gray-700'
                        }`}
                        disabled={isLoading}
                    >
                        <Filter className="h-4 w-4 mr-2" />
                        Filters
                        {hasActiveFilters && (
                            <span className="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                Active
                            </span>
                        )}
                    </button>

                    <button
                        type="button"
                        onClick={onRefresh}
                        className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        disabled={isLoading}
                    >
                        <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
                        Refresh
                    </button>
                </div>
            </div>

            {/* Advanced Filters Panel */}
            {showFilters && (
                <div className="border-t pt-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {/* Local Church Filter */}
                        {filterOptions.local_churches && filterOptions.local_churches.length > 0 && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Local Church
                                </label>
                                <select
                                    value={filters.local_church || ''}
                                    onChange={onFilterChange('local_church')}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    disabled={isLoading}
                                >
                                    <option value="">All Churches</option>
                                    {filterOptions.local_churches.map((church) => (
                                        <option key={church} value={church}>
                                            {church}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Church Group Filter */}
                        {filterOptions.church_groups && filterOptions.church_groups.length > 0 && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Church Group
                                </label>
                                <select
                                    value={filters.church_group || ''}
                                    onChange={onFilterChange('church_group')}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    disabled={isLoading}
                                >
                                    <option value="">All Groups</option>
                                    {filterOptions.church_groups.map((group) => (
                                        <option key={group.value} value={group.value}>
                                            {group.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Membership Status Filter */}
                        {filterOptions.membership_statuses && filterOptions.membership_statuses.length > 0 && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select
                                    value={filters.membership_status || ''}
                                    onChange={onFilterChange('membership_status')}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    disabled={isLoading}
                                >
                                    <option value="">All Statuses</option>
                                    {filterOptions.membership_statuses.map((status) => (
                                        <option key={status.value} value={status.value}>
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Gender Filter */}
                        {filterOptions.genders && filterOptions.genders.length > 0 && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Gender
                                </label>
                                <select
                                    value={filters.gender || ''}
                                    onChange={onFilterChange('gender')}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    disabled={isLoading}
                                >
                                    <option value="">All Genders</option>
                                    {filterOptions.genders.map((gender) => (
                                        <option key={gender.value} value={gender.value}>
                                            {gender.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Age Group Filter */}
                        {filterOptions.age_groups && filterOptions.age_groups.length > 0 && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Age Group
                                </label>
                                <select
                                    value={filters.age_group || ''}
                                    onChange={onFilterChange('age_group')}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    disabled={isLoading}
                                >
                                    <option value="">All Ages</option>
                                    {filterOptions.age_groups.map((group) => (
                                        <option key={group.value} value={group.value}>
                                            {group.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                    </div>

                    {/* Clear Filters */}
                    {hasActiveFilters && (
                        <div className="mt-4 flex justify-end">
                            <button
                                type="button"
                                onClick={onClearFilters}
                                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                disabled={isLoading}
                            >
                                <X className="h-4 w-4 mr-1" />
                                Clear Filters
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
});

MembersSearchAndFilters.displayName = 'MembersSearchAndFilters';

export default MembersSearchAndFilters;