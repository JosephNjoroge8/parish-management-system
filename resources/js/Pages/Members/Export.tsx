import React, { useState, useCallback, useMemo } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import {
    ArrowLeft,
    BarChart3,
    Filter,
    Settings,
    Download,
    Eye,
    RefreshCw,
    FileText,
    FileSpreadsheet,
    Loader2,
    CheckCircle,
    AlertCircle,
    Info,
    X,
    Plus,
    Minus,
    FileDown,
    ChevronDown,
    ChevronRight
} from 'lucide-react';

interface ExportStats {
    total_members: number;
    by_church: Record<string, number>;
    by_group: Record<string, number>;
    by_status: Record<string, number>;
    recent_exports: Array<{
        date: string;
        format: string;
        records: number;
        file_size: string;
    }>;
}

interface ExportProps extends PageProps {
    stats: ExportStats;
    filters?: {
        local_churches: string[];
        church_groups: Array<{value: string, label: string}>;
        membership_statuses: Array<{value: string, label: string}>;
        genders: Array<{value: string, label: string}>;
    };
}

interface PreviewMember {
    id: number;
    full_name: string;
    age: number | null;
    gender: string;
    church_group: string;
    membership_status: string;
    local_church: string;
    phone?: string;
    email?: string;
    family_name?: string;
}

