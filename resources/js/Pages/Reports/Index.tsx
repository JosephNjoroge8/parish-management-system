import React, { useState, useEffect, useCallback, useMemo } from 'react';
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
    FileSpreadsheet,
    FileDown,
    Church,
    Heart,
    Gift,
    Activity,
    Filter,
    UserPlus,
    Equal,
    TrendingDown,
    RefreshCw,
    Search,
    X,
    ChevronDown,
    Award,
    GraduationCap,
    MapPin,
    Crown,
    Star,
    UserCheck,
    BookOpen
} from 'lucide-react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    permissions?: any;
}

interface EnhancedStatistics {
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
        education_levels: Record<string, number>;
        occupations: Record<string, number>;
    };
    church_groups: {
        primary_groups: Record<string, number>;
        additional_memberships: Record<string, number>;
        total_group_memberships: number;
    };
    local_churches: Record<string, number>;
    small_christian_communities: Record<string, number>;
    sacraments: {
        baptized: number;
        confirmed: number;
        married: number;
        marriage_types: Record<string, number>;
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

interface EnhancedChartData {
    monthly_trends: Array<{
        month: string;
        registrations: number;
        baptisms: number;
        confirmations: number;
    }>;
    age_distribution: Array<{ name: string; value: number }>;
    gender_distribution: Array<{ name: string; value: number }>;
    status_distribution: Array<{ name: string; value: number }>;
    church_groups_distribution: Array<{ name: string; value: number }>;
    education_levels_distribution: Array<{ name: string; value: number }>;
    local_churches_distribution: Array<{ name: string; value: number }>;
    marriage_types_distribution: Array<{ name: string; value: number }>;
}

interface AdvancedFilters {
    church_group?: string;
    additional_church_groups?: string[];
    local_church?: string;
    small_christian_community?: string;
    education_level?: string;
    occupation?: string;
    matrimony_status?: string;
    marriage_type?: string;
    age_min?: number;
    age_max?: number;
    gender?: string;
    membership_status?: string;
    has_baptism?: boolean;
    has_confirmation?: boolean;
    tribe?: string;
    clan?: string;
}

interface ReportsIndexProps extends PageProps {
    auth: {
        user: User;
    };
    statistics: EnhancedStatistics;
    charts: EnhancedChartData;
    filters: {
        periods: Record<string, string>;
        export_types: Record<string, string>;
        formats: Record<string, string>;
        church_groups: Record<string, string>;
        local_churches: Record<string, string>;
        education_levels: Record<string, string>;
        occupations: Record<string, string>;
        communities: string[];
    };
}

export default function EnhancedReportsIndex({ auth, statistics, charts, filters }: ReportsIndexProps) {
    const [selectedPeriod, setSelectedPeriod] = useState('all');
    const [exportType, setExportType] = useState('all');
    const [exportFormat, setExportFormat] = useState('excel');
    const [customStartDate, setCustomStartDate] = useState('');
    const [customEndDate, setCustomEndDate] = useState('');
    const [loading, setLoading] = useState(false);
    const [currentStats, setCurrentStats] = useState(statistics);
    const [currentCharts, setCurrentCharts] = useState(charts);
    
    // Advanced filtering state
    const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
    const [advancedFilters, setAdvancedFilters] = useState<AdvancedFilters>({});
    const [activeFiltersCount, setActiveFiltersCount] = useState(0);
    
    // Toast notification state
    const [toast, setToast] = useState<{message: string; type: 'success' | 'error' | 'info'} | null>(null);
    
    // Member viewing state
    const [showMemberModal, setShowMemberModal] = useState(false);
    const [currentMembers, setCurrentMembers] = useState<any[]>([]);
    const [memberViewTitle, setMemberViewTitle] = useState('');
    const [memberViewLoading, setMemberViewLoading] = useState(false);

    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D', '#FFC658', '#FF7C7C'];

    // Permission checks
    const canViewReports = true;
    const canExport = auth?.user?.permissions?.can_export_reports ?? true;

    // Toast notification helper
    const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
        setToast({message, type});
        setTimeout(() => setToast(null), 5000);
    };

    // Helper function to export members by category with working backend routes
    const exportByCategory = async (category: string, value: string = 'all', format: string = 'excel') => {
        if (!canExport) {
            showToast('You do not have permission to export reports', 'error');
            return;
        }

        setLoading(true);
        showToast(`Preparing ${category.replace('_', ' ').toUpperCase()} export in ${format.toUpperCase()} format...`, 'info');

        try {
            // Map frontend categories to backend route names - Complete synchronization
            const routeMap: Record<string, string> = {
                // Church and organizational exports
                'local_church': 'export-by-local-church',
                'church_group': 'export-by-church-group', 
                'age_group': 'export-by-age-group',
                'gender': 'export-by-gender',
                
                // Membership status exports
                'membership_status': 'export-by-membership-status',
                'marital_status': 'export-by-marital-status',
                'matrimony_status': 'export-by-marital-status',
                'marriage_type': 'export-by-marital-status',
                
                // Geographic exports
                'state': 'export-by-state',
                'lga': 'export-by-lga',
                
                // Personal information exports
                'education_level': 'export-by-education-level',
                'education': 'export-by-education-level',
                'occupation': 'export-by-occupation',
                'tribe': 'export-by-tribe',
                
                // Community exports
                'community': 'export-by-community',
                'small_christian_community': 'export-by-community',
                
                // Time-based exports
                'year_joined': 'export-by-year-joined',
                'monthly_trends': 'export-members-data',
                
                // Sacrament-based exports
                'baptized': 'export-baptized-members',
                'confirmed': 'export-confirmed-members',
                'married': 'export-married-members',
                
                // Special reports
                'comprehensive': 'export-comprehensive',
                'directory': 'export-member-directory',
                'all_records': 'export-members-data',
                
                // Legacy compatibility
                'all': 'export-members-data'
            };

            const routeName = routeMap[category] || 'export-members-data';
            const url = `/reports/${routeName}?value=${encodeURIComponent(value)}&format=${format}`;

            console.log('Export request:', { category, value, format, url });
            showToast(`Requesting: ${url}`, 'info');

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/octet-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                }
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                // Get detailed error information with enhanced handling
                let errorMessage = `Export failed: ${response.status} ${response.statusText}`;
                try {
                    const errorText = await response.text();
                    if (errorText) {
                        // Check for specific error types
                        if (response.status === 403 || errorText.includes('permission') || errorText.includes('unauthorized')) {
                            errorMessage = 'You do not have permission to export reports. Please contact your administrator.';
                        } else if (response.status === 404) {
                            errorMessage = `Export route not found: /reports/${routeName}. The backend route may be missing or incorrectly configured.`;
                        } else if (response.status === 422) {
                            errorMessage = 'Invalid export parameters. Please check your filter settings and try again.';
                        } else if (response.status === 500) {
                            // Extract meaningful error from Laravel error page if possible
                            const match = errorText.match(/<title>(.*?)<\/title>/);
                            if (match) {
                                errorMessage = `Server error: ${match[1]}`;
                            } else if (errorText.includes('Exception')) {
                                const exceptionMatch = errorText.match(/Exception.*?in.*?line \d+/);
                                if (exceptionMatch) {
                                    errorMessage = `Server error: ${exceptionMatch[0]}`;
                                }
                            } else {
                                errorMessage = 'Internal server error occurred while generating export.';
                            }
                        } else if (response.status === 413) {
                            errorMessage = 'Export data too large. Please apply more specific filters to reduce the dataset size.';
                        } else if (response.status === 408) {
                            errorMessage = 'Export request timed out. Please try with more specific filters or a smaller date range.';
                        }
                        console.error('Export error details:', {
                            status: response.status,
                            category,
                            value,
                            format,
                            url,
                            errorText: errorText.substring(0, 500)
                        });
                    }
                } catch (e) {
                    console.error('Could not parse error response:', e);
                    errorMessage = `Network error occurred. Status: ${response.status}`;
                }
                throw new Error(errorMessage);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/')) {
                const errorText = await response.text();
                throw new Error(`Invalid response format: ${errorText}`);
            }

            const blob = await response.blob();
            if (blob.size === 0) {
                throw new Error('Export returned empty file');
            }

            const mimeTypes = {
                excel: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                csv: 'text/csv',
                pdf: 'application/pdf'
            };

            const extensions = {
                excel: 'xlsx',
                csv: 'csv',
                pdf: 'pdf'
            };

            const filename = `${category}_${value.replace(/[^a-zA-Z0-9]/g, '_')}_members_${new Date().toISOString().split('T')[0]}.${extensions[format as keyof typeof extensions]}`;
            
            const downloadBlob = new Blob([blob], { type: mimeTypes[format as keyof typeof mimeTypes] });
            const url2 = URL.createObjectURL(downloadBlob);
            const a = document.createElement('a');
            a.href = url2;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url2);

            showToast(`${category.replace('_', ' ').toUpperCase()} export completed successfully!`, 'success');
        } catch (error) {
            console.error('Export error:', error);
            const errorMessage = error instanceof Error ? error.message : 'An unknown error occurred';
            showToast(`Export failed: ${errorMessage}`, 'error');
        } finally {
            setLoading(false);
        }
    };

    // Helper function to export filtered members with enhanced validation
    const exportFilteredMembers = async (format: string = 'excel') => {
        if (!canExport) {
            showToast('You do not have permission to export reports', 'error');
            return;
        }

        // Validate filters before sending
        const filterCount = Object.values(advancedFilters).filter(value => {
            if (Array.isArray(value)) return value.length > 0;
            if (typeof value === 'boolean') return value === true;
            return value !== undefined && value !== '' && value !== null;
        }).length;

        if (filterCount === 0 && selectedPeriod === 'all') {
            const confirmed = window.confirm('No filters applied. This will export ALL members. Continue?');
            if (!confirmed) return;
        }

        try {
            setLoading(true);
            showToast(`Preparing filtered export with ${filterCount} active filters...`, 'info');

            const exportData = {
                ...advancedFilters,
                period: selectedPeriod,
                start_date: customStartDate,
                end_date: customEndDate,
                format,
                timestamp: new Date().toISOString()
            };

            console.log('Filtered export request:', exportData);

            const response = await fetch('/reports/export/filtered', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/octet-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(exportData)
            });

            if (!response.ok) {
                const errorText = await response.text();
                let errorMessage = `Filtered export failed: ${response.status} ${response.statusText}`;
                
                if (response.status === 422) {
                    errorMessage = 'Invalid filter parameters. Please check your filter settings.';
                } else if (response.status === 413) {
                    errorMessage = 'Too much data selected. Please apply more specific filters.';
                }
                
                console.error('Filtered export error:', { status: response.status, errorText });
                throw new Error(errorMessage);
            }

            const blob = await response.blob();
            
            if (blob.size === 0) {
                showToast('No data found matching your filters', 'info');
                return;
            }

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            
            const timestamp = new Date().toISOString().split('T')[0];
            const extension = format === 'excel' ? 'xlsx' : (format === 'csv' ? 'csv' : 'pdf');
            a.download = `filtered-members-${filterCount}filters-${timestamp}.${extension}`;
            
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            showToast(`Filtered export completed successfully! (${(blob.size / (1024 * 1024)).toFixed(2)} MB)`, 'success');
        } catch (error) {
            console.error('Filtered export failed:', error);
            const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
            showToast(`Filtered export failed: ${errorMessage}`, 'error');
        } finally {
            setLoading(false);
        }
    };

    // Helper function to download member lists by category with enhanced error handling
    const downloadMemberList = async (type: string, value: string) => {
        if (!canExport) {
            showToast('You do not have permission to export reports', 'error');
            return;
        }

        setLoading(true);
        showToast(`Preparing ${type.replace('_', ' ')} list for ${value}...`, 'info');

        try {
            // Use the same route mapping as exportByCategory for consistency
            const routeMap: Record<string, string> = {
                'local_church': 'export-by-local-church',
                'church_group': 'export-by-church-group',
                'age_group': 'export-by-age-group',
                'gender': 'export-by-gender',
                'status': 'export-by-membership-status'
            };

            let url = '';
            let actualValue = value;
            
            // Map status values to what backend expects
            if (type === 'status') {
                const statusMap: Record<string, string> = {
                    'active': 'Active',
                    'inactive': 'Inactive', 
                    'transferred': 'Transferred',
                    'deceased': 'Deceased'
                };
                actualValue = statusMap[value] || value;
                url = `/reports/export-by-membership-status?value=${encodeURIComponent(actualValue)}&format=excel`;
            } else {
                const routeName = routeMap[type] || 'export-members-data';
                url = `/reports/${routeName}?value=${encodeURIComponent(actualValue)}&format=excel`;
            }

            console.log('Download request:', { type, value, actualValue, url });

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/octet-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                }
            });

            if (!response.ok) {
                let errorMessage = `Download failed: ${response.status} ${response.statusText}`;
                if (response.status === 404) {
                    errorMessage = 'Export endpoint not found. Please check your configuration.';
                } else if (response.status === 403) {
                    errorMessage = 'You do not have permission to download this report.';
                } else if (response.status === 500) {
                    errorMessage = 'Server error occurred while generating the report.';
                }
                throw new Error(errorMessage);
            }

            const blob = await response.blob();
            if (blob.size === 0) {
                throw new Error('Export returned empty file');
            }

            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `${type}-${value.replace(/\s+/g, '-').toLowerCase()}-members-${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);

            showToast(`${type.replace('_', ' ')} list downloaded successfully!`, 'success');
        } catch (error) {
            console.error('Download failed:', error);
            const errorMessage = error instanceof Error ? error.message : 'Download failed. Please try again.';
            showToast(errorMessage, 'error');
        } finally {
            setLoading(false);
        }
    };

    // Helper function to download all clear records with enhanced functionality
    const downloadAllClearRecords = async (format: string = 'excel') => {
        if (!canExport) {
            showToast('You do not have permission to export reports', 'error');
            return;
        }

        setLoading(true);
        showToast(`Preparing complete records in ${format.toUpperCase()} format...`, 'info');

        try {
            const url = `/reports/export-members-data?value=all&format=${format}`;
            
            console.log('All records download request:', { format, url });

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/octet-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                }
            });

            if (!response.ok) {
                let errorMessage = `Download failed: ${response.status} ${response.statusText}`;
                if (response.status === 404) {
                    errorMessage = 'Export endpoint not found. Please check your configuration.';
                } else if (response.status === 403) {
                    errorMessage = 'You do not have permission to download this report.';
                } else if (response.status === 500) {
                    errorMessage = 'Server error occurred while generating the report.';
                }
                throw new Error(errorMessage);
            }

            const blob = await response.blob();
            if (blob.size === 0) {
                throw new Error('Export returned empty file');
            }

            const url2 = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url2;
            
            const fileExtension = format === 'excel' ? 'xlsx' : (format === 'csv' ? 'csv' : 'pdf');
            a.download = `all-clear-records-${new Date().toISOString().split('T')[0]}.${fileExtension}`;
            
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url2);
            document.body.removeChild(a);

            showToast(`Complete records downloaded successfully in ${format.toUpperCase()} format!`, 'success');
        } catch (error) {
            console.error('Download failed:', error);
            const errorMessage = error instanceof Error ? error.message : 'Download failed. Please try again.';
            showToast(errorMessage, 'error');
        } finally {
            setLoading(false);
        }
    };

    // Helper function to view member lists before downloading
    const viewMemberList = async (type: string, value: string) => {
        setMemberViewLoading(true);
        setMemberViewTitle(`${type.replace('_', ' ')} - ${value}`);
        setShowMemberModal(true);

        try {
            // Use a different endpoint for viewing (returns JSON instead of file)
            let url = '';
            let actualValue = value;
            
            if (type === 'status') {
                const statusMap: Record<string, string> = {
                    'active': 'Active',
                    'inactive': 'Inactive', 
                    'transferred': 'Transferred',
                    'deceased': 'Deceased'
                };
                actualValue = statusMap[value] || value;
            }

            // Create a view URL that returns JSON data
            const routeMap: Record<string, string> = {
                'local_church': 'export-by-local-church',
                'church_group': 'export-by-church-group',
                'age_group': 'export-by-age-group',
                'gender': 'export-by-gender',
                'status': 'export-by-membership-status'
            };

            const routeName = routeMap[type] || 'export-members-data';
            url = `/reports/${routeName}?value=${encodeURIComponent(actualValue)}&format=json`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                }
            });

            if (response.ok) {
                const data = await response.json();
                setCurrentMembers(data.members || data || []);
            } else {
                showToast('Failed to load member list for viewing', 'error');
                setCurrentMembers([]);
            }
        } catch (error) {
            console.error('Failed to load members:', error);
            showToast('Failed to load member list for viewing', 'error');
            setCurrentMembers([]);
        } finally {
            setMemberViewLoading(false);
        }
    };

    // Helper function to download filtered members list with correct route
    const downloadFilteredMembersList = async () => {
        await exportByCategory('all_records', 'filtered', 'excel');
    };

    // Helper function to download member directory with correct route
    const downloadMemberDirectory = async () => {
        await downloadAllClearRecords('excel');
    };

    // Safe data access with fallbacks
    const safeStats = useMemo(() => ({
        overview: currentStats?.overview || {
            total_members: 0,
            new_members: 0,
            active_members: 0,
            inactive_members: 0,
            growth_rate: 0,
        },
        demographics: currentStats?.demographics || {
            age_groups: { children: 0, youth: 0, adults: 0, seniors: 0 },
            gender_distribution: { male: 0, female: 0 },
            marital_status: {},
            education_levels: {},
            occupations: {},
        },
        church_groups: currentStats?.church_groups || {
            primary_groups: {},
            additional_memberships: {},
            total_group_memberships: 0,
        },
        local_churches: currentStats?.local_churches || {},
        small_christian_communities: currentStats?.small_christian_communities || {},
        sacraments: currentStats?.sacraments || {
            baptized: 0,
            confirmed: 0,
            married: 0,
            marriage_types: {},
        },
        recent_activity: currentStats?.recent_activity || {
            new_registrations: [],
            recent_updates: [],
        },
        period_info: currentStats?.period_info || {
            period: 'all',
            generated_at: new Date().toISOString(),
        },
    }), [currentStats]);

    const safeCharts = useMemo(() => ({
        monthly_trends: currentCharts?.monthly_trends || [],
        age_distribution: currentCharts?.age_distribution || [],
        gender_distribution: currentCharts?.gender_distribution || [],
        status_distribution: currentCharts?.status_distribution || [],
        church_groups_distribution: currentCharts?.church_groups_distribution || [],
        education_levels_distribution: currentCharts?.education_levels_distribution || [],
        local_churches_distribution: currentCharts?.local_churches_distribution || [],
        marriage_types_distribution: currentCharts?.marriage_types_distribution || [],
    }), [currentCharts]);

    // Count active filters
    useEffect(() => {
        const count = Object.values(advancedFilters).filter(value => {
            if (Array.isArray(value)) return value.length > 0;
            if (typeof value === 'boolean') return value === true;
            return value !== undefined && value !== '' && value !== null;
        }).length;
        setActiveFiltersCount(count);
    }, [advancedFilters]);

    const fetchUpdatedData = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.append('period', selectedPeriod);
            
            if (selectedPeriod === 'custom' && customStartDate && customEndDate) {
                params.append('start_date', customStartDate);
                params.append('end_date', customEndDate);
            }

            // Add advanced filters
            Object.entries(advancedFilters).forEach(([key, value]) => {
                if (Array.isArray(value) && value.length > 0) {
                    value.forEach(v => params.append(`${key}[]`, v));
                } else if (value !== undefined && value !== '' && value !== null) {
                    params.append(key, value.toString());
                }
            });

            const response = await fetch(`/reports/enhanced-statistics?${params.toString()}`);
            const data = await response.json();
            
            setCurrentStats(data.statistics);
            setCurrentCharts(data.charts);
        } catch (error) {
            console.error('Error fetching updated data:', error);
        } finally {
            setLoading(false);
        }
    }, [selectedPeriod, customStartDate, customEndDate, advancedFilters]);

    useEffect(() => {
        if (selectedPeriod !== 'all' || activeFiltersCount > 0) {
            const timeoutId = setTimeout(() => {
                fetchUpdatedData();
            }, 300);

            return () => clearTimeout(timeoutId);
        }
    }, [selectedPeriod, customStartDate, customEndDate, advancedFilters, activeFiltersCount, fetchUpdatedData]);

    const handleExport = useCallback(() => {
        const params = new URLSearchParams();
        params.append('type', exportType);
        params.append('period', selectedPeriod);
        params.append('format', exportFormat);
        
        if (selectedPeriod === 'custom' && customStartDate && customEndDate) {
            params.append('start_date', customStartDate);
            params.append('end_date', customEndDate);
        }

        // Add advanced filters to export
        Object.entries(advancedFilters).forEach(([key, value]) => {
            if (Array.isArray(value) && value.length > 0) {
                value.forEach(v => params.append(`${key}[]`, v));
            } else if (value !== undefined && value !== '' && value !== null) {
                params.append(key, value.toString());
            }
        });

        window.open(`/reports/export?${params.toString()}`, '_blank');
    }, [exportType, selectedPeriod, exportFormat, customStartDate, customEndDate, advancedFilters]);

    const clearAdvancedFilters = useCallback(() => {
        setAdvancedFilters({});
    }, []);

    const updateFilter = useCallback((key: keyof AdvancedFilters, value: any) => {
        setAdvancedFilters(prev => ({
            ...prev,
            [key]: value
        }));
    }, []);

    // Chart configurations
    const chartConfig = useMemo(() => ({
        height: 300,
        margin: { top: 5, right: 30, left: 20, bottom: 5 }
    }), []);

    // Member Modal Component
    const MemberModal = () => (
        showMemberModal && (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div className="bg-white rounded-lg max-w-6xl w-full max-h-[90vh] overflow-hidden">
                    <div className="flex items-center justify-between p-6 border-b">
                        <h3 className="text-xl font-bold text-gray-900">{memberViewTitle}</h3>
                        <button
                            onClick={() => setShowMemberModal(false)}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            <X className="w-6 h-6" />
                        </button>
                    </div>
                    
                    <div className="p-6 overflow-y-auto max-h-[70vh]">
                        {memberViewLoading ? (
                            <div className="flex items-center justify-center py-8">
                                <RefreshCw className="w-8 h-8 animate-spin text-blue-600" />
                                <span className="ml-2 text-lg">Loading members...</span>
                            </div>
                        ) : currentMembers.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Church Group</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Local Church</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {currentMembers.map((member, index) => (
                                            <tr key={member.id || index} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {member.first_name} {member.middle_name} {member.surname}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {member.email || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {member.phone_number || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {member.church_group || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {member.local_church || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                        member.membership_status === 'Active' 
                                                            ? 'bg-green-100 text-green-800'
                                                            : member.membership_status === 'Inactive'
                                                            ? 'bg-yellow-100 text-yellow-800'
                                                            : member.membership_status === 'Transferred'
                                                            ? 'bg-blue-100 text-blue-800'
                                                            : 'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {member.membership_status || 'Unknown'}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <Users className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                <p className="text-gray-500">No members found for this category.</p>
                            </div>
                        )}
                    </div>
                    
                    <div className="border-t p-6 bg-gray-50">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-gray-600">
                                Total: {currentMembers.length} members
                            </span>
                            <div className="flex space-x-3">
                                <button
                                    onClick={() => setShowMemberModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                >
                                    Close
                                </button>
                                <button
                                    onClick={() => {
                                        // Extract download info from modal title
                                        const [type, value] = memberViewTitle.split(' - ');
                                        downloadMemberList(type.toLowerCase().replace(' ', '_'), value);
                                    }}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center space-x-2"
                                    disabled={loading}
                                >
                                    <Download className="w-4 h-4" />
                                    <span>Download Excel</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        )
    );

    return (
        <>
            <MemberModal />
            <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <BarChart3 className="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h2 className="font-bold text-2xl text-gray-800 leading-tight">
                                    Enhanced Parish Reports
                                </h2>
                                <p className="text-sm text-gray-600">
                                    Comprehensive analytics and insights for parish management
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
                            className={`px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors ${
                                showAdvancedFilters || activeFiltersCount > 0
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            }`}
                        >
                            <Filter className="w-4 h-4" />
                            <span>Advanced Filters</span>
                            {activeFiltersCount > 0 && (
                                <span className="bg-white text-blue-500 px-2 py-0.5 rounded-full text-xs font-medium">
                                    {activeFiltersCount}
                                </span>
                            )}
                        </button>
                        {canExport && (
                            <button
                                onClick={handleExport}
                                className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                            >
                                <Download className="w-4 h-4" />
                                <span>Export</span>
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Enhanced Parish Reports" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Basic Filters */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
                                <select
                                    value={selectedPeriod}
                                    onChange={(e) => setSelectedPeriod(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    {filters?.periods && Object.entries(filters.periods).map(([value, label]) => (
                                        <option key={value} value={value}>{label}</option>
                                    ))}
                                </select>
                            </div>

                            {selectedPeriod === 'custom' && (
                                <>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                        <input
                                            type="date"
                                            value={customStartDate}
                                            onChange={(e) => setCustomStartDate(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                        <input
                                            type="date"
                                            value={customEndDate}
                                            onChange={(e) => setCustomEndDate(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                </>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Export Type</label>
                                <select
                                    value={exportType}
                                    onChange={(e) => setExportType(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    {filters?.export_types && Object.entries(filters.export_types).map(([value, label]) => (
                                        <option key={value} value={value}>{label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Advanced Filters Panel */}
                    {showAdvancedFilters && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center space-x-3">
                                    <Filter className="w-5 h-5 text-blue-500" />
                                    <h3 className="text-lg font-semibold text-gray-900">Advanced Filters</h3>
                                    {activeFiltersCount > 0 && (
                                        <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                            {activeFiltersCount} active
                                        </span>
                                    )}
                                </div>
                                {activeFiltersCount > 0 && (
                                    <button
                                        onClick={clearAdvancedFilters}
                                        className="text-red-600 hover:text-red-800 flex items-center space-x-1 text-sm"
                                    >
                                        <X className="w-4 h-4" />
                                        <span>Clear All</span>
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                {/* Church Group Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Primary Church Group</label>
                                    <select
                                        value={advancedFilters.church_group || ''}
                                        onChange={(e) => updateFilter('church_group', e.target.value || undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">All Groups</option>
                                        {filters?.church_groups && Object.entries(filters.church_groups).map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </select>
                                </div>

                                {/* Local Church Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Local Church</label>
                                    <select
                                        value={advancedFilters.local_church || ''}
                                        onChange={(e) => updateFilter('local_church', e.target.value || undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">All Churches</option>
                                        {filters?.local_churches && Object.entries(filters.local_churches).map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </select>
                                </div>

                                {/* Education Level Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Education Level</label>
                                    <select
                                        value={advancedFilters.education_level || ''}
                                        onChange={(e) => updateFilter('education_level', e.target.value || undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">All Levels</option>
                                        {filters?.education_levels && Object.entries(filters.education_levels).map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </select>
                                </div>

                                {/* Gender Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                    <select
                                        value={advancedFilters.gender || ''}
                                        onChange={(e) => updateFilter('gender', e.target.value || undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">All Genders</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>

                                {/* Matrimony Status Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Matrimony Status</label>
                                    <select
                                        value={advancedFilters.matrimony_status || ''}
                                        onChange={(e) => updateFilter('matrimony_status', e.target.value || undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">All Statuses</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="widowed">Widowed</option>
                                    </select>
                                </div>

                                {/* Occupation Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Occupation</label>
                                    <select
                                        value={advancedFilters.occupation || ''}
                                        onChange={(e) => updateFilter('occupation', e.target.value || undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">All Occupations</option>
                                        {filters?.occupations && Object.entries(filters.occupations).map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </select>
                                </div>

                                {/* Age Range Filters */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Min Age</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="120"
                                        value={advancedFilters.age_min || ''}
                                        onChange={(e) => updateFilter('age_min', e.target.value ? parseInt(e.target.value) : undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="0"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Max Age</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="120"
                                        value={advancedFilters.age_max || ''}
                                        onChange={(e) => updateFilter('age_max', e.target.value ? parseInt(e.target.value) : undefined)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="120"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                {/* Sacrament Filters */}
                                <div className="flex items-center space-x-4">
                                    <label className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            checked={advancedFilters.has_baptism || false}
                                            onChange={(e) => updateFilter('has_baptism', e.target.checked || undefined)}
                                            className="rounded text-blue-500 focus:ring-blue-500"
                                        />
                                        <span className="text-sm text-gray-700">Has Baptism</span>
                                    </label>
                                </div>

                                <div className="flex items-center space-x-4">
                                    <label className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            checked={advancedFilters.has_confirmation || false}
                                            onChange={(e) => updateFilter('has_confirmation', e.target.checked || undefined)}
                                            className="rounded text-blue-500 focus:ring-blue-500"
                                        />
                                        <span className="text-sm text-gray-700">Has Confirmation</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Quick Export Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                        <h3 className="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <Download className="w-5 h-5 mr-2 text-blue-600" />
                            Quick Export Options
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <button
                                onClick={() => downloadMemberList('gender', 'Male')}
                                className="flex items-center space-x-2 bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors text-sm"
                                disabled={loading}
                            >
                                <Download className="w-4 h-4" />
                                <span>Male Members</span>
                            </button>
                            <button
                                onClick={() => downloadMemberList('gender', 'Female')}
                                className="flex items-center space-x-2 bg-pink-600 text-white px-4 py-3 rounded-lg hover:bg-pink-700 transition-colors text-sm"
                                disabled={loading}
                            >
                                <Download className="w-4 h-4" />
                                <span>Female Members</span>
                            </button>
                            <button
                                onClick={() => downloadMemberList('status', 'active')}
                                className="flex items-center space-x-2 bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors text-sm"
                                disabled={loading}
                            >
                                <Download className="w-4 h-4" />
                                <span>Active Members</span>
                            </button>
                            <button
                                onClick={() => downloadAllClearRecords('excel')}
                                className="flex items-center space-x-2 bg-purple-600 text-white px-4 py-3 rounded-lg hover:bg-purple-700 transition-colors text-sm"
                                disabled={loading}
                            >
                                <Download className="w-4 h-4" />
                                <span>All Records</span>
                            </button>
                        </div>
                    </div>

                    {/* Overview Statistics */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Total Members</p>
                                    <p className="text-3xl font-bold text-gray-900">{safeStats.overview.total_members.toLocaleString()}</p>
                                </div>
                                <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <Users className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center">
                                <span className={`text-sm font-medium ${safeStats.overview.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                    {safeStats.overview.growth_rate >= 0 ? '+' : ''}{safeStats.overview.growth_rate.toFixed(1)}%
                                </span>
                                <span className="text-sm text-gray-600 ml-2">from last period</span>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Active Members</p>
                                    <p className="text-3xl font-bold text-gray-900">{safeStats.overview.active_members.toLocaleString()}</p>
                                </div>
                                <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <UserPlus className="w-6 h-6 text-green-600" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <span className="text-sm text-gray-600">
                                    {safeStats.overview.total_members > 0 ? ((safeStats.overview.active_members / safeStats.overview.total_members) * 100).toFixed(1) : 0}% of total
                                </span>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Church Groups</p>
                                    <p className="text-3xl font-bold text-gray-900">{Object.keys(safeStats.church_groups.primary_groups).length}</p>
                                </div>
                                <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <Crown className="w-6 h-6 text-purple-600" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <span className="text-sm text-gray-600">
                                    {safeStats.church_groups.total_group_memberships} total memberships
                                </span>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Sacraments</p>
                                    <p className="text-3xl font-bold text-gray-900">{safeStats.sacraments.baptized + safeStats.sacraments.confirmed}</p>
                                </div>
                                <div className="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <Award className="w-6 h-6 text-amber-600" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <span className="text-sm text-gray-600">
                                    {safeStats.sacraments.baptized} baptized, {safeStats.sacraments.confirmed} confirmed
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Member Lists Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                        <div className="flex items-center space-x-3 mb-6">
                            <Users className="w-6 h-6 text-indigo-600" />
                            <h3 className="text-xl font-bold text-gray-900">Member Lists & Records</h3>
                        </div>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {/* By Local Church */}
                            <div className="p-4 border border-gray-200 rounded-lg">
                                <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
                                    <Church className="w-4 h-4 mr-2 text-green-600" />
                                    By Local Church
                                </h4>
                                <div className="space-y-2">
                                    {Object.entries(safeStats.local_churches || {}).map(([church, count]) => (
                                        <div key={church} className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600 truncate">{church}</span>
                                            <div className="flex items-center space-x-1">
                                                <span className="text-xs bg-gray-100 px-2 py-1 rounded">{count}</span>
                                                <button
                                                    onClick={() => viewMemberList('local_church', church)}
                                                    className="text-blue-600 hover:text-blue-800 p-1"
                                                    title="View member list"
                                                >
                                                    <Eye className="w-3 h-3" />
                                                </button>
                                                <button
                                                    onClick={() => downloadMemberList('local_church', church)}
                                                    className="text-indigo-600 hover:text-indigo-800 p-1"
                                                    title="Download member list"
                                                >
                                                    <Download className="w-3 h-3" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* By Church Group */}
                            <div className="p-4 border border-gray-200 rounded-lg">
                                <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
                                    <Crown className="w-4 h-4 mr-2 text-purple-600" />
                                    By Church Group
                                </h4>
                                <div className="space-y-2">
                                    {Object.entries(safeStats.church_groups?.primary_groups || {}).map(([group, count]) => (
                                        <div key={group} className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600 truncate">{group}</span>
                                            <div className="flex items-center space-x-1">
                                                <span className="text-xs bg-gray-100 px-2 py-1 rounded">{count}</span>
                                                <button
                                                    onClick={() => viewMemberList('church_group', group)}
                                                    className="text-blue-600 hover:text-blue-800 p-1"
                                                    title="View member list"
                                                >
                                                    <Eye className="w-3 h-3" />
                                                </button>
                                                <button
                                                    onClick={() => downloadMemberList('church_group', group)}
                                                    className="text-indigo-600 hover:text-indigo-800 p-1"
                                                    title="Download member list"
                                                >
                                                    <Download className="w-3 h-3" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* By Membership Status */}
                            <div className="p-4 border border-gray-200 rounded-lg">
                                <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
                                    <UserCheck className="w-4 h-4 mr-2 text-blue-600" />
                                    By Status
                                </h4>
                                <div className="space-y-2">
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-600">Active</span>
                                        <div className="flex items-center space-x-1">
                                            <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{safeStats.overview?.active_members || 0}</span>
                                            <button
                                                onClick={() => viewMemberList('status', 'active')}
                                                className="text-blue-600 hover:text-blue-800 p-1"
                                                title="View active members"
                                            >
                                                <Eye className="w-3 h-3" />
                                            </button>
                                            <button
                                                onClick={() => downloadMemberList('status', 'active')}
                                                className="text-indigo-600 hover:text-indigo-800 p-1"
                                                title="Download active members list"
                                            >
                                                <Download className="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-600">Inactive</span>
                                        <div className="flex items-center space-x-1">
                                            <span className="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">{safeStats.overview?.inactive_members || 0}</span>
                                            <button
                                                onClick={() => viewMemberList('status', 'inactive')}
                                                className="text-blue-600 hover:text-blue-800 p-1"
                                                title="View inactive members"
                                            >
                                                <Eye className="w-3 h-3" />
                                            </button>
                                            <button
                                                onClick={() => downloadMemberList('status', 'inactive')}
                                                className="text-indigo-600 hover:text-indigo-800 p-1"
                                                title="Download inactive members list"
                                            >
                                                <Download className="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-600">Transferred</span>
                                        <div className="flex items-center space-x-1">
                                            <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                {safeCharts.status_distribution?.find(s => s.name === 'Transferred')?.value || 0}
                                            </span>
                                            <button
                                                onClick={() => viewMemberList('status', 'transferred')}
                                                className="text-blue-600 hover:text-blue-800 p-1"
                                                title="View transferred members"
                                            >
                                                <Eye className="w-3 h-3" />
                                            </button>
                                            <button
                                                onClick={() => downloadMemberList('status', 'transferred')}
                                                className="text-indigo-600 hover:text-indigo-800 p-1"
                                                title="Download transferred members list"
                                            >
                                                <Download className="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-600">Deceased</span>
                                        <div className="flex items-center space-x-1">
                                            <span className="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">
                                                {safeCharts.status_distribution?.find(s => s.name === 'Deceased')?.value || 0}
                                            </span>
                                            <button
                                                onClick={() => viewMemberList('status', 'deceased')}
                                                className="text-blue-600 hover:text-blue-800 p-1"
                                                title="View deceased members"
                                            >
                                                <Eye className="w-3 h-3" />
                                            </button>
                                            <button
                                                onClick={() => downloadMemberList('status', 'deceased')}
                                                className="text-indigo-600 hover:text-indigo-800 p-1"
                                                title="Download deceased members list"
                                            >
                                                <Download className="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* By Age Group */}
                            <div className="p-4 border border-gray-200 rounded-lg">
                                <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
                                    <Calendar className="w-4 h-4 mr-2 text-orange-600" />
                                    By Age Group
                                </h4>
                                <div className="space-y-2">
                                    {Object.entries(safeStats.demographics?.age_groups || {}).map(([ageGroup, count]) => (
                                        <div key={ageGroup} className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600 capitalize">{ageGroup}</span>
                                            <div className="flex items-center space-x-1">
                                                <span className="text-xs bg-gray-100 px-2 py-1 rounded">{count}</span>
                                                <button
                                                    onClick={() => viewMemberList('age_group', ageGroup)}
                                                    className="text-blue-600 hover:text-blue-800 p-1"
                                                    title="View age group"
                                                >
                                                    <Eye className="w-3 h-3" />
                                                </button>
                                                <button
                                                    onClick={() => downloadMemberList('age_group', ageGroup)}
                                                    className="text-indigo-600 hover:text-indigo-800 p-1"
                                                    title="Download age group list"
                                                >
                                                    <Download className="w-3 h-3" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* By Gender */}
                            <div className="p-4 border border-gray-200 rounded-lg">
                                <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
                                    <Users className="w-4 h-4 mr-2 text-pink-600" />
                                    By Gender
                                </h4>
                                <div className="space-y-2">
                                    {Object.entries(safeStats.demographics?.gender_distribution || {}).map(([gender, count]) => (
                                        <div key={gender} className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600 capitalize">{gender}</span>
                                            <div className="flex items-center space-x-1">
                                                <span className="text-xs bg-gray-100 px-2 py-1 rounded">{count}</span>
                                                <button
                                                    onClick={() => viewMemberList('gender', gender)}
                                                    className="text-blue-600 hover:text-blue-800 p-1"
                                                    title="View gender list"
                                                >
                                                    <Eye className="w-3 h-3" />
                                                </button>
                                                <button
                                                    onClick={() => downloadMemberList('gender', gender)}
                                                    className="text-indigo-600 hover:text-indigo-800 p-1"
                                                    title="Download gender list"
                                                >
                                                    <Download className="w-3 h-3" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* All Clear Records */}
                            <div className="p-4 border border-gray-200 rounded-lg">
                                <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
                                    <FileText className="w-4 h-4 mr-2 text-emerald-600" />
                                    Complete Records
                                </h4>
                                <div className="space-y-3">
                                    <div className="grid grid-cols-3 gap-1">
                                        <button
                                            onClick={() => downloadAllClearRecords('excel')}
                                            className="bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                            disabled={loading}
                                        >
                                            <FileSpreadsheet className="w-3 h-3 mr-1" />
                                            Excel
                                        </button>
                                        <button
                                            onClick={() => downloadAllClearRecords('csv')}
                                            className="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                            disabled={loading}
                                        >
                                            <FileText className="w-3 h-3 mr-1" />
                                            CSV
                                        </button>
                                        <button
                                            onClick={() => downloadAllClearRecords('pdf')}
                                            className="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                            disabled={loading}
                                        >
                                            <FileDown className="w-3 h-3 mr-1" />
                                            PDF
                                        </button>
                                    </div>
                                    <button
                                        onClick={() => downloadFilteredMembersList()}
                                        className="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm flex items-center justify-center space-x-2"
                                        disabled={loading}
                                    >
                                        <Filter className="w-4 h-4" />
                                        <span>Filtered List</span>
                                    </button>
                                    <button
                                        onClick={() => downloadMemberDirectory()}
                                        className="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm flex items-center justify-center space-x-2"
                                        disabled={loading}
                                    >
                                        <BookOpen className="w-4 h-4" />
                                        <span>Member Directory</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Member Search Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                        <div className="flex items-center justify-between mb-6">
                            <div className="flex items-center space-x-3">
                                <Search className="w-6 h-6 text-blue-600" />
                                <h3 className="text-xl font-bold text-gray-900">Search Members & Download Certificates</h3>
                            </div>
                        </div>
                        
                        <MemberSearchSection />
                    </div>

                    {/* Charts Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        {/* Church Groups Distribution */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center space-x-3">
                                    <Crown className="w-6 h-6 text-purple-600" />
                                    <h3 className="text-xl font-bold text-gray-900">Church Groups Distribution</h3>
                                </div>
                                {canExport && (
                                    <div className="flex space-x-2">
                                        <button
                                            onClick={() => exportByCategory('church_group', 'all', 'excel')}
                                            className="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                                            disabled={loading}
                                        >
                                            Export Excel
                                        </button>
                                        <button
                                            onClick={() => exportByCategory('church_group', 'all', 'csv')}
                                            className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
                                            disabled={loading}
                                        >
                                            Export CSV
                                        </button>
                                    </div>
                                )}
                            </div>
                            <ResponsiveContainer width="100%" height={chartConfig.height}>
                                <PieChart>
                                    <Pie
                                        data={safeCharts.church_groups_distribution}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {safeCharts.church_groups_distribution.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Education Levels Distribution */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center space-x-3">
                                    <GraduationCap className="w-6 h-6 text-blue-600" />
                                    <h3 className="text-xl font-bold text-gray-900">Education Levels</h3>
                                </div>
                                <button
                                    onClick={() => exportByCategory('education', 'all', 'excel')}
                                    className="flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm"
                                    disabled={loading}
                                >
                                    <Download className="w-4 h-4" />
                                    <span>Export List</span>
                                </button>
                            </div>
                            <ResponsiveContainer width="100%" height={chartConfig.height}>
                                <BarChart data={safeCharts.education_levels_distribution}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="value" fill="#3B82F6" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Local Churches Distribution */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center space-x-3">
                                    <Church className="w-6 h-6 text-green-600" />
                                    <h3 className="text-xl font-bold text-gray-900">Local Churches</h3>
                                </div>
                                <button
                                    onClick={() => exportByCategory('local_church', 'all', 'excel')}
                                    className="flex items-center space-x-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors text-sm"
                                    disabled={loading}
                                >
                                    <Download className="w-4 h-4" />
                                    <span>Export List</span>
                                </button>
                            </div>
                            <ResponsiveContainer width="100%" height={chartConfig.height}>
                                <BarChart data={safeCharts.local_churches_distribution}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="value" fill="#10B981" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Marriage Types Distribution */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center space-x-3">
                                    <Heart className="w-6 h-6 text-red-600" />
                                    <h3 className="text-xl font-bold text-gray-900">Marriage Types</h3>
                                </div>
                                <button
                                    onClick={() => exportByCategory('marriage_type', 'all', 'excel')}
                                    className="flex items-center space-x-2 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors text-sm"
                                    disabled={loading}
                                >
                                    <Download className="w-4 h-4" />
                                    <span>Export List</span>
                                </button>
                            </div>
                            <ResponsiveContainer width="100%" height={chartConfig.height}>
                                <PieChart>
                                    <Pie
                                        data={safeCharts.marriage_types_distribution}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {safeCharts.marriage_types_distribution.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Monthly Trends */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                        <div className="flex items-center justify-between mb-6">
                            <div className="flex items-center space-x-3">
                                <TrendingUp className="w-6 h-6 text-indigo-600" />
                                <h3 className="text-xl font-bold text-gray-900">Monthly Trends</h3>
                            </div>
                            <button
                                onClick={() => exportByCategory('monthly_trends', 'all', 'excel')}
                                className="flex items-center space-x-2 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors text-sm"
                                disabled={loading}
                            >
                                <Download className="w-4 h-4" />
                                <span>Export Data</span>
                            </button>
                        </div>
                        <ResponsiveContainer width="100%" height={400}>
                            <LineChart data={safeCharts.monthly_trends}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="month" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Line type="monotone" dataKey="registrations" stroke="#3B82F6" strokeWidth={2} name="New Registrations" />
                                <Line type="monotone" dataKey="baptisms" stroke="#10B981" strokeWidth={2} name="Baptisms" />
                                <Line type="monotone" dataKey="confirmations" stroke="#F59E0B" strokeWidth={2} name="Confirmations" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>

                    {/* Loading Overlay */}
                    {loading && (
                        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                            <div className="bg-white rounded-lg p-6 flex items-center space-x-3">
                                <RefreshCw className="w-6 h-6 animate-spin text-blue-600" />
                                <span className="text-lg font-medium">Updating reports...</span>
                            </div>
                        </div>
                    )}

                    {/* Toast Notification */}
                    {toast && (
                        <div className={`fixed top-4 right-4 z-60 px-6 py-4 rounded-lg shadow-lg border-l-4 max-w-md transition-all duration-300 ${
                            toast.type === 'success' 
                                ? 'bg-green-50 border-green-400 text-green-800' 
                                : toast.type === 'error'
                                ? 'bg-red-50 border-red-400 text-red-800'
                                : 'bg-blue-50 border-blue-400 text-blue-800'
                        }`}>
                            <div className="flex items-start space-x-3">
                                <div className="flex-shrink-0">
                                    {toast.type === 'success' && (
                                        <div className="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                                            <Download className="w-4 h-4 text-green-600" />
                                        </div>
                                    )}
                                    {toast.type === 'error' && (
                                        <div className="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                            <span className="text-red-600 font-bold text-sm">!</span>
                                        </div>
                                    )}
                                    {toast.type === 'info' && (
                                        <div className="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                            <RefreshCw className="w-4 h-4 text-blue-600 animate-spin" />
                                        </div>
                                    )}
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-medium">{toast.message}</p>
                                </div>
                                <button
                                    onClick={() => setToast(null)}
                                    className="flex-shrink-0 text-gray-400 hover:text-gray-600"
                                >
                                    <span className="sr-only">Close</span>
                                    <span className="text-lg">&times;</span>
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
        </>
    );
}

// Member Search Section Component
const MemberSearchSection: React.FC = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [searchResults, setSearchResults] = useState<any[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [hasSearched, setHasSearched] = useState(false);

    const handleSearch = async () => {
        if (!searchTerm.trim()) return;
        
        setIsSearching(true);
        setHasSearched(true);
        
        try {
            const response = await fetch(`/api/members/search?q=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();
            setSearchResults(data.members || []);
        } catch (error) {
            console.error('Search error:', error);
            setSearchResults([]);
        } finally {
            setIsSearching(false);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const downloadCertificate = (memberId: number, type: 'baptism' | 'marriage') => {
        const url = type === 'baptism' 
            ? `/members/${memberId}/baptism-certificate`
            : `/members/${memberId}/marriage-certificate`;
        window.open(url, '_blank');
    };

    return (
        <div className="space-y-6">
            {/* Search Input */}
            <div className="flex space-x-4">
                <div className="flex-1">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                        <input
                            type="text"
                            placeholder="Search by name, phone, email, or member ID..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            onKeyPress={handleKeyPress}
                            className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                </div>
                <button
                    onClick={handleSearch}
                    disabled={!searchTerm.trim() || isSearching}
                    className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                >
                    {isSearching ? (
                        <>
                            <RefreshCw className="w-5 h-5 animate-spin" />
                            <span>Searching...</span>
                        </>
                    ) : (
                        <>
                            <Search className="w-5 h-5" />
                            <span>Search</span>
                        </>
                    )}
                </button>
            </div>

            {/* Search Results */}
            {hasSearched && (
                <div className="border-t pt-6">
                    {searchResults.length > 0 ? (
                        <div className="space-y-4">
                            <h4 className="text-lg font-semibold text-gray-900 flex items-center">
                                <Users className="w-5 h-5 mr-2 text-blue-600" />
                                Found {searchResults.length} member{searchResults.length !== 1 ? 's' : ''}
                            </h4>
                            
                            <div className="grid gap-4">
                                {searchResults.map((member) => (
                                    <div key={member.id} className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center space-x-4">
                                                    <div className="flex-1">
                                                        <h5 className="font-semibold text-gray-900">
                                                            {member.first_name} {member.middle_name ? member.middle_name + ' ' : ''}{member.last_name}
                                                        </h5>
                                                        <div className="text-sm text-gray-600 mt-1 space-y-1">
                                                            {member.email && <div>📧 {member.email}</div>}
                                                            {member.phone && <div>📱 {member.phone}</div>}
                                                            <div>🆔 Member ID: {member.id}</div>
                                                            {member.date_of_birth && (
                                                                <div>🎂 Born: {new Date(member.date_of_birth).toLocaleDateString()}</div>
                                                            )}
                                                        </div>
                                                    </div>
                                                    
                                                    <div className="flex items-center space-x-2">
                                                        <Link
                                                            href={`/members/${member.id}`}
                                                            className="px-3 py-2 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 flex items-center space-x-1"
                                                        >
                                                            <Eye className="w-4 h-4" />
                                                            <span>View</span>
                                                        </Link>
                                                        
                                                        <div className="flex space-x-1">
                                                            <button
                                                                onClick={() => downloadCertificate(member.id, 'baptism')}
                                                                className="px-3 py-2 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200 flex items-center space-x-1"
                                                                title="Download Baptism Certificate"
                                                            >
                                                                <FileText className="w-4 h-4" />
                                                                <span>Baptism</span>
                                                            </button>
                                                            
                                                            <button
                                                                onClick={() => downloadCertificate(member.id, 'marriage')}
                                                                className="px-3 py-2 text-sm bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 flex items-center space-x-1"
                                                                title="Download Marriage Certificate"
                                                            >
                                                                <Heart className="w-4 h-4" />
                                                                <span>Marriage</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-8">
                            <Search className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <h4 className="text-lg font-semibold text-gray-900 mb-2">No members found</h4>
                            <p className="text-gray-600">
                                Try searching with different keywords like name, phone number, email, or member ID.
                            </p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};
