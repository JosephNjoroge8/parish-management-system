import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Search, Plus, Edit, Eye, Trash2, DollarSign, Calendar, TrendingUp, Users, CreditCard, Receipt } from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    permissions?: {
        can_edit_tithing?: boolean;
        can_delete_tithing?: boolean;
        can_view_tithing?: boolean;
        can_view_financial_reports?: boolean;
    };
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    member_number: string;
}

interface TithingRecord {
    id: number;
    member_id: number;
    member: Member;
    amount: number;
    payment_date: string;
    payment_method: 'cash' | 'cheque' | 'bank_transfer' | 'mobile_money' | 'card';
    reference_number?: string;
    offering_type: 'tithe' | 'offering' | 'thanksgiving' | 'project' | 'special';
    received_by: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface TithingIndexProps {
    auth: {
        user: User;
    };
    tithing: {
        data: TithingRecord[];
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
        payment_method?: string;
        offering_type?: string;
        month?: string;
        year?: string;
    };
    statistics?: {
        total_amount: number;
        this_month: number;
        this_year: number;
        total_records: number;
        by_type: Record<string, number>;
        by_method: Record<string, number>;
    };
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
}

const paymentMethodLabels = {
    cash: 'Cash',
    cheque: 'Cheque',
    bank_transfer: 'Bank Transfer',
    mobile_money: 'Mobile Money',
    card: 'Card',
};

const offeringTypeLabels = {
    tithe: 'Tithe',
    offering: 'Offering',
    thanksgiving: 'Thanksgiving',
    project: 'Project Contribution',
    special: 'Special Offering',
};

const paymentMethodColors = {
    cash: 'text-green-600 bg-green-100',
    cheque: 'text-blue-600 bg-blue-100',
    bank_transfer: 'text-purple-600 bg-purple-100',
    mobile_money: 'text-orange-600 bg-orange-100',
    card: 'text-indigo-600 bg-indigo-100',
};

const offeringTypeColors = {
    tithe: 'text-emerald-600 bg-emerald-100',
    offering: 'text-blue-600 bg-blue-100',
    thanksgiving: 'text-yellow-600 bg-yellow-100',
    project: 'text-purple-600 bg-purple-100',
    special: 'text-pink-600 bg-pink-100',
};

export default function TithingIndex({ auth, tithing, filters, statistics }: TithingIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [paymentMethodFilter, setPaymentMethodFilter] = useState(filters.payment_method || '');
    const [offeringTypeFilter, setOfferingTypeFilter] = useState(filters.offering_type || '');
    const [monthFilter, setMonthFilter] = useState(filters.month || '');
    const [yearFilter, setYearFilter] = useState(filters.year || '');
    const [deletingId, setDeletingId] = useState<number | null>(null);

    // Defensive fallback for statistics
    const safeStatistics = statistics || {
        total_amount: 0,
        this_month: 0,
        this_year: 0,
        total_records: 0,
        by_type: {},
        by_method: {},
    };

    // Check permissions
    const canEdit = auth?.user?.permissions?.can_edit_tithing ?? true;
    const canDelete = auth?.user?.permissions?.can_delete_tithing ?? true;
    const canView = auth?.user?.permissions?.can_view_tithing ?? true;
    const canViewReports = auth?.user?.permissions?.can_view_financial_reports ?? true;

    const availableYears = useMemo(() => {
        const years = new Set<string>();
        tithing.data.forEach(record => {
            years.add(new Date(record.payment_date).getFullYear().toString());
        });
        return Array.from(years).sort((a, b) => parseInt(b) - parseInt(a));
    }, [tithing.data]);

    const months = [
        { value: '01', label: 'January' },
        { value: '02', label: 'February' },
        { value: '03', label: 'March' },
        { value: '04', label: 'April' },
        { value: '05', label: 'May' },
        { value: '06', label: 'June' },
        { value: '07', label: 'July' },
        { value: '08', label: 'August' },
        { value: '09', label: 'September' },
        { value: '10', label: 'October' },
        { value: '11', label: 'November' },
        { value: '12', label: 'December' },
    ];

    const handleSearch = () => {
        router.get(route('tithes.index'), {
            search: searchTerm,
            payment_method: paymentMethodFilter,
            offering_type: offeringTypeFilter,
            month: monthFilter,
            year: yearFilter,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setPaymentMethodFilter('');
        setOfferingTypeFilter('');
        setMonthFilter('');
        setYearFilter('');
        router.get(route('tithes.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (record: TithingRecord) => {
        if (confirm(`Are you sure you want to delete this ${offeringTypeLabels[record.offering_type]} record for ${record.member.first_name} ${record.member.last_name}? This action cannot be undone.`)) {
            setDeletingId(record.id);
            router.delete(route('tithes.destroy', record.id), {
                onFinish: () => setDeletingId(null),
                onError: () => {
                    setDeletingId(null);
                    alert('Failed to delete tithing record. Please try again.');
                }
            });
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-KE', {
            style: 'currency',
            currency: 'KES',
        }).format(amount);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Tithing & Offerings
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage tithes, offerings, and financial contributions
                        </p>
                    </div>
                    <div className="flex space-x-3">
                        {canViewReports && (
                            <Link
                                href={route('reports.financial')}
                                className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                            >
                                <Receipt className="w-4 h-4" />
                                <span>Reports</span>
                            </Link>
                        )}
                        {canEdit && (
                            <Link
                                href={route('tithes.create')}
                                className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Add Record</span>
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Tithing & Offerings" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <DollarSign className="h-8 w-8 text-green-600" />
                                </div>
                                <div className="ml-3 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">Total Amount</dt>
                                        <dd className="text-lg font-medium text-gray-900">{formatCurrency(safeStatistics.total_amount)}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <Calendar className="h-8 w-8 text-blue-600" />
                                </div>
                                <div className="ml-3 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">This Month</dt>
                                        <dd className="text-lg font-medium text-gray-900">{formatCurrency(safeStatistics.this_month)}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <TrendingUp className="h-8 w-8 text-purple-600" />
                                </div>
                                <div className="ml-3 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">This Year</dt>
                                        <dd className="text-lg font-medium text-gray-900">{formatCurrency(safeStatistics.this_year)}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <Users className="h-8 w-8 text-orange-600" />
                                </div>
                                <div className="ml-3 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">Total Records</dt>
                                        <dd className="text-lg font-medium text-gray-900">{safeStatistics.total_records}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Offering Types Breakdown */}
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        {Object.entries(offeringTypeLabels).map(([type, label]) => {
                            const amount = safeStatistics.by_type[type] || 0;
                            const colorClasses = offeringTypeColors[type as keyof typeof offeringTypeColors];
                            return (
                                <div key={type} className="bg-white rounded-lg shadow p-4">
                                    <div className="text-center">
                                        <div className={`mx-auto w-8 h-8 rounded-full ${colorClasses.split(' ')[1]} flex items-center justify-center mb-2`}>
                                            <DollarSign className={`w-4 h-4 ${colorClasses.split(' ')[0]}`} />
                                        </div>
                                        <dt className="text-xs font-medium text-gray-500 truncate">{label}</dt>
                                        <dd className="text-sm font-medium text-gray-900">{formatCurrency(amount)}</dd>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Search and Filter Section */}
                    <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div className="grid grid-cols-1 lg:grid-cols-6 gap-4">
                            <div className="lg:col-span-2">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                    <input
                                        type="text"
                                        placeholder="Search by member name, reference number..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyPress={handleKeyPress}
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            
                            <select
                                value={offeringTypeFilter}
                                onChange={(e) => setOfferingTypeFilter(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            >
                                <option value="">All Types</option>
                                {Object.entries(offeringTypeLabels).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>

                            <select
                                value={paymentMethodFilter}
                                onChange={(e) => setPaymentMethodFilter(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            >
                                <option value="">All Methods</option>
                                {Object.entries(paymentMethodLabels).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>

                            <select
                                value={monthFilter}
                                onChange={(e) => setMonthFilter(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            >
                                <option value="">All Months</option>
                                {months.map(month => (
                                    <option key={month.value} value={month.value}>{month.label}</option>
                                ))}
                            </select>

                            <select
                                value={yearFilter}
                                onChange={(e) => setYearFilter(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            >
                                <option value="">All Years</option>
                                {availableYears.map(year => (
                                    <option key={year} value={year}>{year}</option>
                                ))}
                            </select>
                        </div>

                        <div className="flex justify-end space-x-3 mt-4">
                            <button
                                onClick={handleSearch}
                                className="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                            >
                                <Search className="w-4 h-4" />
                                <span>Search</span>
                            </button>
                            
                            <button
                                onClick={clearFilters}
                                className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors"
                            >
                                Clear
                            </button>
                        </div>
                    </div>

                    {/* Tithing Records */}
                    {tithing.data.length > 0 ? (
                        <>
                            {/* Desktop Table View */}
                            <div className="hidden lg:block bg-white rounded-lg shadow-md overflow-hidden mb-6">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Member
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Amount
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Payment Date
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Method
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Reference
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Received By
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {tithing.data.map((record) => {
                                            const typeColorClasses = offeringTypeColors[record.offering_type];
                                            const methodColorClasses = paymentMethodColors[record.payment_method];
                                            return (
                                                <tr key={record.id} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div>
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {record.member.first_name} {record.member.last_name}
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    #{record.member.member_number}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${typeColorClasses}`}>
                                                            {offeringTypeLabels[record.offering_type]}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {formatCurrency(record.amount)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <div className="flex items-center">
                                                            <Calendar className="w-4 h-4 text-gray-400 mr-1" />
                                                            {new Date(record.payment_date).toLocaleDateString()}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${methodColorClasses}`}>
                                                            {paymentMethodLabels[record.payment_method]}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {record.reference_number || 'N/A'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {record.received_by}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex justify-end space-x-2">
                                                            {canView && (
                                                                <Link
                                                                    href={route('tithes.show', record.id)}
                                                                    className="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50 transition-colors"
                                                                    title="View Details"
                                                                >
                                                                    <Eye className="w-4 h-4" />
                                                                </Link>
                                                            )}
                                                            {canEdit && (
                                                                <Link
                                                                    href={route('tithes.edit', record.id)}
                                                                    className="text-yellow-600 hover:text-yellow-800 p-2 rounded hover:bg-yellow-50 transition-colors"
                                                                    title="Edit"
                                                                >
                                                                    <Edit className="w-4 h-4" />
                                                                </Link>
                                                            )}
                                                            {canDelete && (
                                                                <button
                                                                    onClick={() => handleDelete(record)}
                                                                    disabled={deletingId === record.id}
                                                                    className="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50 transition-colors disabled:opacity-50"
                                                                    title="Delete"
                                                                >
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

                            {/* Mobile Card View */}
                            <div className="lg:hidden grid grid-cols-1 gap-4 mb-6">
                                {tithing.data.map((record) => {
                                    const typeColorClasses = offeringTypeColors[record.offering_type];
                                    const methodColorClasses = paymentMethodColors[record.payment_method];
                                    return (
                                        <div key={record.id} className="bg-white rounded-lg shadow-md p-6">
                                            <div className="flex items-start justify-between mb-4">
                                                <div>
                                                    <h3 className="text-lg font-medium text-gray-900">
                                                        {record.member.first_name} {record.member.last_name}
                                                    </h3>
                                                    <p className="text-sm text-gray-600">#{record.member.member_number}</p>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-xl font-bold text-green-600">
                                                        {formatCurrency(record.amount)}
                                                    </div>
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${typeColorClasses}`}>
                                                        {offeringTypeLabels[record.offering_type]}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="space-y-2 mb-4">
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <Calendar className="w-4 h-4 mr-2 flex-shrink-0" />
                                                    <span>{new Date(record.payment_date).toLocaleDateString()}</span>
                                                </div>
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <CreditCard className="w-4 h-4 mr-2 flex-shrink-0" />
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${methodColorClasses}`}>
                                                        {paymentMethodLabels[record.payment_method]}
                                                    </span>
                                                </div>
                                                {record.reference_number && (
                                                    <div className="flex items-center text-sm text-gray-600">
                                                        <span className="font-medium mr-2">Reference:</span>
                                                        <span>{record.reference_number}</span>
                                                    </div>
                                                )}
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <span className="font-medium mr-2">Received by:</span>
                                                    <span>{record.received_by}</span>
                                                </div>
                                            </div>

                                            <div className="flex justify-end space-x-2">
                                                {canView && (
                                                    <Link
                                                        href={route('tithes.show', record.id)}
                                                        className="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50 transition-colors"
                                                        title="View Details"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                )}
                                                {canEdit && (
                                                    <Link
                                                        href={route('tithes.edit', record.id)}
                                                        className="text-yellow-600 hover:text-yellow-800 p-2 rounded hover:bg-yellow-50 transition-colors"
                                                        title="Edit"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </Link>
                                                )}
                                                {canDelete && (
                                                    <button
                                                        onClick={() => handleDelete(record)}
                                                        disabled={deletingId === record.id}
                                                        className="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50 transition-colors disabled:opacity-50"
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Pagination */}
                            {tithing.last_page > 1 && (
                                <div className="flex items-center justify-between bg-white px-4 py-3 border-t border-gray-200 sm:px-6 rounded-lg shadow">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {tithing.links[0]?.url && (
                                            <Link
                                                href={tithing.links[0].url}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {tithing.links[tithing.links.length - 1]?.url && (
                                            <Link
                                                href={tithing.links[tithing.links.length - 1].url}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing <span className="font-medium">{tithing.from}</span> to{' '}
                                                <span className="font-medium">{tithing.to}</span> of{' '}
                                                <span className="font-medium">{tithing.total}</span> results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {tithing.links.map((link, index) => (
                                                    <Link
                                                        key={index}
                                                        href={link.url || '#'}
                                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                            link.active
                                                                ? 'z-10 bg-green-50 border-green-500 text-green-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        } ${index === 0 ? 'rounded-l-md' : ''} ${
                                                            index === tithing.links.length - 1 ? 'rounded-r-md' : ''
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
                        <div className="bg-white rounded-lg shadow-md p-8 text-center">
                            <DollarSign className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No tithing records found</h3>
                            <p className="text-gray-600 mb-4">
                                {searchTerm || paymentMethodFilter || offeringTypeFilter || monthFilter || yearFilter
                                    ? 'Try adjusting your search criteria.'
                                    : 'Get started by adding your first tithing record.'}
                            </p>
                            {canEdit && (!searchTerm && !paymentMethodFilter && !offeringTypeFilter && !monthFilter && !yearFilter) && (
                                <Link
                                    href={route('tithes.create')}
                                    className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg inline-flex items-center space-x-2 transition-colors"
                                >
                                    <Plus className="w-4 h-4" />
                                    <span>Add First Record</span>
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}