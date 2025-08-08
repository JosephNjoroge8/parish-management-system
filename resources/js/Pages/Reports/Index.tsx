import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
    LineChart, Line, PieChart, Pie, Cell, AreaChart, Area
} from 'recharts';
import { 
    BarChart3, 
    PieChart as PieChartIcon, 
    TrendingUp, 
    Users, 
    DollarSign, 
    Calendar,
    Download,
    Eye,
    FileText,
    Church,
    Heart,
    Gift,
    Activity,
    Filter,
    UserPlus,
    Equal,
    TrendingDown,
    RefreshCw
} from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    permissions?: any;
}

interface Statistics {
    overview: {
        total_members: number;
        new_members: number;
        active_members: number;
        inactive_members: number;
        growth_rate: number;
    };
    demographics: {
        age_groups: {
            children: number;
            youth: number;
            adults: number;
            seniors: number;
        };
        gender_distribution: {
            male: number;
            female: number;
        };
        marital_status: Record<string, number>;
    };
    sacraments: {
        baptism?: number;
        confirmation?: number;
        first_communion?: number;
    };
    recent_activity: {
        new_registrations: any[];
        recent_updates: any[];
    };
    period_info: {
        period: string;
        generated_at: string;
    };
}

interface ChartData {
    monthly_trends: Array<{
        month: string;
        registrations: number;
        baptisms: number;
    }>;
    age_distribution: Array<{ name: string; value: number }>;
    gender_distribution: Array<{ name: string; value: number }>;
    status_distribution: Array<{ name: string; value: number }>;
}

interface ReportsIndexProps extends PageProps {
    auth: {
        user: User;
    };
    statistics: Statistics;
    charts: ChartData;
    filters: {
        periods: Record<string, string>;
        export_types: Record<string, string>;
        formats: Record<string, string>;
    };
}

