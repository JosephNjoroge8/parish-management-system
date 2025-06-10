// resources/js/Pages/Members/Create.jsx
import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Search, Plus, Edit, Eye, Trash2, Filter, Home } from 'lucide-react';

// Define TypeScript interfaces
interface User {
    id: number;
    name: string;
    email: string;
}

interface Family {
    id: number;
    family_name: string;
    head_of_family: string;
    phone?: string;
    email?: string;
    members_count: number;
    registration_date: string;
    status: 'active' | 'inactive';
}

interface FamiliesIndexProps {
    auth: {
        user: User;
    };
    families: {
        data: Family[];
        links: any;
        current_page: number;
        total: number;
        per_page: number;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

export default function FamiliesIndex({ auth, families, filters }: FamiliesIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = () => {
        router.get(route('families.index'), {
            search: searchTerm,
            status: statusFilter,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setStatusFilter('');
        router.get(route('families.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Parish Families
                    </h2>
                    <Link
                        href={route('families.create')}
                        className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Add Family</span>
                    </Link>
                </div>
            }
        >
            <Head title="Families" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Search and Filter Section */}
                    <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div className="flex flex-col md:flex-row gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                    <input
                                        type="text"
                                        placeholder="Search families..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyPress={handleKeyPress}
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            
                            <div className="flex items-center gap-4">
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                
                                <button
                                    onClick={handleSearch}
                                    className="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg flex items-center space-x-2 transition-colors"
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
                    </div>

                    {/* Families Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {families.data.map((family) => (
                            <div key={family.id} className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex items-center space-x-3">
                                        <div className="bg-blue-100 rounded-full p-2">
                                            <Home className="w-5 h-5 text-blue-600" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-medium text-gray-900">
                                                {family.family_name}
                                            </h3>
                                            <p className="text-sm text-gray-600">
                                                Head: {family.head_of_family}
                                            </p>
                                        </div>
                                    </div>
                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                        family.status === 'active' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-red-100 text-red-800'
                                    }`}>
                                        {family.status}
                                    </span>
                                </div>

                                <div className="space-y-2 mb-4">
                                    {family.phone && (
                                        <p className="text-sm text-gray-600">
                                            📞 {family.phone}
                                        </p>
                                    )}
                                    {family.email && (
                                        <p className="text-sm text-gray-600">
                                            ✉️ {family.email}
                                        </p>
                                    )}
                                    <p className="text-sm text-gray-600">
                                        👥 {family.members_count} members
                                    </p>
                                    <p className="text-sm text-gray-600">
                                        📅 Registered: {new Date(family.registration_date).toLocaleDateString()}
                                    </p>
                                </div>

                                <div className="flex justify-end space-x-2">
                                    <Link
                                        href={route('families.show', family.id)}
                                        className="text-blue-600 hover:text-blue-800 p-1 rounded transition-colors"
                                        title="View Family"
                                    >
                                        <Eye className="w-4 h-4" />
                                    </Link>
                                    <Link
                                        href={route('families.edit', family.id)}
                                        className="text-yellow-600 hover:text-yellow-800 p-1 rounded transition-colors"
                                        title="Edit Family"
                                    >
                                        <Edit className="w-4 h-4" />
                                    </Link>
                                    <button
                                        onClick={() => {
                                            if (confirm('Are you sure you want to delete this family?')) {
                                                router.delete(route('families.destroy', family.id));
                                            }
                                        }}
                                        className="text-red-600 hover:text-red-800 p-1 rounded transition-colors"
                                        title="Delete Family"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Pagination would go here */}
                    {families.data.length === 0 && (
                        <div className="text-center py-12">
                            <Home className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No families found</h3>
                            <p className="text-gray-600 mb-4">Start by adding a new family to the parish.</p>
                            <Link
                                href={route('families.create')}
                                className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center space-x-2 transition-colors"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Add First Family</span>
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
