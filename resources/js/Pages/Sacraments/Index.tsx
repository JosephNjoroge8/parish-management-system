import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Search, Plus, Edit, Eye, Trash2, Filter, Crown, Cross, Heart, Church, Baby, Calendar, MapPin } from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    permissions?: {
        can_edit_sacraments?: boolean;
        can_delete_sacraments?: boolean;
        can_view_sacraments?: boolean;
    };
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    member_number: string;
    id_number?: string;
}

interface Sacrament {
    id: number;
    member_id: number;
    member?: Member;
    sacrament_type: 'baptism' | 'eucharist' | 'confirmation' | 'reconciliation' | 'anointing' | 'marriage' | 'holy_orders';
    date_administered: string;
    administered_by: string;
    location: string;
    certificate_number?: string;
    witness_1?: string;
    witness_2?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface SacramentsIndexProps {
    auth: {
        user: User;
    };
    sacraments: {
        data: Sacrament[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        sacrament_type?: string;
        year?: string;
    };
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
}

const sacramentIcons = {
    baptism: Baby,
    eucharist: Cross,
    confirmation: Crown,
    reconciliation: Heart,
    anointing: Cross,
    marriage: Heart,
    holy_orders: Church,
};

const sacramentLabels = {
    baptism: 'Baptism',
    eucharist: 'First Holy Communion',
    confirmation: 'Confirmation',
    reconciliation: 'Reconciliation',
    anointing: 'Anointing of the Sick',
    marriage: 'Marriage',
    holy_orders: 'Holy Orders',
};

const sacramentColors = {
    baptism: 'text-blue-600 bg-blue-100',
    eucharist: 'text-green-600 bg-green-100',
    confirmation: 'text-purple-600 bg-purple-100',
    reconciliation: 'text-orange-600 bg-orange-100',
    anointing: 'text-yellow-600 bg-yellow-100',
    marriage: 'text-pink-600 bg-pink-100',
    holy_orders: 'text-indigo-600 bg-indigo-100',
};

export default function SacramentsIndex({ auth, sacraments, filters }: SacramentsIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [sacramentTypeFilter, setSacramentTypeFilter] = useState(filters.sacrament_type || '');
    const [yearFilter, setYearFilter] = useState(filters.year || '');
    const [deletingId, setDeletingId] = useState<number | null>(null);

    // Check permissions
    const canEdit = auth?.user?.permissions?.can_edit_sacraments ?? true;
    const canDelete = auth?.user?.permissions?.can_delete_sacraments ?? true;
    const canView = auth?.user?.permissions?.can_view_sacraments ?? true;

    const stats = useMemo(() => {
        const typeStats = sacraments.data.reduce((acc, sacrament) => {
            acc[sacrament.sacrament_type] = (acc[sacrament.sacrament_type] || 0) + 1;
            return acc;
        }, {} as Record<string, number>);

        return {
            total: sacraments.total,
            ...typeStats
        };
    }, [sacraments.data, sacraments.total]);

    const availableYears = useMemo(() => {
        const years = new Set<string>();
        sacraments.data.forEach(sacrament => {
            years.add(new Date(sacrament.date_administered).getFullYear().toString());
        });
        return Array.from(years).sort((a, b) => parseInt(b) - parseInt(a));
    }, [sacraments.data]);

    const handleSearch = () => {
        router.get(route('sacraments.index'), {
            search: searchTerm,
            sacrament_type: sacramentTypeFilter,
            year: yearFilter,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSacramentTypeFilter('');
        setYearFilter('');
        router.get(route('sacraments.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (sacrament: Sacrament) => {
        const memberName = sacrament.member?.first_name && sacrament.member?.last_name 
            ? `${sacrament.member.first_name} ${sacrament.member.last_name}`
            : 'this member';
        
        if (confirm(`Are you sure you want to delete this ${sacramentLabels[sacrament.sacrament_type]} record for ${memberName}? This action cannot be undone.`)) {
            setDeletingId(sacrament.id);
            router.delete(route('sacraments.destroy', sacrament.id), {
                onFinish: () => setDeletingId(null),
                onError: () => {
                    setDeletingId(null);
                    alert('Failed to delete sacrament record. Please try again.');
                }
            });
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <div>
                        <h2 className="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
                            Sacramental Records
                        </h2>
                        <p className="text-xs sm:text-sm text-gray-600 mt-1">
                            Manage baptisms, confirmations, and other sacramental records
                        </p>
                    </div>
                    {canEdit && (
                        <Link
                            href={route('sacraments.create')}
                            className="bg-purple-500 hover:bg-purple-600 text-white px-3 sm:px-4 py-2 rounded-lg flex items-center justify-center space-x-2 transition-colors text-sm sm:text-base"
                        >
                            <Plus className="w-4 h-4" />
                            <span className="hidden sm:inline">Add Sacrament</span>
                            <span className="sm:hidden">Add</span>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Sacraments" />

            <div className="py-6 sm:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Responsive Statistics Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-7 gap-3 sm:gap-4 mb-6">
                        <div className="bg-white rounded-lg shadow p-3 sm:p-4">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <Church className="h-6 w-6 sm:h-8 sm:w-8 text-purple-600" />
                                </div>
                                <div className="ml-2 sm:ml-3 w-0 flex-1">
                                    <dl>
                                        <dt className="text-xs sm:text-sm font-medium text-gray-500 truncate">Total Records</dt>
                                        <dd className="text-base sm:text-lg font-medium text-gray-900">{stats.total}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {Object.entries(sacramentLabels).map(([type, label]) => {
                            const Icon = sacramentIcons[type as keyof typeof sacramentIcons];
                            const count = stats[type as keyof typeof stats] || 0;
                            const colorClasses = sacramentColors[type as keyof typeof sacramentColors];
                            return (
                                <div key={type} className="bg-white rounded-lg shadow p-3 sm:p-4">
                                    <div className="flex items-center">
                                        <div className={`flex-shrink-0 p-1.5 sm:p-2 rounded-full ${colorClasses.split(' ')[1]}`}>
                                            <Icon className={`h-4 w-4 sm:h-6 sm:w-6 ${colorClasses.split(' ')[0]}`} />
                                        </div>
                                        <div className="ml-2 sm:ml-3 w-0 flex-1">
                                            <dl>
                                                <dt className="text-xs font-medium text-gray-500 truncate" title={label}>{label}</dt>
                                                <dd className="text-base sm:text-lg font-medium text-gray-900">{count}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Responsive Search and Filter Section */}
                    <div className="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
                        <div className="flex flex-col space-y-4 lg:flex-row lg:space-y-0 lg:gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                    <input
                                        type="text"
                                        placeholder="Search by member name, certificate number, administered by..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyPress={handleKeyPress}
                                        className="w-full pl-10 pr-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            
                            <div className="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3 lg:space-x-4">
                                <select
                                    value={sacramentTypeFilter}
                                    onChange={(e) => setSacramentTypeFilter(e.target.value)}
                                    className="px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent min-w-0 sm:min-w-[140px]"
                                >
                                    <option value="">All Sacraments</option>
                                    {Object.entries(sacramentLabels).map(([value, label]) => (
                                        <option key={value} value={value}>{label}</option>
                                    ))}
                                </select>

                                <select
                                    value={yearFilter}
                                    onChange={(e) => setYearFilter(e.target.value)}
                                    className="px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent min-w-0 sm:min-w-[100px]"
                                >
                                    <option value="">All Years</option>
                                    {availableYears.map(year => (
                                        <option key={year} value={year}>{year}</option>
                                    ))}
                                </select>
                                
                                <div className="flex space-x-2">
                                    <button
                                        onClick={handleSearch}
                                        className="flex-1 sm:flex-none bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2 transition-colors text-sm sm:text-base"
                                    >
                                        <Search className="w-4 h-4" />
                                        <span className="hidden sm:inline">Search</span>
                                    </button>
                                    
                                    <button
                                        onClick={clearFilters}
                                        className="flex-1 sm:flex-none bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors text-sm sm:text-base"
                                    >
                                        <span className="hidden sm:inline">Clear</span>
                                        <span className="sm:hidden">×</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Multi-Breakpoint Responsive Data Display */}
                    {sacraments.data.length > 0 ? (
                        <>
                            {/* Large Desktop Table (lg and up) */}
                            <div className="hidden lg:block bg-white rounded-lg shadow-md overflow-hidden mb-6">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sacrament</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Administered By</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {sacraments.data.map((sacrament) => {
                                                const Icon = sacramentIcons[sacrament.sacrament_type];
                                                const colorClasses = sacramentColors[sacrament.sacrament_type];
                                                return (
                                                    <tr key={sacrament.id} className="hover:bg-gray-50 transition-colors">
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div>
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {sacrament.member?.first_name || 'Unknown'} {sacrament.member?.last_name || 'Member'}
                                                                </div>
                                                                <div className="text-sm text-gray-500">#{sacrament.member?.member_number || 'N/A'}</div>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="flex items-center">
                                                                <div className={`p-1 rounded-full ${colorClasses.split(' ')[1]} mr-2`}>
                                                                    <Icon className={`w-4 h-4 ${colorClasses.split(' ')[0]}`} />
                                                                </div>
                                                                <span className="text-sm text-gray-900">{sacramentLabels[sacrament.sacrament_type]}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            <div className="flex items-center">
                                                                <Calendar className="w-4 h-4 text-gray-400 mr-1" />
                                                                {new Date(sacrament.date_administered).toLocaleDateString()}
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{sacrament.administered_by}</td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{sacrament.location}</td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{sacrament.certificate_number || 'N/A'}</td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <div className="flex justify-end space-x-2">
                                                                {canView && (
                                                                    <Link href={route('sacraments.show', sacrament.id)} className="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50 transition-colors" title="View">
                                                                        <Eye className="w-4 h-4" />
                                                                    </Link>
                                                                )}
                                                                {canEdit && (
                                                                    <Link href={route('sacraments.edit', sacrament.id)} className="text-yellow-600 hover:text-yellow-800 p-2 rounded hover:bg-yellow-50 transition-colors" title="Edit">
                                                                        <Edit className="w-4 h-4" />
                                                                    </Link>
                                                                )}
                                                                {canDelete && (
                                                                    <button onClick={() => handleDelete(sacrament)} disabled={deletingId === sacrament.id} className="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50 transition-colors disabled:opacity-50" title="Delete">
                                                                        <Trash2 className="w-4 h-4" />
                                                                    </button>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Medium Screen Compact Table (md to lg) */}
                            <div className="hidden md:block lg:hidden bg-white rounded-lg shadow-md overflow-hidden mb-6">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member & Sacrament</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Location</th>
                                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {sacraments.data.map((sacrament) => {
                                                const Icon = sacramentIcons[sacrament.sacrament_type];
                                                const colorClasses = sacramentColors[sacrament.sacrament_type];
                                                return (
                                                    <tr key={sacrament.id} className="hover:bg-gray-50 transition-colors">
                                                        <td className="px-4 py-4">
                                                            <div className="space-y-1">
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {sacrament.member?.first_name || 'Unknown'} {sacrament.member?.last_name || 'Member'}
                                                                </div>
                                                                <div className="flex items-center">
                                                                    <div className={`p-1 rounded-full ${colorClasses.split(' ')[1]} mr-2`}>
                                                                        <Icon className={`w-3 h-3 ${colorClasses.split(' ')[0]}`} />
                                                                    </div>
                                                                    <span className="text-xs text-gray-600">{sacramentLabels[sacrament.sacrament_type]}</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <div className="space-y-1">
                                                                <div className="text-sm text-gray-900">{new Date(sacrament.date_administered).toLocaleDateString()}</div>
                                                                <div className="text-xs text-gray-600 truncate max-w-[120px]">{sacrament.location}</div>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4 text-right">
                                                            <div className="flex justify-end space-x-1">
                                                                {canView && (
                                                                    <Link href={route('sacraments.show', sacrament.id)} className="text-blue-600 hover:text-blue-800 p-1.5 rounded hover:bg-blue-50 transition-colors" title="View">
                                                                        <Eye className="w-3 h-3" />
                                                                    </Link>
                                                                )}
                                                                {canEdit && (
                                                                    <Link href={route('sacraments.edit', sacrament.id)} className="text-yellow-600 hover:text-yellow-800 p-1.5 rounded hover:bg-yellow-50 transition-colors" title="Edit">
                                                                        <Edit className="w-3 h-3" />
                                                                    </Link>
                                                                )}
                                                                {canDelete && (
                                                                    <button onClick={() => handleDelete(sacrament)} disabled={deletingId === sacrament.id} className="text-red-600 hover:text-red-800 p-1.5 rounded hover:bg-red-50 transition-colors disabled:opacity-50" title="Delete">
                                                                        <Trash2 className="w-3 h-3" />
                                                                    </button>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Mobile Card View (sm and below) */}
                            <div className="md:hidden grid grid-cols-1 gap-3 sm:gap-4 mb-6">
                                {sacraments.data.map((sacrament) => {
                                    const Icon = sacramentIcons[sacrament.sacrament_type];
                                    const colorClasses = sacramentColors[sacrament.sacrament_type];
                                    return (
                                        <div key={sacrament.id} className="bg-white rounded-lg shadow-md p-4 sm:p-5">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex items-center space-x-3 min-w-0 flex-1">
                                                    <div className={`p-1.5 sm:p-2 rounded-full ${colorClasses.split(' ')[1]} flex-shrink-0`}>
                                                        <Icon className={`w-4 h-4 sm:w-5 sm:h-5 ${colorClasses.split(' ')[0]}`} />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="text-base sm:text-lg font-medium text-gray-900 truncate">
                                                            {sacrament.member?.first_name || 'Unknown'} {sacrament.member?.last_name || 'Member'}
                                                        </h3>
                                                        <p className="text-sm text-gray-600">{sacramentLabels[sacrament.sacrament_type]}</p>
                                                        <p className="text-xs text-gray-500">#{sacrament.member?.member_number || 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="space-y-2 mb-4">
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <Calendar className="w-4 h-4 mr-2 flex-shrink-0" />
                                                    <span className="truncate">{new Date(sacrament.date_administered).toLocaleDateString()}</span>
                                                </div>
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <Church className="w-4 h-4 mr-2 flex-shrink-0" />
                                                    <span className="truncate">{sacrament.administered_by}</span>
                                                </div>
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <MapPin className="w-4 h-4 mr-2 flex-shrink-0" />
                                                    <span className="truncate">{sacrament.location}</span>
                                                </div>
                                                {sacrament.certificate_number && (
                                                    <div className="flex items-center text-sm text-gray-600">
                                                        <span className="font-medium mr-2 flex-shrink-0">Certificate:</span>
                                                        <span className="truncate">{sacrament.certificate_number}</span>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="flex justify-end space-x-2 pt-2 border-t border-gray-100">
                                                {canView && (
                                                    <Link href={route('sacraments.show', sacrament.id)} className="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50 transition-colors" title="View">
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                )}
                                                {canEdit && (
                                                    <Link href={route('sacraments.edit', sacrament.id)} className="text-yellow-600 hover:text-yellow-800 p-2 rounded hover:bg-yellow-50 transition-colors" title="Edit">
                                                        <Edit className="w-4 h-4" />
                                                    </Link>
                                                )}
                                                {canDelete && (
                                                    <button onClick={() => handleDelete(sacrament)} disabled={deletingId === sacrament.id} className="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50 transition-colors disabled:opacity-50" title="Delete">
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Responsive Pagination */}
                            {sacraments.last_page > 1 && (
                                <div className="flex items-center justify-between bg-white px-3 sm:px-4 lg:px-6 py-3 border-t border-gray-200 rounded-lg shadow">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {sacraments.links[0]?.url && (
                                            <Link href={sacraments.links[0].url} className="relative inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                Previous
                                            </Link>
                                        )}
                                        {sacraments.links[sacraments.links.length - 1]?.url && (
                                            <Link href={sacraments.links[sacraments.links.length - 1].url} className="ml-3 relative inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-xs sm:text-sm text-gray-700">
                                                Showing <span className="font-medium">{sacraments.from}</span> to{' '}
                                                <span className="font-medium">{sacraments.to}</span> of{' '}
                                                <span className="font-medium">{sacraments.total}</span> results
                                            </p>
                                        </div>
                                        <div className="overflow-x-auto">
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {sacraments.links.map((link, index) => (
                                                    <Link
                                                        key={index}
                                                        href={link.url || '#'}
                                                        className={`relative inline-flex items-center px-2 sm:px-4 py-2 border text-xs sm:text-sm font-medium ${
                                                            link.active
                                                                ? 'z-10 bg-purple-50 border-purple-500 text-purple-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        } ${index === 0 ? 'rounded-l-md' : ''} ${
                                                            index === sacraments.links.length - 1 ? 'rounded-r-md' : ''
                                                        } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="bg-white rounded-lg shadow-md p-6 sm:p-8 text-center">
                            <Church className="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400 mb-4" />
                            <h3 className="text-base sm:text-lg font-medium text-gray-900 mb-2">No sacramental records found</h3>
                            <p className="text-sm sm:text-base text-gray-600 mb-4">
                                {searchTerm || sacramentTypeFilter || yearFilter
                                    ? 'Try adjusting your search criteria.'
                                    : 'Get started by adding your first sacramental record.'}
                            </p>
                            {canEdit && (!searchTerm && !sacramentTypeFilter && !yearFilter) && (
                                <Link
                                    href={route('sacraments.create')}
                                    className="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg inline-flex items-center space-x-2 transition-colors text-sm sm:text-base"
                                >
                                    <Plus className="w-4 h-4" />
                                    <span>Add First Sacrament</span>
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}