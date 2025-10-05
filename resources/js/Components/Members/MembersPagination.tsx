import React, { memo } from 'react';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { PaginatedMembers, Filters } from './types';

interface MembersPaginationProps {
    members: PaginatedMembers;
    filters: Filters;
}

const MembersPagination = memo<MembersPaginationProps>(({ members, filters }) => {
    const {
        current_page,
        last_page,
        total,
        from,
        to,
        per_page,
        links
    } = members;

    // Build query params for pagination links
    const getPageUrl = (page: number): string => {
        const params = new URLSearchParams();
        
        // Add current filters
        Object.entries(filters).forEach(([key, value]) => {
            if (value && value !== '') {
                params.append(key, String(value));
            }
        });
        
        // Add page
        if (page > 1) {
            params.append('page', String(page));
        }
        
        return route('members.index') + (params.toString() ? `?${params.toString()}` : '');
    };

    // Per page options
    const perPageOptions = [10, 25, 50, 100];

    const handlePerPageChange = (newPerPage: number): void => {
        const params = new URLSearchParams();
        
        Object.entries(filters).forEach(([key, value]) => {
            if (value && value !== '' && key !== 'per_page') {
                params.append(key, String(value));
            }
        });
        
        params.append('per_page', String(newPerPage));
        
        window.location.href = route('members.index') + `?${params.toString()}`;
    };

    if (total === 0) {
        return null;
    }

    return (
        <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            {/* Mobile view */}
            <div className="flex-1 flex justify-between sm:hidden">
                {current_page > 1 ? (
                    <Link
                        href={getPageUrl(current_page - 1)}
                        className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Previous
                    </Link>
                ) : (
                    <span className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                        Previous
                    </span>
                )}
                
                {current_page < last_page ? (
                    <Link
                        href={getPageUrl(current_page + 1)}
                        className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Next
                    </Link>
                ) : (
                    <span className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                        Next
                    </span>
                )}
            </div>

            {/* Desktop view */}
            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                {/* Results info */}
                <div className="flex items-center space-x-4">
                    <p className="text-sm text-gray-700">
                        Showing <span className="font-medium">{from}</span> to{' '}
                        <span className="font-medium">{to}</span> of{' '}
                        <span className="font-medium">{total}</span> results
                    </p>
                    
                    {/* Per page selector */}
                    <div className="flex items-center space-x-2">
                        <label className="text-sm text-gray-700">Show:</label>
                        <select
                            value={per_page}
                            onChange={(e) => handlePerPageChange(Number(e.target.value))}
                            className="border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            {perPageOptions.map((option) => (
                                <option key={option} value={option}>
                                    {option}
                                </option>
                            ))}
                        </select>
                        <span className="text-sm text-gray-700">per page</span>
                    </div>
                </div>

                {/* Pagination buttons */}
                <div className="flex items-center space-x-1">
                    {/* First page */}
                    {current_page > 1 ? (
                        <Link
                            href={getPageUrl(1)}
                            className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 rounded-l-md"
                        >
                            <ChevronsLeft className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-300 rounded-l-md cursor-not-allowed">
                            <ChevronsLeft className="h-4 w-4" />
                        </span>
                    )}

                    {/* Previous page */}
                    {current_page > 1 ? (
                        <Link
                            href={getPageUrl(current_page - 1)}
                            className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-300 cursor-not-allowed">
                            <ChevronLeft className="h-4 w-4" />
                        </span>
                    )}

                    {/* Page numbers */}
                    {links
                        .filter(link => link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;')
                        .map((link, index) => {
                            if (link.label.includes('...')) {
                                return (
                                    <span
                                        key={index}
                                        className="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                    >
                                        ...
                                    </span>
                                );
                            }

                            const pageNumber = parseInt(link.label);
                            return (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`relative inline-flex items-center px-3 py-2 border text-sm font-medium ${
                                        link.active
                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                    }`}
                                >
                                    {pageNumber}
                                </Link>
                            );
                        })}

                    {/* Next page */}
                    {current_page < last_page ? (
                        <Link
                            href={getPageUrl(current_page + 1)}
                            className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-300 cursor-not-allowed">
                            <ChevronRight className="h-4 w-4" />
                        </span>
                    )}

                    {/* Last page */}
                    {current_page < last_page ? (
                        <Link
                            href={getPageUrl(last_page)}
                            className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 rounded-r-md"
                        >
                            <ChevronsRight className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span className="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-300 rounded-r-md cursor-not-allowed">
                            <ChevronsRight className="h-4 w-4" />
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
});

MembersPagination.displayName = 'MembersPagination';

export default MembersPagination;