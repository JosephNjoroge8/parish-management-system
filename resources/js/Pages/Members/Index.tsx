import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
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
    X
} from 'lucide-react';
import { showNotification } from '@/Utils/notifications';

// Import optimized components
import MembersStats from '@/Components/Members/MembersStats';
import MembersSearchAndFilters from '@/Components/Members/MembersSearchAndFilters';
import MembersGrid from '@/Components/Members/MembersGrid';
import MembersPagination from '@/Components/Members/MembersPagination';
import { MembersIndexProps, Member, Filters } from '@/Components/Members/types';

// Type for Inertia errors
interface InertiaErrors {
    [key: string]: string | string[];
}

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
            
            router.get(route('members.index', cleanParams), undefined, {
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

            router.get(route('members.index', cleanParams), undefined, {
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
        router.get(route('members.index'), undefined, {
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
        router.delete(route('members.destroy', memberToDelete.id), {
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

    // Status change handler
    const handleStatusChange = useCallback(async (memberId: number, newStatus: string) => {
        try {
            await new Promise((resolve, reject) => {
                router.patch(route('members.update-status', memberId), 
                    { membership_status: newStatus },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            if (typeof showNotification === 'function') {
                                showNotification('Success', `Member status updated to ${newStatus} successfully`, 'success');
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
    }, []);

    // Bulk delete handler
    const handleBulkDelete = useCallback(() => {
        if (selectedMembers.length === 0) return;
        setShowBulkDeleteModal(true);
    }, [selectedMembers.length]);

    const confirmBulkDelete = useCallback(() => {
        router.post(route('members.bulk-delete'), {
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
        
        const url = route('members.export') + '?' + exportParams.toString();
        window.open(url, '_blank');
    }, [filters]);

    // Import handlers
    const handleImport = useCallback(() => {
        if (!importFile) return;

        const formData = new FormData();
        formData.append('file', importFile);

        setIsImporting(true);
        
        router.post(route('members.import'), formData, {
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
                            href={route('members.create')}
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