export default function ReportsIndex({ auth, statistics, charts, filters }: ReportsIndexProps) {
    const [selectedPeriod, setSelectedPeriod] = useState('all');
    const [exportType, setExportType] = useState('all');
    const [exportFormat, setExportFormat] = useState('excel');
    const [customStartDate, setCustomStartDate] = useState('');
    const [customEndDate, setCustomEndDate] = useState('');
    const [loading, setLoading] = useState(false);
    const [currentStats, setCurrentStats] = useState(statistics);
    const [currentCharts, setCurrentCharts] = useState(charts);

    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

    // Permission checks
    const canViewReports = true; // Based on middleware protection
    const canExport = auth?.user?.permissions?.can_export_reports ?? true;

    const fetchUpdatedData = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.append('period', selectedPeriod);
            
            if (selectedPeriod === 'custom' && customStartDate && customEndDate) {
                params.append('start_date', customStartDate);
                params.append('end_date', customEndDate);
            }

            const response = await fetch(`/reports/statistics?${params.toString()}`);
            const data = await response.json();
            
            setCurrentStats(data.statistics);
            setCurrentCharts(data.charts);
        } catch (error) {
            console.error('Error fetching updated data:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (selectedPeriod !== 'all') {
            const timeoutId = setTimeout(() => {
                fetchUpdatedData();
            }, 300);

            return () => clearTimeout(timeoutId);
        }
    }, [selectedPeriod, customStartDate, customEndDate]);

    const handleExport = () => {
        const params = new URLSearchParams();
        params.append('type', exportType);
        params.append('period', selectedPeriod);
        params.append('format', exportFormat);
        
        if (selectedPeriod === 'custom' && customStartDate && customEndDate) {
            params.append('start_date', customStartDate);
            params.append('end_date', customEndDate);
        }

        window.open(`/reports/export?${params.toString()}`, '_blank');
    };

    const formatNumber = (num: number) => {
        return new Intl.NumberFormat().format(num);
    };

    const getGrowthTrend = (rate: number) => {
        if (rate > 0) return { icon: TrendingUp, color: 'text-green-600', bg: 'bg-green-100' };
        if (rate < 0) return { icon: TrendingDown, color: 'text-red-600', bg: 'bg-red-100' };
        return { icon: Equal, color: 'text-gray-600', bg: 'bg-gray-100' };
    };

    const GrowthIcon = getGrowthTrend(currentStats.overview.growth_rate).icon;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Parish Reports & Analytics
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Comprehensive reporting and analytics for parish management
                        </p>
                    </div>
                    <div className="text-sm text-gray-500">
                        Last updated: {new Date(currentStats.period_info.generated_at).toLocaleString()}
                    </div>
                </div>
            }
        >
            <Head title="Parish Reports" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Filter Controls */}
                    <div className="bg-white rounded-lg shadow p-6">
                        <div className="flex items-center gap-2 mb-4">
                            <Filter className="h-5 w-5 text-gray-600" />
                            <h3 className="text-lg font-medium text-gray-900">Report Filters & Export</h3>
                        </div>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="space-y-2">
                                <label htmlFor="period" className="block text-sm font-medium text-gray-700">Time Period</label>
                                <select
                                    id="period"
                                    value={selectedPeriod}
                                    onChange={(e) => setSelectedPeriod(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    {Object.entries(filters.periods).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </select>
                            </div>

                            {selectedPeriod === 'custom' && (
                                <>
                                    <div className="space-y-2">
                                        <label htmlFor="start-date" className="block text-sm font-medium text-gray-700">Start Date</label>
                                        <input
                                            id="start-date"
                                            type="date"
                                            value={customStartDate}
                                            onChange={(e) => setCustomStartDate(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <label htmlFor="end-date" className="block text-sm font-medium text-gray-700">End Date</label>
                                        <input
                                            id="end-date"
                                            type="date"
                                            value={customEndDate}
                                            onChange={(e) => setCustomEndDate(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                </>
                            )}

                            <div className="space-y-2">
                                <label htmlFor="export-type" className="block text-sm font-medium text-gray-700">Export Type</label>
                                <select
                                    id="export-type"
                                    value={exportType}
                                    onChange={(e) => setExportType(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                >
                                    {Object.entries(filters.export_types).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="export-format" className="block text-sm font-medium text-gray-700">Format</label>
                                <select
                                    id="export-format"
                                    value={exportFormat}
                                    onChange={(e) => setExportFormat(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                >
                                    {Object.entries(filters.formats).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="mt-4 flex gap-2">
                            <button
                                onClick={handleExport}
                                className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                            >
                                <Download className="h-4 w-4" />
                                Export Report
                            </button>
                            <button
                                onClick={fetchUpdatedData}
                                disabled={loading}
                                className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors disabled:opacity-50"
                            >
                                <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                                {loading ? 'Refreshing...' : 'Refresh Data'}
                            </button>
                        </div>
                    </div>

                    {/* Overview Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total Members</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatNumber(currentStats.overview.total_members)}</p>
                                    <p className="text-xs text-gray-500 mt-1">
                                        {formatNumber(currentStats.overview.active_members)} active
                                    </p>
                                </div>
                                <Users className="h-8 w-8 text-blue-600" />
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">New Members</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatNumber(currentStats.overview.new_members)}</p>
                                    <p className="text-xs text-gray-500 mt-1">In selected period</p>
                                </div>
                                <UserPlus className="h-8 w-8 text-green-600" />
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Growth Rate</p>
                                    <p className="text-2xl font-bold text-gray-900">{currentStats.overview.growth_rate}%</p>
                                    <p className="text-xs text-gray-500 mt-1">Member growth</p>
                                </div>
                                <GrowthIcon className={`h-8 w-8 ${getGrowthTrend(currentStats.overview.growth_rate).color}`} />
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Baptized</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatNumber(currentStats.sacraments.baptism || 0)}</p>
                                    <p className="text-xs text-gray-500 mt-1">Total baptized members</p>
                                </div>
                                <Church className="h-8 w-8 text-purple-600" />
                            </div>
                        </div>
                    </div>

                    {/* Charts Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Monthly Trends */}
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Monthly Registration Trends</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <AreaChart data={currentCharts.monthly_trends}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis />
                                    <Tooltip />
                                    <Legend />
                                    <Area type="monotone" dataKey="registrations" stackId="1" stroke="#8884d8" fill="#8884d8" name="New Registrations" />
                                    <Area type="monotone" dataKey="baptisms" stackId="1" stroke="#82ca9d" fill="#82ca9d" name="Baptisms" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Age Distribution */}
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Age Distribution</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={currentCharts.age_distribution}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {currentCharts.age_distribution.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Gender Distribution */}
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Gender Distribution</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={currentCharts.gender_distribution}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="value" fill="#8884d8" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Status Distribution */}
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Member Status</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={currentCharts.status_distribution}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {currentCharts.status_distribution.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Demographics Summary */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-2xl font-bold text-blue-600">{formatNumber(currentStats.demographics.age_groups.children)}</div>
                            <p className="text-sm text-gray-600">Children (0-17)</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-2xl font-bold text-green-600">{formatNumber(currentStats.demographics.age_groups.youth)}</div>
                            <p className="text-sm text-gray-600">Youth (18-35)</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-2xl font-bold text-orange-600">{formatNumber(currentStats.demographics.age_groups.adults)}</div>
                            <p className="text-sm text-gray-600">Adults (36-60)</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-2xl font-bold text-purple-600">{formatNumber(currentStats.demographics.age_groups.seniors)}</div>
                            <p className="text-sm text-gray-600">Seniors (60+)</p>
                        </div>
                    </div>

                    {/* Sacramental Statistics */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Baptisms</h3>
                                    <div className="text-3xl font-bold text-blue-600">{formatNumber(currentStats.sacraments.baptism || 0)}</div>
                                    <p className="text-sm text-gray-600">Total baptized members</p>
                                </div>
                                <Activity className="h-8 w-8 text-blue-600" />
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Confirmations</h3>
                                    <div className="text-3xl font-bold text-purple-600">{formatNumber(currentStats.sacraments.confirmation || 0)}</div>
                                    <p className="text-sm text-gray-600">Total confirmed members</p>
                                </div>
                                <Gift className="h-8 w-8 text-purple-600" />
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">First Communion</h3>
                                    <div className="text-3xl font-bold text-green-600">{formatNumber(currentStats.sacraments.first_communion || 0)}</div>
                                    <p className="text-sm text-gray-600">Total first communion</p>
                                </div>
                                <Church className="h-8 w-8 text-green-600" />
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Registrations</h3>
                            <div className="space-y-3">
                                {currentStats.recent_activity.new_registrations.slice(0, 5).map((member, index) => (
                                    <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <p className="font-medium">{member.first_name} {member.last_name}</p>
                                            <p className="text-sm text-gray-500">{member.email}</p>
                                        </div>
                                        <span className="text-sm text-gray-500">
                                            {new Date(member.created_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Statistics</h3>
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <span>Male Members</span>
                                    <span className="font-bold">{formatNumber(currentStats.demographics.gender_distribution.male)}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span>Female Members</span>
                                    <span className="font-bold">{formatNumber(currentStats.demographics.gender_distribution.female)}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span>Active Members</span>
                                    <span className="font-bold">{formatNumber(currentStats.overview.active_members)}</span>
                                </div>
                                {Object.entries(currentStats.demographics.marital_status).map(([status, count]) => (
                                    <div key={status} className="flex justify-between items-center">
                                        <span className="capitalize">{status.replace('_', ' ')}</span>
                                        <span className="font-bold">{formatNumber(count)}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Quick Export Actions */}
                    {canExport && (
                        <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg p-6">
                            <h3 className="text-lg font-semibold text-white mb-4">Quick Export Actions</h3>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <Link
                                    href="/reports/export/all"
                                    className="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 transition-all duration-200 flex items-center space-x-3 text-white"
                                >
                                    <Download className="w-5 h-5" />
                                    <span>Export All Data</span>
                                </Link>
                                
                                <Link
                                    href="/reports/export/members"
                                    className="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 transition-all duration-200 flex items-center space-x-3 text-white"
                                >
                                    <Users className="w-5 h-5" />
                                    <span>Export Members</span>
                                </Link>
                                
                                <button
                                    onClick={() => {
                                        setExportType('sacraments');
                                        handleExport();
                                    }}
                                    className="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 transition-all duration-200 flex items-center space-x-3 text-white"
                                >
                                    <Church className="w-5 h-5" />
                                    <span>Export Sacraments</span>
                                </button>
                                
                                <button
                                    onClick={() => {
                                        setExportType('marriages');
                                        handleExport();
                                    }}
                                    className="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-4 transition-all duration-200 flex items-center space-x-3 text-white"
                                >
                                    <Heart className="w-5 h-5" />
                                    <span>Export Marriages</span>
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}