export default function MembersExport({ auth, stats, filters }: ExportProps) {
    const [isExporting, setIsExporting] = useState(false);
    const [exportProgress, setExportProgress] = useState(0);
    const [showAdvancedOptions, setShowAdvancedOptions] = useState(false);
    const [previewData, setPreviewData] = useState<PreviewMember[]>([]);
    const [showPreview, setShowPreview] = useState(false);
    const [isLoadingPreview, setIsLoadingPreview] = useState(false);
    const [previewCount, setPreviewCount] = useState({ showing: 0, total: 0 });
    const [notification, setNotification] = useState<{type: 'success' | 'error' | 'warning' | 'info', message: string} | null>(null);

    const { data, setData, processing } = useForm({
        // Filters
        search: '',
        local_church: '',
        church_group: '',
        membership_status: '',
        gender: '',
        age_group: '',
        date_range: 'all',
        start_date: '',
        end_date: '',
        
        // Export Options
        format: 'csv' as 'csv' | 'excel' | 'pdf',
        include_family_info: true as boolean,
        include_contact_info: true as boolean,
        include_church_details: true as boolean,
        include_personal_info: true as boolean,
        date_format: 'Y-m-d' as 'Y-m-d' | 'd/m/Y' | 'm/d/Y',
        encoding: 'utf-8' as 'utf-8' | 'iso-8859-1',
        
        // Advanced options
        sort_by: 'last_name',
        sort_direction: 'asc',
        limit: '',
        selected_fields: [] as string[],
    });

    const safeStats = useMemo(() => ({
        total_members: stats?.total_members || 0,
        by_church: stats?.by_church || {},
        by_group: stats?.by_group || {},
        by_status: stats?.by_status || {},
        recent_exports: stats?.recent_exports || [],
    }), [stats]);

    const safeFilters = useMemo(() => ({
        local_churches: filters?.local_churches || [],
        church_groups: filters?.church_groups || [],
        membership_statuses: filters?.membership_statuses || [],
        genders: filters?.genders || [],
    }), [filters]);

    // Available fields for custom export
    const availableFields = useMemo(() => [
        { id: 'id', label: 'Member ID', category: 'basic' },
        { id: 'first_name', label: 'First Name', category: 'personal' },
        { id: 'middle_name', label: 'Middle Name', category: 'personal' },
        { id: 'last_name', label: 'Last Name', category: 'personal' },
        { id: 'date_of_birth', label: 'Date of Birth', category: 'personal' },
        { id: 'age', label: 'Age', category: 'personal' },
        { id: 'gender', label: 'Gender', category: 'personal' },
        { id: 'id_number', label: 'ID Number', category: 'personal' },
        { id: 'phone', label: 'Phone', category: 'contact' },
        { id: 'email', label: 'Email', category: 'contact' },
        { id: 'residence', label: 'Residence', category: 'contact' },
        { id: 'emergency_contact', label: 'Emergency Contact', category: 'contact' },
        { id: 'emergency_phone', label: 'Emergency Phone', category: 'contact' },
        { id: 'local_church', label: 'Local Church', category: 'church' },
        { id: 'church_group', label: 'Church Group', category: 'church' },
        { id: 'membership_status', label: 'Membership Status', category: 'church' },
        { id: 'membership_date', label: 'Membership Date', category: 'church' },
        { id: 'baptism_date', label: 'Baptism Date', category: 'church' },
        { id: 'confirmation_date', label: 'Confirmation Date', category: 'church' },
        { id: 'matrimony_status', label: 'Matrimony Status', category: 'church' },
        { id: 'occupation', label: 'Occupation', category: 'personal' },
        { id: 'education_level', label: 'Education Level', category: 'personal' },
        { id: 'family_name', label: 'Family Name', category: 'family' },
        { id: 'family_head', label: 'Family Head', category: 'family' },
        { id: 'parent', label: 'Parent/Guardian', category: 'family' },
        { id: 'sponsor', label: 'Sponsor', category: 'church' },
        { id: 'minister', label: 'Minister', category: 'church' },
        { id: 'tribe', label: 'Tribe', category: 'personal' },
        { id: 'clan', label: 'Clan', category: 'personal' },
        { id: 'notes', label: 'Notes', category: 'other' },
        { id: 'created_at', label: 'Created Date', category: 'other' },
        { id: 'updated_at', label: 'Updated Date', category: 'other' },
    ], []);

    // Get estimated export count
    const estimatedCount = useMemo(() => {
        let count = safeStats.total_members;
        
        if (data.local_church) {
            count = safeStats.by_church[data.local_church] || 0;
        }
        if (data.church_group) {
            count = Math.min(count, safeStats.by_group[data.church_group] || 0);
        }
        if (data.membership_status) {
            count = Math.min(count, safeStats.by_status[data.membership_status] || 0);
        }
        
        if (data.limit && parseInt(data.limit) > 0) {
            count = Math.min(count, parseInt(data.limit));
        }
        
        return count;
    }, [safeStats, data]);

    // Show notification
    const showNotification = useCallback((type: 'success' | 'error' | 'warning' | 'info', message: string) => {
        setNotification({ type, message });
        setTimeout(() => setNotification(null), 5000);
    }, []);

    // Handle field selection
    const handleFieldToggle = useCallback((fieldId: string) => {
        setData('selected_fields', 
            data.selected_fields.includes(fieldId)
                ? data.selected_fields.filter(id => id !== fieldId)
                : [...data.selected_fields, fieldId]
        );
    }, [data.selected_fields, setData]);

    // Select all fields in category
    const selectCategoryFields = useCallback((category: string) => {
        const categoryFields = availableFields
            .filter(field => field.category === category)
            .map(field => field.id);
        
        const newSelected = [...new Set([...data.selected_fields, ...categoryFields])];
        setData('selected_fields', newSelected);
    }, [availableFields, data.selected_fields, setData]);

    // Deselect all fields in category
    const deselectCategoryFields = useCallback((category: string) => {
        const categoryFields = availableFields
            .filter(field => field.category === category)
            .map(field => field.id);
        
        const newSelected = data.selected_fields.filter(id => !categoryFields.includes(id));
        setData('selected_fields', newSelected);
    }, [availableFields, data.selected_fields, setData]);

    // Handle export
    const handleExport = useCallback(async () => {
        setIsExporting(true);
        setExportProgress(10);

        try {
            const params = new URLSearchParams(
                Object.fromEntries(
                    Object.entries(data).map(([key, value]) => [
                        key, 
                        Array.isArray(value) ? value.join(',') : String(value)
                    ])
                )
            );

            setExportProgress(30);

            // Use the correct route
            const response = await fetch(`/members/export/download?${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, application/octet-stream',
                },
            });

            setExportProgress(60);

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `members_export_${new Date().toISOString().split('T')[0]}.${data.format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                setExportProgress(100);
                setTimeout(() => {
                    setExportProgress(0);
                    showNotification('success', `Export completed successfully! Downloaded ${estimatedCount} records as ${data.format.toUpperCase()}.`);
                }, 1000);
            } else {
                const errorText = await response.text();
                let errorMessage = 'Export failed';
                
                try {
                    const errorData = JSON.parse(errorText);
                    errorMessage = errorData.error || errorMessage;
                } catch {
                    errorMessage = errorText || errorMessage;
                }
                
                throw new Error(errorMessage);
            }
        } catch (error) {
            console.error('Export error:', error);
            setExportProgress(0);
            showNotification('error', error instanceof Error ? error.message : 'Export failed. Please try again.');
        } finally {
            setIsExporting(false);
        }
    }, [data, estimatedCount, showNotification]);

    // Load preview data
    const loadPreview = useCallback(async () => {
        setIsLoadingPreview(true);
        try {
            const params = new URLSearchParams(
                Object.fromEntries(
                    Object.entries(data).map(([key, value]) => [
                        key, 
                        Array.isArray(value) ? value.join(',') : String(value)
                    ])
                )
            );

            const response = await fetch(`/members/export/preview?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const result = await response.json();
                setPreviewData(result.preview_data || []);
                setPreviewCount({
                    showing: result.showing || 0,
                    total: result.total_count || 0
                });
                setShowPreview(true);
            } else {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Failed to load preview');
            }
        } catch (error) {
            console.error('Preview error:', error);
            showNotification('error', error instanceof Error ? error.message : 'Failed to load preview');
        } finally {
            setIsLoadingPreview(false);
        }
    }, [data, showNotification]);

    // Group fields by category
    const fieldsByCategory = useMemo(() => {
        const categories = ['basic', 'personal', 'contact', 'church', 'family', 'other'];
        return categories.reduce((acc, category) => {
            acc[category] = availableFields.filter(field => field.category === category);
            return acc;
        }, {} as Record<string, typeof availableFields>);
    }, [availableFields]);

    // Get category selection status
    const getCategoryStatus = useCallback((category: string) => {
        const categoryFields = fieldsByCategory[category] || [];
        const selectedCount = categoryFields.filter(field => data.selected_fields.includes(field.id)).length;
        
        if (selectedCount === 0) return 'none';
        if (selectedCount === categoryFields.length) return 'all';
        return 'partial';
    }, [fieldsByCategory, data.selected_fields]);

    // Get category display name
    const getCategoryDisplayName = useCallback((category: string) => {
        const names = {
            basic: 'Basic Information',
            personal: 'Personal Details',
            contact: 'Contact Information',
            church: 'Church Details',
            family: 'Family Information',
            other: 'Other Fields'
        };
        return names[category as keyof typeof names] || category;
    }, []);

    return (
        <AuthenticatedLayout
     
            header={
                <div className="flex items-center gap-4">
                    <Link
                        href={route('members.index')}
                        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                        Back to Members
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Export Members
                    </h2>
                </div>
            }
        >
            <Head title="Export Members" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Notification */}
                    {notification && (
                        <div className={`mb-6 p-4 rounded-lg flex items-center gap-3 ${
                            notification.type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
                            notification.type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
                            notification.type === 'warning' ? 'bg-yellow-50 text-yellow-800 border border-yellow-200' :
                            'bg-blue-50 text-blue-800 border border-blue-200'
                        }`}>
                            {notification.type === 'success' && <CheckCircle className="w-5 h-5 flex-shrink-0" />}
                            {notification.type === 'error' && <AlertCircle className="w-5 h-5 flex-shrink-0" />}
                            {notification.type === 'warning' && <AlertCircle className="w-5 h-5 flex-shrink-0" />}
                            {notification.type === 'info' && <Info className="w-5 h-5 flex-shrink-0" />}
                            <span className="flex-1">{notification.message}</span>
                            <button
                                onClick={() => setNotification(null)}
                                className="text-current hover:opacity-70"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Export Form */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Export Statistics */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center gap-2">
                                        <BarChart3 className="w-5 h-5" />
                                        Export Statistics
                                    </h3>
                                </div>
                                <div className="p-6">
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                        <div className="text-center p-4 bg-blue-50 rounded-lg">
                                            <div className="text-2xl font-bold text-blue-600">{safeStats.total_members}</div>
                                            <div className="text-sm text-blue-800">Total Members</div>
                                        </div>
                                        <div className="text-center p-4 bg-green-50 rounded-lg">
                                            <div className="text-2xl font-bold text-green-600">{Object.keys(safeStats.by_church).length}</div>
                                            <div className="text-sm text-green-800">Churches</div>
                                        </div>
                                        <div className="text-center p-4 bg-purple-50 rounded-lg">
                                            <div className="text-2xl font-bold text-purple-600">{Object.keys(safeStats.by_group).length}</div>
                                            <div className="text-sm text-purple-800">Groups</div>
                                        </div>
                                        <div className="text-center p-4 bg-orange-50 rounded-lg">
                                            <div className="text-2xl font-bold text-orange-600">{estimatedCount}</div>
                                            <div className="text-sm text-orange-800">Estimated Export</div>
                                        </div>
                                    </div>

                                    {/* Recent Exports */}
                                    {safeStats.recent_exports.length > 0 && (
                                        <div>
                                            <h4 className="text-sm font-medium text-gray-900 mb-3">Recent Exports</h4>
                                            <div className="space-y-2">
                                                {safeStats.recent_exports.map((export_, index) => (
                                                    <div key={index} className="flex justify-between items-center text-sm bg-gray-50 p-3 rounded">
                                                        <div className="flex items-center gap-2">
                                                            <FileDown className="w-4 h-4 text-gray-500" />
                                                            <span>{export_.date}</span>
                                                            <span className="text-gray-500">({export_.format.toUpperCase()})</span>
                                                        </div>
                                                        <div className="text-gray-600">
                                                            {export_.records} records ({export_.file_size})
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Filters */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center gap-2">
                                        <Filter className="w-5 h-5" />
                                        Filter Members
                                    </h3>
                                </div>
                                <div className="p-6 space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Search
                                            </label>
                                            <input
                                                type="text"
                                                value={data.search}
                                                onChange={(e) => setData('search', e.target.value)}
                                                placeholder="Search by name, phone, email..."
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Local Church
                                            </label>
                                            <select
                                                value={data.local_church}
                                                onChange={(e) => setData('local_church', e.target.value)}
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Churches</option>
                                                {safeFilters.local_churches.map((church) => (
                                                    <option key={church} value={church}>{church}</option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Church Group
                                            </label>
                                            <select
                                                value={data.church_group}
                                                onChange={(e) => setData('church_group', e.target.value)}
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Groups</option>
                                                {safeFilters.church_groups.map((group) => (
                                                    <option key={group.value} value={group.value}>{group.label}</option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Membership Status
                                            </label>
                                            <select
                                                value={data.membership_status}
                                                onChange={(e) => setData('membership_status', e.target.value)}
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Statuses</option>
                                                {safeFilters.membership_statuses.map((status) => (
                                                    <option key={status.value} value={status.value}>{status.label}</option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Gender
                                            </label>
                                            <select
                                                value={data.gender}
                                                onChange={(e) => setData('gender', e.target.value)}
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Genders</option>
                                                {safeFilters.genders.map((gender) => (
                                                    <option key={gender.value} value={gender.value}>{gender.label}</option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Age Group
                                            </label>
                                            <select
                                                value={data.age_group}
                                                onChange={(e) => setData('age_group', e.target.value)}
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="">All Ages</option>
                                                <option value="children">Children (0-12)</option>
                                                <option value="youth">Youth (13-24)</option>
                                                <option value="adults">Adults (25-59)</option>
                                                <option value="seniors">Seniors (60+)</option>
                                            </select>
                                        </div>
                                    </div>

                                    {/* Date Range */}
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Date Range
                                            </label>
                                            <select
                                                value={data.date_range}
                                                onChange={(e) => setData('date_range', e.target.value)}
                                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="all">All Time</option>
                                                <option value="this_year">This Year</option>
                                                <option value="last_year">Last Year</option>
                                                <option value="last_6_months">Last 6 Months</option>
                                                <option value="last_30_days">Last 30 Days</option>
                                                <option value="custom">Custom Range</option>
                                            </select>
                                        </div>

                                        {data.date_range === 'custom' && (
                                            <>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Start Date
                                                    </label>
                                                    <input
                                                        type="date"
                                                        value={data.start_date}
                                                        onChange={(e) => setData('start_date', e.target.value)}
                                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        End Date
                                                    </label>
                                                    <input
                                                        type="date"
                                                        value={data.end_date}
                                                        onChange={(e) => setData('end_date', e.target.value)}
                                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                    />
                                                </div>
                                            </>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Limit Records
                                        </label>
                                        <input
                                            type="number"
                                            value={data.limit}
                                            onChange={(e) => setData('limit', e.target.value)}
                                            placeholder="Leave empty for all records"
                                            min="1"
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Export Options */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center gap-2">
                                        <Settings className="w-5 h-5" />
                                        Export Options
                                    </h3>
                                </div>
                                <div className="p-6 space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Export Format
                                        </label>
                                        <div className="grid grid-cols-3 gap-4">
                                            <button
                                                onClick={() => setData('format', 'csv')}
                                                className={`p-4 border rounded-lg text-center transition-colors ${
                                                    data.format === 'csv'
                                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                        : 'border-gray-300 hover:border-gray-400'
                                                }`}
                                            >
                                                <FileText className="w-8 h-8 mx-auto mb-2" />
                                                <div className="font-medium">CSV</div>
                                                <div className="text-xs text-gray-500">Comma Separated</div>
                                            </button>

                                            <button
                                                onClick={() => setData('format', 'excel')}
                                                className={`p-4 border rounded-lg text-center transition-colors ${
                                                    data.format === 'excel'
                                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                        : 'border-gray-300 hover:border-gray-400'
                                                }`}
                                            >
                                                <FileSpreadsheet className="w-8 h-8 mx-auto mb-2" />
                                                <div className="font-medium">Excel</div>
                                                <div className="text-xs text-gray-500">XLSX Format</div>
                                            </button>

                                            <button
                                                onClick={() => setData('format', 'pdf')}
                                                className={`p-4 border rounded-lg text-center transition-colors ${
                                                    data.format === 'pdf'
                                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                        : 'border-gray-300 hover:border-gray-400'
                                                }`}
                                            >
                                                <FileText className="w-8 h-8 mx-auto mb-2" />
                                                <div className="font-medium">PDF</div>
                                                <div className="text-xs text-gray-500">Portable Document</div>
                                            </button>
                                        </div>
                                    </div>

                                    {/* Include Options */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Include Information
                                        </label>
                                        <div className="space-y-2">
                                            <label className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={data.include_personal_info}
                                                    onChange={(e) => setData('include_personal_info', e.target.checked)}
                                                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                />
                                                <span className="ml-2 text-sm text-gray-900">Personal Information</span>
                                            </label>

                                            <label className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={data.include_contact_info}
                                                    onChange={(e) => setData('include_contact_info', e.target.checked)}
                                                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                />
                                                <span className="ml-2 text-sm text-gray-900">Contact Information</span>
                                            </label>

                                            <label className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={data.include_church_details}
                                                    onChange={(e) => setData('include_church_details', e.target.checked)}
                                                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                />
                                                <span className="ml-2 text-sm text-gray-900">Church Details</span>
                                            </label>

                                            <label className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={data.include_family_info}
                                                    onChange={(e) => setData('include_family_info', e.target.checked)}
                                                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                />
                                                <span className="ml-2 text-sm text-gray-900">Family Information</span>
                                            </label>
                                        </div>
                                    </div>

                                    {/* Advanced Options */}
                                    <div className="border-t pt-4">
                                        <button
                                            onClick={() => setShowAdvancedOptions(!showAdvancedOptions)}
                                            className="flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            <Settings className="w-4 h-4" />
                                            Advanced Options
                                            {showAdvancedOptions ? <Minus className="w-4 h-4" /> : <Plus className="w-4 h-4" />}
                                        </button>

                                        {showAdvancedOptions && (
                                            <div className="mt-4 space-y-4 pl-6 border-l-2 border-gray-200">
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                                            Sort By
                                                        </label>
                                                        <select
                                                            value={data.sort_by}
                                                            onChange={(e) => setData('sort_by', e.target.value)}
                                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                        >
                                                            <option value="last_name">Last Name</option>
                                                            <option value="first_name">First Name</option>
                                                            <option value="age">Age</option>
                                                            <option value="membership_date">Membership Date</option>
                                                            <option value="created_at">Created Date</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                                            Sort Direction
                                                        </label>
                                                        <select
                                                            value={data.sort_direction}
                                                            onChange={(e) => setData('sort_direction', e.target.value)}
                                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                        >
                                                            <option value="asc">Ascending</option>
                                                            <option value="desc">Descending</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                                            Date Format
                                                        </label>
                                                        <select
                                                            value={data.date_format}
                                                            onChange={(e) => setData('date_format', e.target.value as 'Y-m-d' | 'd/m/Y' | 'm/d/Y')}
                                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                        >
                                                            <option value="Y-m-d">YYYY-MM-DD</option>
                                                            <option value="d/m/Y">DD/MM/YYYY</option>
                                                            <option value="m/d/Y">MM/DD/YYYY</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                                            Encoding
                                                        </label>
                                                        <select
                                                            value={data.encoding}
                                                            onChange={(e) => setData('encoding', e.target.value as 'utf-8' | 'iso-8859-1')}
                                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                        >
                                                            <option value="utf-8">UTF-8</option>
                                                            <option value="iso-8859-1">ISO-8859-1</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                {/* Custom Field Selection */}
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                                        Custom Field Selection
                                                    </label>
                                                    <div className="space-y-4">
                                                        {Object.entries(fieldsByCategory).map(([category, fields]) => (
                                                            <div key={category} className="border rounded-lg p-4">
                                                                <div className="flex items-center justify-between mb-3">
                                                                    <h4 className="font-medium text-gray-900">
                                                                        {getCategoryDisplayName(category)}
                                                                    </h4>
                                                                    <div className="flex items-center gap-2">
                                                                        <button
                                                                            onClick={() => selectCategoryFields(category)}
                                                                            className="text-xs text-blue-600 hover:text-blue-800"
                                                                        >
                                                                            Select All
                                                                        </button>
                                                                        <button
                                                                            onClick={() => deselectCategoryFields(category)}
                                                                            className="text-xs text-gray-600 hover:text-gray-800"
                                                                        >
                                                                            Deselect All
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                                    {fields.map((field) => (
                                                                        <label key={field.id} className="flex items-center text-sm">
                                                                            <input
                                                                                type="checkbox"
                                                                                checked={data.selected_fields.includes(field.id)}
                                                                                onChange={() => handleFieldToggle(field.id)}
                                                                                className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-2"
                                                                            />
                                                                            {field.label}
                                                                        </label>
                                                                    ))}
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Actions Sidebar */}
                        <div className="space-y-6">
                            {/* Export Actions */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Export Actions</h3>
                                    
                                    <div className="space-y-4">
                                        <button
                                            onClick={loadPreview}
                                            disabled={isLoadingPreview}
                                            className="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-4 rounded-lg transition-colors disabled:opacity-50"
                                        >
                                            {isLoadingPreview ? (
                                                <Loader2 className="w-5 h-5 animate-spin" />
                                            ) : (
                                                <Eye className="w-5 h-5" />
                                            )}
                                            {isLoadingPreview ? 'Loading...' : 'Preview Data'}
                                        </button>

                                        <button
                                            onClick={handleExport}
                                            disabled={isExporting || processing}
                                            className="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors disabled:opacity-50"
                                        >
                                            {isExporting ? (
                                                <Loader2 className="w-5 h-5 animate-spin" />
                                            ) : (
                                                <Download className="w-5 h-5" />
                                            )}
                                            {isExporting ? 'Exporting...' : 'Export Data'}
                                        </button>

                                        {isExporting && exportProgress > 0 && (
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                    style={{ width: `${exportProgress}%` }}
                                                ></div>
                                            </div>
                                        )}

                                        <button
                                            onClick={() => window.location.reload()}
                                            className="w-full flex items-center justify-center gap-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition-colors"
                                        >
                                            <RefreshCw className="w-5 h-5" />
                                            Reset Filters
                                        </button>
                                    </div>

                                    <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                                        <div className="text-sm text-blue-800">
                                            <strong>Estimated Export:</strong><br />
                                            {estimatedCount} records<br />
                                            Format: {data.format.toUpperCase()}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Stats */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                                    
                                    <div className="space-y-3">
                                        <div className="flex justify-between items-center text-sm">
                                            <span className="text-gray-600">Total Members:</span>
                                            <span className="font-medium">{safeStats.total_members}</span>
                                        </div>
                                        
                                        {Object.entries(safeStats.by_status).slice(0, 3).map(([status, count]) => (
                                            <div key={status} className="flex justify-between items-center text-sm">
                                                <span className="text-gray-600">{status}:</span>
                                                <span className="font-medium">{count}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Preview Modal */}
                    {showPreview && (
                        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                            <div className="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Data Preview ({previewCount.showing} of {previewCount.total} records)
                                    </h3>
                                    <button
                                        onClick={() => setShowPreview(false)}
                                        className="text-gray-400 hover:text-gray-600"
                                    >
                                        <X className="w-6 h-6" />
                                    </button>
                                </div>

                                <div className="max-h-96 overflow-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Church</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {previewData.map((member) => (
                                                <tr key={member.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {member.full_name}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.age || 'N/A'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.gender}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.local_church}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.church_group}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.membership_status}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="mt-4 flex justify-end">
                                    <button
                                        onClick={() => setShowPreview(false)}
                                        className="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}