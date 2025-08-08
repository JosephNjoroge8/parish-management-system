import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Users, 
    Plus, 
    Search, 
    Filter,
    Eye,
    UserPlus,
    RefreshCw,
    TrendingUp
} from 'lucide-react';

interface ChurchGroup {
    id: string;
    name: string;
    icon: string;
    color: string;
    description: string;
    members_count: number;
    active_members: number;
    inactive_members: number;
    latest_member_joined?: string;
    created_date?: string;
}

interface GroupStatistics {
    total_groups: number;
    total_members: number;
    groups_with_members: number;
    average_members_per_group: number;
    most_popular_group: string;
    groups_breakdown: ChurchGroup[];
}

interface Props {
    statistics: GroupStatistics;
    filters: {
        search?: string;
        sort?: string;
        direction?: string;
    };
    error?: string;
}

export default function Index({ 
    statistics,
    filters = {},
    error 
}: Props) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [loading, setLoading] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('community-groups.index'), {
            search: searchTerm,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleClearFilters = () => {
        setSearchTerm('');
        router.get(route('community-groups.index'));
    };

    const refreshData = async () => {
        setLoading(true);
        router.reload({
            onFinish: () => setLoading(false)
        });
    };

    const filteredGroups = statistics.groups_breakdown.filter(group =>
        group.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Church Groups
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage church groups and their members
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={refreshData}
                            disabled={loading}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                        >
                            <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                            {loading ? 'Refreshing...' : 'Refresh'}
                        </button>
                        <Link href={route('members.create')}>
                            <button className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <Plus className="w-4 h-4 mr-2" />
                                Add New Member
                            </button>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Church Groups" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {error && (
                        <div className="bg-red-50 border border-red-200 rounded-md p-4">
                            <p className="text-red-600">{error}</p>
                        </div>
                    )}

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Total Groups</p>
                                        <p className="text-2xl font-bold text-gray-900">{statistics.total_groups}</p>
                                        <p className="text-xs text-gray-500 mt-1">Active church groups</p>
                                    </div>
                                    <div className="text-3xl">👥</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Total Members</p>
                                        <p className="text-2xl font-bold text-gray-900">{statistics.total_members}</p>
                                        <p className="text-xs text-gray-500 mt-1">Across all groups</p>
                                    </div>
                                    <Users className="h-8 w-8 text-green-400" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Active Groups</p>
                                        <p className="text-2xl font-bold text-gray-900">{statistics.groups_with_members}</p>
                                        <p className="text-xs text-gray-500 mt-1">Groups with members</p>
                                    </div>
                                    <TrendingUp className="h-8 w-8 text-purple-400" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Avg Members/Group</p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {Math.round(statistics.average_members_per_group || 0)}
                                        </p>
                                        <p className="text-xs text-gray-500 mt-1">Per group</p>
                                    </div>
                                    <div className="text-3xl">📊</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Most Popular Group */}
                    {statistics.most_popular_group && (
                        <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg p-6">
                            <div className="text-white">
                                <h3 className="text-lg font-semibold mb-2 flex items-center">
                                    <div className="text-2xl mr-3">🏆</div>
                                    Most Popular Group
                                </h3>
                                <p className="text-2xl font-bold">{statistics.most_popular_group}</p>
                                <p className="text-sm opacity-90">Has the most members in the parish</p>
                            </div>
                        </div>
                    )}

                    {/* Search Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center gap-2 mb-4">
                                <Filter className="w-5 h-5 text-gray-600" />
                                <h3 className="text-lg font-medium text-gray-900">Search Groups</h3>
                            </div>
                            
                            <form onSubmit={handleSearch} className="space-y-4">
                                <div className="flex gap-4">
                                    <div className="flex-1 relative">
                                        <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                        <input
                                            type="text"
                                            placeholder="Search church groups..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        className="inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    >
                                        Search
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleClearFilters}
                                        className="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Groups Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredGroups.length === 0 ? (
                            <div className="col-span-full bg-white rounded-lg shadow p-8 text-center">
                                <div className="text-6xl mb-4">😔</div>
                                <p className="text-gray-500 font-medium mb-2">No church groups found</p>
                                <p className="text-sm text-gray-400 mb-4">Groups will appear here based on member registrations</p>
                                <Link href={route('members.create')}>
                                    <button className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <Plus className="w-4 h-4 mr-2" />
                                        Add First Member
                                    </button>
                                </Link>
                            </div>
                        ) : (
                            filteredGroups.map((group) => (
                                <div key={group.id} className="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-all duration-200 border-l-4 border-blue-500">
                                    <div className="p-6">
                                        {/* Group Header */}
                                        <div className="flex items-center justify-between mb-4">
                                            <div className="flex items-center space-x-3">
                                                <div className="text-3xl">{group.icon}</div>
                                                <div>
                                                    <h3 className="text-lg font-semibold text-gray-900">{group.name}</h3>
                                                    <p className="text-sm text-gray-600">{group.description}</p>
                                                </div>
                                            </div>
                                            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                {group.members_count} members
                                            </span>
                                        </div>
                                        
                                        {/* Group Stats */}
                                        <div className="grid grid-cols-2 gap-4 mb-4">
                                            <div className="text-center p-3 bg-green-50 rounded-lg">
                                                <div className="text-lg font-bold text-green-600">{group.active_members}</div>
                                                <div className="text-xs text-green-600">Active</div>
                                            </div>
                                            <div className="text-center p-3 bg-gray-50 rounded-lg">
                                                <div className="text-lg font-bold text-gray-600">{group.inactive_members}</div>
                                                <div className="text-xs text-gray-600">Inactive</div>
                                            </div>
                                        </div>

                                        {group.latest_member_joined && (
                                            <div className="mb-4 p-3 bg-gray-50 rounded-lg">
                                                <div className="text-xs text-gray-500">Latest Member Joined:</div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {new Date(group.latest_member_joined).toLocaleDateString()}
                                                </div>
                                            </div>
                                        )}

                                        {/* Action Buttons */}
                                        <div className="flex gap-2">
                                            <Link 
                                                href={route('community-groups.show', encodeURIComponent(group.name))}
                                                className="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                            >
                                                <Eye className="w-4 h-4 mr-2" />
                                                View Members
                                            </Link>
                                            <Link
                                                href={route('members.create', { church_group: group.name })}
                                                className="flex-1 inline-flex justify-center items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                            >
                                                <UserPlus className="w-4 h-4 mr-2" />
                                                Add Member
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Link href={route('members.index')}>
                                    <button className="w-full inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                        <Users className="w-5 h-5 mr-2" />
                                        View All Members
                                    </button>
                                </Link>
                                <Link href={route('members.create')}>
                                    <button className="w-full inline-flex items-center justify-center px-4 py-3 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                        <Plus className="w-5 h-5 mr-2" />
                                        Register New Member
                                    </button>
                                </Link>
                                <Link href={route('reports.index')}>
                                    <button className="w-full inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                        <Filter className="w-5 h-5 mr-2" />
                                        View Reports
                                    </button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}