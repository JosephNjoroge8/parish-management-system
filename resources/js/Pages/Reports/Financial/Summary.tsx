import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    ArrowLeft, 
    DollarSign, 
    TrendingUp, 
    TrendingDown, 
    Calendar,
    Download,
    PieChart,
    BarChart3,
    CreditCard,
    Users,
    Target,
    AlertCircle
} from 'lucide-react';
import { PageProps } from '@/types';

interface FinancialUser {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    permissions?: {
        can_view_financial_reports?: boolean;
        can_export_reports?: boolean;
    };
}

interface FinancialData {
    total_amount: number;
    previous_period_amount: number;
    period_growth: number;
    monthly_data: Array<{
        month: string;
        amount: number;
        tithe: number;
        offering: number;
        special: number;
        project: number;
        thanksgiving: number;
    }>;
    by_type: {
        tithe: number;
        offering: number;
        thanksgiving: number;
        project: number;
        special: number;
    };
    by_method: {
        cash: number;
        cheque: number;
        bank_transfer: number;
        mobile_money: number;
        card: number;
    };
    top_contributors: Array<{
        member_name: string;
        total_amount: number;
        contribution_count: number;
    }>;
    statistics: {
        average_contribution: number;
        total_contributors: number;
        highest_single_contribution: number;
        most_active_month: string;
    };
}

interface FinancialSummaryProps {
    auth: {
        user: FinancialUser;
    };
    financial_data: FinancialData;
    period: string;
    year: string;
    filters: {
        period?: string;
        year?: string;
        offering_type?: string;
    };
}

