import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    ChartBarIcon, 
    ClockIcon, 
    DatabaseIcon, 
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    ArrowTrendingUpIcon,
    ArrowPathIcon,
    CpuChipIcon
} from '@heroicons/react/24/outline';

export default function PerformanceDashboard({ 
    performance = {}, 
    database = {}, 
    system = {}, 
    recommendations = [],
    lastUpdated 
}) {
    const [isLoading, setIsLoading] = useState(false);
    const [autoRefresh, setAutoRefresh] = useState(false);

    // Auto-refresh functionality
    useEffect(() => {
        let interval;
        if (autoRefresh) {
            interval = setInterval(() => {
                router.reload({ only: ['performance', 'database', 'system', 'recommendations', 'lastUpdated'] });
            }, 30000); // Refresh every 30 seconds
        }
        return () => {
            if (interval) clearInterval(interval);
        };
    }, [autoRefresh]);

    const refreshData = () => {
        setIsLoading(true);
        router.reload({ 
            only: ['performance', 'database', 'system', 'recommendations', 'lastUpdated'],
            onFinish: () => setIsLoading(false)
        });
    };

    const clearCache = async () => {
        try {
            await fetch('/admin/performance/clear-cache', { method: 'POST' });
            refreshData();
        } catch (error) {
            console.error('Failed to clear cache:', error);
        }
    };

    const getRecommendationIcon = (type) => {
        switch (type) {
            case 'error':
                return <ExclamationTriangleIcon className="w-5 h-5 text-red-500" />;
            case 'warning':
                return <ExclamationTriangleIcon className="w-5 h-5 text-yellow-500" />;
            case 'success':
                return <CheckCircleIcon className="w-5 h-5 text-green-500" />;
            default:
                return <InformationCircleIcon className="w-5 h-5 text-blue-500" />;
        }
    };

    const formatTime = (ms) => {
        if (ms >= 1000) {
            return `${(ms / 1000).toFixed(2)}s`;
        }
        return `${ms.toFixed(0)}ms`;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Performance Dashboard
                    </h2>
                    <div className="flex space-x-3">
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={autoRefresh}
                                onChange={(e) => setAutoRefresh(e.target.checked)}
                                className="mr-2"
                            />
                            Auto-refresh
                        </label>
                        <button
                            onClick={clearCache}
                            className="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                        >
                            Clear Cache
                        </button>
                        <button
                            onClick={refreshData}
                            disabled={isLoading}
                            className="flex items-center px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                        >
                            <ArrowPathIcon className={`w-4 h-4 mr-1 ${isLoading ? 'animate-spin' : ''}`} />
                            Refresh
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Performance Dashboard" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Last Updated */}
                    <div className="mb-6 text-sm text-gray-600">
                        Last updated: {lastUpdated ? new Date(lastUpdated).toLocaleString() : 'Never'}
                    </div>

                    {/* Performance Overview Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Avg Response Time</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {performance.avg_response_time ? formatTime(performance.avg_response_time) : 'N/A'}
                                    </p>
                                </div>
                                <ClockIcon className="w-8 h-8 text-blue-500" />
                            </div>
                            {performance.percentiles && (
                                <div className="mt-2 text-xs text-gray-500">
                                    P95: {formatTime(performance.percentiles.p95)}
                                </div>
                            )}
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Queries/Request</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {performance.avg_queries_per_request?.toFixed(1) || 'N/A'}
                                    </p>
                                </div>
                                <DatabaseIcon className="w-8 h-8 text-green-500" />
                            </div>
                            {performance.query_stats && (
                                <div className="mt-2 text-xs text-gray-500">
                                    Max: {performance.query_stats.max}
                                </div>
                            )}
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Recent Requests</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {performance.recent_requests || 0}
                                    </p>
                                </div>
                                <ChartBarIcon className="w-8 h-8 text-purple-500" />
                            </div>
                            {performance.slow_request_percentage !== undefined && (
                                <div className="mt-2 text-xs text-gray-500">
                                    {performance.slow_request_percentage}% slow
                                </div>
                            )}
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">DB Health Score</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {database.health_score || 'N/A'}
                                        {database.health_score && '/100'}
                                    </p>
                                </div>
                                <CpuChipIcon className={`w-8 h-8 ${
                                    database.health_score >= 80 ? 'text-green-500' :
                                    database.health_score >= 60 ? 'text-yellow-500' : 'text-red-500'
                                }`} />
                            </div>
                            {database.total_size_mb && (
                                <div className="mt-2 text-xs text-gray-500">
                                    {database.total_size_mb.toFixed(1)} MB
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        {/* Performance Trends */}
                        {performance.hourly_trends && (
                            <div className="bg-white rounded-lg shadow">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <ArrowTrendingUpIcon className="w-5 h-5 mr-2" />
                                        24-Hour Performance Trends
                                    </h3>
                                </div>
                                <div className="p-6">
                                    <div className="space-y-3">
                                        {performance.hourly_trends.slice(-8).map((trend, index) => (
                                            <div key={index} className="flex justify-between items-center">
                                                <span className="text-sm text-gray-600">
                                                    {new Date(trend.hour + ':00').toLocaleTimeString([], {
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </span>
                                                <div className="flex space-x-4 text-sm">
                                                    <span className="text-blue-600">
                                                        {trend.requests} req
                                                    </span>
                                                    <span className="text-green-600">
                                                        {formatTime(trend.avg_time)}
                                                    </span>
                                                    <span className="text-purple-600">
                                                        {trend.avg_queries.toFixed(1)}q
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Database Tables */}
                        {database.tables && database.tables.length > 0 && (
                            <div className="bg-white rounded-lg shadow">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <DatabaseIcon className="w-5 h-5 mr-2" />
                                        Database Tables
                                    </h3>
                                </div>
                                <div className="p-6">
                                    <div className="space-y-3 max-h-80 overflow-y-auto">
                                        {database.tables.slice(0, 10).map((table, index) => (
                                            <div key={index} className="flex justify-between items-center">
                                                <span className="text-sm font-medium text-gray-900">
                                                    {table.name}
                                                </span>
                                                <div className="flex space-x-4 text-sm text-gray-600">
                                                    <span>{table.row_count} rows</span>
                                                    <span>{table.size_mb} MB</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* System Information */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <CpuChipIcon className="w-5 h-5 mr-2" />
                                    System Information
                                </h3>
                            </div>
                            <div className="p-6">
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span className="text-gray-600">PHP Version:</span>
                                        <span className="ml-2 font-medium">{system.php_version}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Laravel Version:</span>
                                        <span className="ml-2 font-medium">{system.laravel_version}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Memory Limit:</span>
                                        <span className="ml-2 font-medium">{system.memory_limit}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Cache Driver:</span>
                                        <span className="ml-2 font-medium">{system.cache_driver}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Current Memory:</span>
                                        <span className="ml-2 font-medium">{system.current_memory_usage}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Peak Memory:</span>
                                        <span className="ml-2 font-medium">{system.peak_memory_usage}</span>
                                    </div>
                                </div>
                                
                                {/* Performance Checks */}
                                {system.performance_checks && (
                                    <div className="mt-4 pt-4 border-t border-gray-200">
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">Performance Checks</h4>
                                        <div className="space-y-2">
                                            {Object.entries(system.performance_checks).map(([check, passed]) => (
                                                <div key={check} className="flex items-center">
                                                    {passed ? (
                                                        <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                                    ) : (
                                                        <ExclamationTriangleIcon className="w-4 h-4 text-red-500 mr-2" />
                                                    )}
                                                    <span className="text-sm text-gray-700">
                                                        {check.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Slowest Routes */}
                        {performance.slowest_routes && Object.keys(performance.slowest_routes).length > 0 && (
                            <div className="bg-white rounded-lg shadow">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">Slowest Routes</h3>
                                </div>
                                <div className="p-6">
                                    <div className="space-y-3">
                                        {Object.entries(performance.slowest_routes).slice(0, 5).map(([route, stats]) => (
                                            <div key={route} className="flex justify-between items-center">
                                                <span className="text-sm font-medium text-gray-900 truncate">
                                                    {route}
                                                </span>
                                                <div className="flex space-x-4 text-sm text-gray-600">
                                                    <span>{stats.count} req</span>
                                                    <span>{formatTime(stats.avg_time)}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Recommendations */}
                    {recommendations.length > 0 && (
                        <div className="mt-8 bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Performance Recommendations</h3>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    {recommendations.map((rec, index) => (
                                        <div key={index} className={`p-4 rounded-lg border-l-4 ${
                                            rec.type === 'error' ? 'bg-red-50 border-red-400' :
                                            rec.type === 'warning' ? 'bg-yellow-50 border-yellow-400' :
                                            rec.type === 'success' ? 'bg-green-50 border-green-400' :
                                            'bg-blue-50 border-blue-400'
                                        }`}>
                                            <div className="flex items-start">
                                                <div className="flex-shrink-0">
                                                    {getRecommendationIcon(rec.type)}
                                                </div>
                                                <div className="ml-3 flex-1">
                                                    <div className="flex justify-between items-start">
                                                        <div>
                                                            <h4 className="text-sm font-medium text-gray-900">
                                                                {rec.title}
                                                            </h4>
                                                            <p className="mt-1 text-sm text-gray-700">
                                                                {rec.message}
                                                            </p>
                                                            <p className="mt-2 text-sm text-gray-600">
                                                                <strong>Action:</strong> {rec.action}
                                                            </p>
                                                        </div>
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                            rec.priority === 'high' ? 'bg-red-100 text-red-800' :
                                                            rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                                            'bg-green-100 text-green-800'
                                                        }`}>
                                                            {rec.priority}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}