import React, { useState, useCallback, useRef } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Upload,
    Download,
    FileText,
    AlertCircle,
    CheckCircle,
    Info,
    X,
    ArrowLeft,
    Loader2,
    FileSpreadsheet,
    Users,
    TrendingUp,
    Clock,
    Activity
} from 'lucide-react';
import { PageProps } from '@/types';

interface ImportStats {
    total_members: number;
    recent_imports: Array<{
        date: string;
        records: number;
        status: string;
    }>;
    supported_formats: string[];
    max_file_size: string;
    max_records: number;
}

interface ImportProps extends PageProps {
    stats: ImportStats;
}

interface ImportResult {
    success: boolean;
    message: string;
    imported: number;
    updated: number;
    skipped: number;
    errors: string[];
    warnings: string[];
    total_processed: number;
}

export default function MembersImport({ auth, stats }: ImportProps) {
    const [dragActive, setDragActive] = useState(false);
    const [importResult, setImportResult] = useState<ImportResult | null>(null);
    const [showAdvancedOptions, setShowAdvancedOptions] = useState(false);
    const [isImporting, setIsImporting] = useState(false);
    const [importProgress, setImportProgress] = useState(0);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [previewData, setPreviewData] = useState<string[][]>([]);
    const [showPreview, setShowPreview] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing } = useForm<{
        file: File | null;
        update_existing: boolean;
        skip_duplicates: boolean;
        validate_families: boolean;
    }>({
        file: null as File | null,
        update_existing: true,
        skip_duplicates: false,
        validate_families: true,
    });

    // Handle drag events
    const handleDrag = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    }, []);

    // Handle drop
    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        const files = Array.from(e.dataTransfer.files);
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    }, []);

    // Handle file selection
    const handleFileSelect = useCallback((file: File) => {
        // Validate file type
        const allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
        if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv')) {
            alert('Please select a CSV file');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }

        setSelectedFile(file);
        setData('file', file);
        previewFile(file);
    }, [setData]);

    // Preview file contents
    const previewFile = useCallback((file: File) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const text = e.target?.result as string;
            const lines = text.split('\n').slice(0, 6); // First 6 lines
            const csvData = lines.map(line => line.split(',').map(cell => cell.trim().replace(/"/g, '')));
            setPreviewData(csvData);
            setShowPreview(true);
        };
        reader.readAsText(file);
    }, []);

    // Handle file input change
    const handleFileInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const files = e.target.files;
        if (files && files.length > 0) {
            handleFileSelect(files[0]);
        }
    }, [handleFileSelect]);

    // Download template
    const downloadTemplate = useCallback(async () => {
        try {
            const response = await fetch(route('members.import-template'), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'members_import_template.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                alert('Failed to download template');
            }
        } catch (error) {
            console.error('Template download error:', error);
            alert('Failed to download template');
        }
    }, []);

    // Handle import
    const handleImport = useCallback(async () => {
        if (!selectedFile) {
            alert('Please select a file to import');
            return;
        }

        setIsImporting(true);
        setImportProgress(10);

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('update_existing', data.update_existing ? '1' : '0');
        formData.append('skip_duplicates', data.skip_duplicates ? '1' : '0');
        formData.append('validate_families', data.validate_families ? '1' : '0');

        try {
            setImportProgress(30);
            
            const response = await fetch(route('members.import'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            setImportProgress(70);
            const result = await response.json();

            if (response.ok && result.success) {
                setImportProgress(100);
                setImportResult(result);
                
                // Clear file selection after successful import
                setTimeout(() => {
                    setSelectedFile(null);
                    setData('file', null);
                    setShowPreview(false);
                    setImportProgress(0);
                    if (fileInputRef.current) {
                        fileInputRef.current.value = '';
                    }
                }, 2000);
            } else {
                throw new Error(result.error || result.message || 'Import failed');
            }
        } catch (error) {
            setImportProgress(0);
            console.error('Import error:', error);
            setImportResult({
                success: false,
                message: error instanceof Error ? error.message : 'Import failed',
                imported: 0,
                updated: 0,
                skipped: 0,
                errors: [error instanceof Error ? error.message : 'Unknown error'],
                warnings: [],
                total_processed: 0
            });
        } finally {
            setIsImporting(false);
        }
    }, [selectedFile, data]);

    // Clear file selection
    const clearFile = useCallback(() => {
        setSelectedFile(null);
        setData('file', null);
        setShowPreview(false);
        setImportResult(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, [setData]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('members.index')}
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Import Members
                            </h2>
                            <p className="text-sm text-gray-600">
                                Upload a CSV file to import multiple members at once
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={downloadTemplate}
                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                        >
                            <Download className="w-4 h-4 mr-2" />
                            Download Template
                        </button>
                        <Link
                            href={route('members.index')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition-colors"
                        >
                            <Users className="w-4 h-4 mr-2" />
                            View Members
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Import Members" />

            <div className="py-8">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-blue-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Members
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {stats?.total_members?.toLocaleString() || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <FileSpreadsheet className="h-8 w-8 text-green-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Max File Size
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {stats?.max_file_size || '5MB'}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <TrendingUp className="h-8 w-8 text-purple-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Max Records
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {stats?.max_records?.toLocaleString() || 1000}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Activity className="h-8 w-8 text-orange-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Recent Imports
                                            </dt>
                                            <dd className="text-2xl font-bold text-gray-900">
                                                {stats?.recent_imports?.length || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Upload Section */}
                        <div className="lg:col-span-2">
                            <div className="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Upload CSV File
                                    </h3>
                                    
                                    {/* File Upload Area */}
                                    <div
                                        className={`relative border-2 border-dashed rounded-lg p-6 transition-colors ${
                                            dragActive
                                                ? 'border-blue-400 bg-blue-50'
                                                : selectedFile
                                                ? 'border-green-400 bg-green-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                        }`}
                                        onDragEnter={handleDrag}
                                        onDragLeave={handleDrag}
                                        onDragOver={handleDrag}
                                        onDrop={handleDrop}
                                    >
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept=".csv"
                                            onChange={handleFileInputChange}
                                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                        />
                                        
                                        <div className="text-center">
                                            {selectedFile ? (
                                                <div className="flex items-center justify-center">
                                                    <CheckCircle className="mx-auto h-12 w-12 text-green-400" />
                                                    <div className="ml-4">
                                                        <p className="text-lg font-medium text-gray-900">
                                                            {selectedFile.name}
                                                        </p>
                                                        <p className="text-sm text-gray-500">
                                                            {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
                                                        </p>
                                                        <button
                                                            onClick={clearFile}
                                                            className="mt-2 text-sm text-red-600 hover:text-red-500"
                                                        >
                                                            Remove file
                                                        </button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <>
                                                    <Upload className="mx-auto h-12 w-12 text-gray-400" />
                                                    <div className="mt-4">
                                                        <p className="text-lg font-medium text-gray-900">
                                                            Drop your CSV file here
                                                        </p>
                                                        <p className="text-sm text-gray-500">
                                                            or click to browse files
                                                        </p>
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    {/* Advanced Options */}
                                    <div className="mt-6">
                                        <button
                                            onClick={() => setShowAdvancedOptions(!showAdvancedOptions)}
                                            className="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900"
                                        >
                                            Advanced Options
                                            <Info className="ml-1 h-4 w-4" />
                                        </button>

                                        {showAdvancedOptions && (
                                            <div className="mt-4 space-y-4 p-4 bg-gray-50 rounded-lg">
                                                <div className="flex items-center">
                                                    <input
                                                        id="update_existing"
                                                        type="checkbox"
                                                        checked={data.update_existing}
                                                        onChange={(e) => setData('update_existing', e.target.checked)}
                                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                    />
                                                    <label htmlFor="update_existing" className="ml-2 block text-sm text-gray-900">
                                                        Update existing members
                                                    </label>
                                                </div>
                                                
                                                <div className="flex items-center">
                                                    <input
                                                        id="skip_duplicates"
                                                        type="checkbox"
                                                        checked={data.skip_duplicates}
                                                        onChange={(e) => setData('skip_duplicates', e.target.checked)}
                                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                    />
                                                    <label htmlFor="skip_duplicates" className="ml-2 block text-sm text-gray-900">
                                                        Skip duplicate entries
                                                    </label>
                                                </div>
                                                
                                                <div className="flex items-center">
                                                    <input
                                                        id="validate_families"
                                                        type="checkbox"
                                                        checked={data.validate_families}
                                                        onChange={(e) => setData('validate_families', e.target.checked)}
                                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                    />
                                                    <label htmlFor="validate_families" className="ml-2 block text-sm text-gray-900">
                                                        Validate family references
                                                    </label>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Import Progress */}
                                    {isImporting && (
                                        <div className="mt-6">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-sm font-medium text-gray-700">
                                                    Importing members...
                                                </span>
                                                <span className="text-sm text-gray-500">
                                                    {importProgress}%
                                                </span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                    style={{ width: `${importProgress}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Import Button */}
                                    <div className="mt-6">
                                        <button
                                            onClick={handleImport}
                                            disabled={!selectedFile || isImporting}
                                            className="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                        >
                                            {isImporting ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    Importing...
                                                </>
                                            ) : (
                                                <>
                                                    <Upload className="w-4 h-4 mr-2" />
                                                    Import Members
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* File Preview */}
                            {showPreview && previewData.length > 0 && (
                                <div className="mt-8 bg-white shadow-sm rounded-lg border border-gray-200">
                                    <div className="p-6">
                                        <div className="flex items-center justify-between mb-4">
                                            <h3 className="text-lg font-medium text-gray-900">
                                                File Preview
                                            </h3>
                                            <button
                                                onClick={() => setShowPreview(false)}
                                                className="text-gray-400 hover:text-gray-600"
                                            >
                                                <X className="w-5 h-5" />
                                            </button>
                                        </div>
                                        
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {previewData.map((row, index) => (
                                                        <tr key={index} className={index === 0 ? 'bg-gray-50' : ''}>
                                                            {row.map((cell, cellIndex) => (
                                                                <td
                                                                    key={cellIndex}
                                                                    className={`px-3 py-2 text-sm ${
                                                                        index === 0
                                                                            ? 'font-medium text-gray-900'
                                                                            : 'text-gray-700'
                                                                    }`}
                                                                >
                                                                    {cell || '-'}
                                                                </td>
                                                            ))}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <p className="mt-2 text-sm text-gray-500">
                                            Showing first 5 rows of your file
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar - Instructions and Results */}
                        <div className="space-y-6">
                            {/* Instructions */}
                            <div className="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Import Instructions
                                    </h3>
                                    
                                    <div className="space-y-4 text-sm text-gray-600">
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-medium mr-3 mt-0.5">
                                                1
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900">Download Template</p>
                                                <p>Start with our CSV template to ensure proper formatting</p>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-medium mr-3 mt-0.5">
                                                2
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900">Fill Required Fields</p>
                                                <p>Ensure first_name, last_name, local_church, church_group, date_of_birth, and gender are included</p>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-medium mr-3 mt-0.5">
                                                3
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900">Upload & Import</p>
                                                <p>Upload your CSV file and review the preview before importing</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <div className="flex">
                                            <AlertCircle className="h-5 w-5 text-yellow-400 mr-2" />
                                            <div className="text-sm">
                                                <p className="font-medium text-yellow-800">Important Notes:</p>
                                                <ul className="mt-1 list-disc list-inside text-yellow-700 space-y-1">
                                                    <li>Maximum file size: 5MB</li>
                                                    <li>Supported format: CSV only</li>
                                                    <li>Existing members will be updated if found</li>
                                                    <li>Invalid records will be skipped</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Import Results */}
                            {importResult && (
                                <div className="bg-white shadow-sm rounded-lg border border-gray-200">
                                    <div className="p-6">
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            Import Results
                                        </h3>
                                        
                                        {importResult.success ? (
                                            <div className="space-y-4">
                                                <div className="flex items-center text-green-600">
                                                    <CheckCircle className="h-5 w-5 mr-2" />
                                                    <span className="font-medium">Import Successful!</span>
                                                </div>
                                                
                                                <div className="grid grid-cols-2 gap-4 text-sm">
                                                    <div className="text-center p-3 bg-green-50 rounded-lg">
                                                        <div className="text-2xl font-bold text-green-600">
                                                            {importResult.imported}
                                                        </div>
                                                        <div className="text-green-700">New Members</div>
                                                    </div>
                                                    <div className="text-center p-3 bg-blue-50 rounded-lg">
                                                        <div className="text-2xl font-bold text-blue-600">
                                                            {importResult.updated}
                                                        </div>
                                                        <div className="text-blue-700">Updated</div>
                                                    </div>
                                                    <div className="text-center p-3 bg-yellow-50 rounded-lg">
                                                        <div className="text-2xl font-bold text-yellow-600">
                                                            {importResult.skipped}
                                                        </div>
                                                        <div className="text-yellow-700">Skipped</div>
                                                    </div>
                                                    <div className="text-center p-3 bg-gray-50 rounded-lg">
                                                        <div className="text-2xl font-bold text-gray-600">
                                                            {importResult.total_processed}
                                                        </div>
                                                        <div className="text-gray-700">Total Processed</div>
                                                    </div>
                                                </div>
                                                
                                                {importResult.errors.length > 0 && (
                                                    <div className="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                        <h4 className="text-sm font-medium text-red-800 mb-2">
                                                            Errors ({importResult.errors.length}):
                                                        </h4>
                                                        <div className="text-sm text-red-700 max-h-32 overflow-y-auto">
                                                            {importResult.errors.slice(0, 5).map((error, index) => (
                                                                <p key={index} className="mb-1">• {error}</p>
                                                            ))}
                                                            {importResult.errors.length > 5 && (
                                                                <p className="text-red-600 font-medium">
                                                                    ... and {importResult.errors.length - 5} more errors
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                )}
                                                
                                                <div className="mt-4 flex space-x-3">
                                                    <Link
                                                        href={route('members.index')}
                                                        className="flex-1 bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
                                                    >
                                                        View Members
                                                    </Link>
                                                    <button
                                                        onClick={() => setImportResult(null)}
                                                        className="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors"
                                                    >
                                                        Import More
                                                    </button>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="space-y-4">
                                                <div className="flex items-center text-red-600">
                                                    <AlertCircle className="h-5 w-5 mr-2" />
                                                    <span className="font-medium">Import Failed</span>
                                                </div>
                                                
                                                <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
                                                    <p className="text-sm text-red-700">{importResult.message}</p>
                                                    {importResult.errors.length > 0 && (
                                                        <div className="mt-2">
                                                            <h4 className="text-sm font-medium text-red-800">Errors:</h4>
                                                            {importResult.errors.map((error, index) => (
                                                                <p key={index} className="text-sm text-red-700">• {error}</p>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                                
                                                <button
                                                    onClick={() => setImportResult(null)}
                                                    className="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors"
                                                >
                                                    Try Again
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Recent Imports */}
                            {stats?.recent_imports && stats.recent_imports.length > 0 && (
                                <div className="bg-white shadow-sm rounded-lg border border-gray-200">
                                    <div className="p-6">
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            Recent Imports
                                        </h3>
                                        
                                        <div className="space-y-3">
                                            {stats.recent_imports.map((importRecord, index) => (
                                                <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                    <div className="flex items-center">
                                                        <Clock className="h-4 w-4 text-gray-400 mr-2" />
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">
                                                                {importRecord.records} records
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {new Date(importRecord.date).toLocaleDateString()}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                        importRecord.status === 'success'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {importRecord.status}
                                                    </span>
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
        </AuthenticatedLayout>
    );
}