export default function FinancialSummary({ auth, financial_data, period, year, filters }: FinancialSummaryProps) {
    const [selectedView, setSelectedView] = useState<'overview' | 'trends' | 'breakdown'>('overview');
    const [chartType, setChartType] = useState<'bar' | 'pie'>('bar');

    const canViewFinancial = auth?.user?.permissions?.can_view_financial_reports ?? true;
    const canExport = auth?.user?.permissions?.can_export_reports ?? true;

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-KE', {
            style: 'currency',
            currency: 'KES',
        }).format(amount);
    };

    const formatPercentage = (value: number) => {
        return `${value >= 0 ? '+' : ''}${value.toFixed(1)}%`;
    };

    const offeringTypeLabels = {
        tithe: 'Tithe',
        offering: 'Offering',
        thanksgiving: 'Thanksgiving',
        project: 'Project Contribution',
        special: 'Special Offering',
    };

    const paymentMethodLabels = {
        cash: 'Cash',
        cheque: 'Cheque',
        bank_transfer: 'Bank Transfer',
        mobile_money: 'Mobile Money',
        card: 'Card',
    };

    const offeringTypeColors = {
        tithe: 'bg-emerald-500',
        offering: 'bg-blue-500',
        thanksgiving: 'bg-yellow-500',
        project: 'bg-purple-500',
        special: 'bg-pink-500',
    };

    const paymentMethodColors = {
        cash: 'bg-green-500',
        cheque: 'bg-blue-500',
        bank_transfer: 'bg-purple-500',
        mobile_money: 'bg-orange-500',
        card: 'bg-indigo-500',
    };

    const totalByType = Object.values(financial_data.by_type).reduce((sum, amount) => sum + amount, 0);
    const totalByMethod = Object.values(financial_data.by_method).reduce((sum, amount) => sum + amount, 0);

    if (!canViewFinancial) {
        return (
            <AuthenticatedLayout>
                <Head title="Financial Reports - Access Denied" />
                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
                            <h3 className="text-lg font-medium text-red-800 mb-2">Access Denied</h3>
                            <p className="text-red-700">
                                You don't have permission to view financial reports. Please contact an administrator.
                            </p>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('reports.index')}
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Financial Summary Report
                            </h2>
                            <p className="text-sm text-gray-600">
                                {period} {year} - Comprehensive financial analysis
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <div className="flex bg-gray-100 rounded-lg p-1">
                            {['overview', 'trends', 'breakdown'].map((view) => (
                                <button
                                    key={view}
                                    onClick={() => setSelectedView(view as any)}
                                    className={`px-3 py-1 text-sm font-medium capitalize rounded-md transition-colors ${
                                        selectedView === view
                                            ? 'bg-white text-gray-900 shadow-sm'
                                            : 'text-gray-600 hover:text-gray-900'
                                    }`}
                                >
                                    {view}
                                </button>
                            ))}
                        </div>
                        {canExport && (
                            <Link
                                href={route('reports.financial.export', { period, year })}
                                className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                            >
                                <Download className="w-4 h-4" />
                                <span>Export</span>
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Financial Summary - ${period} ${year}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Key Metrics */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total Contributions</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {formatCurrency(financial_data.total_amount)}
                                    </p>
                                </div>
                                <div className="flex-shrink-0">
                                    <DollarSign className="h-8 w-8 text-green-600" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center">
                                {financial_data.period_growth >= 0 ? (
                                    <TrendingUp className="h-4 w-4 text-green-500 mr-1" />
                                ) : (
                                    <TrendingDown className="h-4 w-4 text-red-500 mr-1" />
                                )}
                                <span className={`text-sm font-medium ${
                                    financial_data.period_growth >= 0 ? 'text-green-600' : 'text-red-600'
                                }`}>
                                    {formatPercentage(financial_data.period_growth)}
                                </span>
                                <span className="text-sm text-gray-500 ml-1">vs previous period</span>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Average Contribution</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {formatCurrency(financial_data.statistics.average_contribution)}
                                    </p>
                                </div>
                                <div className="flex-shrink-0">
                                    <Target className="h-8 w-8 text-blue-600" />
                                </div>
                            </div>
                            <p className="mt-4 text-sm text-gray-500">
                                Per contribution transaction
                            </p>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total Contributors</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {financial_data.statistics.total_contributors}
                                    </p>
                                </div>
                                <div className="flex-shrink-0">
                                    <Users className="h-8 w-8 text-purple-600" />
                                </div>
                            </div>
                            <p className="mt-4 text-sm text-gray-500">
                                Unique contributing members
                            </p>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Highest Contribution</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {formatCurrency(financial_data.statistics.highest_single_contribution)}
                                    </p>
                                </div>
                                <div className="flex-shrink-0">
                                    <TrendingUp className="h-8 w-8 text-orange-600" />
                                </div>
                            </div>
                            <p className="mt-4 text-sm text-gray-500">
                                Single largest contribution
                            </p>
                        </div>
                    </div>

                    {/* Content based on selected view */}
                    {selectedView === 'overview' && (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            {/* Contribution by Type */}
                            <div className="bg-white rounded-lg shadow p-6">
                                <div className="flex items-center justify-between mb-6">
                                    <h3 className="text-lg font-medium text-gray-900">Contributions by Type</h3>
                                    <PieChart className="w-5 h-5 text-gray-400" />
                                </div>
                                <div className="space-y-4">
                                    {Object.entries(financial_data.by_type).map(([type, amount]) => {
                                        const percentage = totalByType > 0 ? (amount / totalByType) * 100 : 0;
                                        const colorClass = offeringTypeColors[type as keyof typeof offeringTypeColors];
                                        
                                        return (
                                            <div key={type} className="flex items-center justify-between">
                                                <div className="flex items-center space-x-3">
                                                    <div className={`w-3 h-3 rounded-full ${colorClass}`}></div>
                                                    <span className="text-sm font-medium text-gray-700">
                                                        {offeringTypeLabels[type as keyof typeof offeringTypeLabels]}
                                                    </span>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {formatCurrency(amount)}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {percentage.toFixed(1)}%
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Payment Methods */}
                            <div className="bg-white rounded-lg shadow p-6">
                                <div className="flex items-center justify-between mb-6">
                                    <h3 className="text-lg font-medium text-gray-900">Payment Methods</h3>
                                    <CreditCard className="w-5 h-5 text-gray-400" />
                                </div>
                                <div className="space-y-4">
                                    {Object.entries(financial_data.by_method).map(([method, amount]) => {
                                        const percentage = totalByMethod > 0 ? (amount / totalByMethod) * 100 : 0;
                                        const colorClass = paymentMethodColors[method as keyof typeof paymentMethodColors];
                                        
                                        return (
                                            <div key={method} className="flex items-center justify-between">
                                                <div className="flex items-center space-x-3">
                                                    <div className={`w-3 h-3 rounded-full ${colorClass}`}></div>
                                                    <span className="text-sm font-medium text-gray-700">
                                                        {paymentMethodLabels[method as keyof typeof paymentMethodLabels]}
                                                    </span>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {formatCurrency(amount)}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {percentage.toFixed(1)}%
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    )}

                    {selectedView === 'trends' && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-medium text-gray-900">Monthly Trends</h3>
                                <div className="flex space-x-2">
                                    <button
                                        onClick={() => setChartType('bar')}
                                        className={`p-2 rounded ${chartType === 'bar' ? 'bg-blue-100 text-blue-600' : 'text-gray-400'}`}
                                    >
                                        <BarChart3 className="w-4 h-4" />
                                    </button>
                                    <button
                                        onClick={() => setChartType('pie')}
                                        className={`p-2 rounded ${chartType === 'pie' ? 'bg-blue-100 text-blue-600' : 'text-gray-400'}`}
                                    >
                                        <PieChart className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            
                            {/* Monthly Data Table */}
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Month
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Tithe
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Offerings
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Special
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Projects
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {financial_data.monthly_data.map((month, index) => (
                                            <tr key={index} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {month.month}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                                    {formatCurrency(month.amount)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatCurrency(month.tithe)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatCurrency(month.offering)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatCurrency(month.special)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatCurrency(month.project)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {selectedView === 'breakdown' && (
                        <div className="space-y-8">
                            {/* Top Contributors */}
                            <div className="bg-white rounded-lg shadow p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-6 flex items-center">
                                    <Users className="w-5 h-5 mr-2 text-blue-600" />
                                    Top Contributors
                                </h3>
                                <div className="overflow-hidden">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Member
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Total Amount
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Contributions
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Average
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {financial_data.top_contributors.map((contributor, index) => (
                                                <tr key={index} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                                <span className="text-sm font-medium text-blue-600">
                                                                    #{index + 1}
                                                                </span>
                                                            </div>
                                                            <div className="ml-3">
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {contributor.member_name}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                                        {formatCurrency(contributor.total_amount)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {contributor.contribution_count}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {formatCurrency(contributor.total_amount / contributor.contribution_count)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Additional Statistics */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                <div className="flex items-center mb-4">
                                    <AlertCircle className="w-5 h-5 text-blue-600 mr-2" />
                                    <h3 className="text-lg font-medium text-blue-900">Key Insights</h3>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                                    <div>
                                        <strong>Most Active Month:</strong> {financial_data.statistics.most_active_month}
                                    </div>
                                    <div>
                                        <strong>Contribution Frequency:</strong> 
                                        {financial_data.statistics.total_contributors > 0 
                                            ? ` ${(financial_data.monthly_data.length / financial_data.statistics.total_contributors).toFixed(1)} contributions per member`
                                            : ' No data available'
                                        }
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}