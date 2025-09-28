import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import StatsCard from '@/Components/StatsCard';
import { Search, Plus, Filter, Calendar, MapPin, Users, Clock } from 'lucide-react';

interface Activity {
    id: number;
    title: string;
    description: string;
    activity_type: string;
    start_date: string;
    end_date?: string;
    start_time?: string;
    end_time?: string;
    location?: string;
    organizer?: string;
    status: string;
    participant_count: number;
    community_group?: {
        id: number;
        name: string;
    };
}

interface Statistics {
    total_activities: number;
    upcoming_activities: number;
    active_activities: number;
    this_month_activities: number;
}

interface Props {
    activities: {
        data: Activity[];
        links: any[];
        meta: any;
    };
    statistics: Statistics;
    activityTypes: Record<string, string>;
    statuses: Record<string, string>;
    communityGroups: Array<{id: number; name: string}>;
    filters: {
        search?: string;
        activity_type?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}

export default function Index({ activities, statistics, activityTypes, statuses, communityGroups, filters }: Props) {
    const { auth } = usePage().props as any;
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedType, setSelectedType] = useState(filters.activity_type || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleSearch = () => {
        router.get(route('activities.index'), {
            search: searchTerm,
            activity_type: selectedType,
            status: selectedStatus,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedType('');
        setSelectedStatus('');
        router.get(route('activities.index'));
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'planned': return 'bg-blue-100 text-blue-800';
            case 'active': return 'bg-green-100 text-green-800';
            case 'completed': return 'bg-gray-100 text-gray-800';
            case 'cancelled': return 'bg-red-100 text-red-800';
            case 'postponed': return 'bg-yellow-100 text-yellow-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'mass': return 'bg-purple-100 text-purple-800';
            case 'meeting': return 'bg-blue-100 text-blue-800';
            case 'event': return 'bg-green-100 text-green-800';
            case 'workshop': return 'bg-orange-100 text-orange-800';
            case 'retreat': return 'bg-indigo-100 text-indigo-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Parish Activities
                    </h2>
                    {auth.permissions.includes('manage activities') && (
                        <Link href={route('activities.create')}>
                            <PrimaryButton>
                                + Add Activity
                            </PrimaryButton>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Activities" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <StatsCard
                            title="Total Activities"
                            value={statistics.total_activities}
                            icon={Calendar}
                        />
                        <StatsCard
                            title="Upcoming"
                            value={statistics.upcoming_activities}
                            icon={Clock}
                        />
                        <StatsCard
                            title="Active"
                            value={statistics.active_activities}
                            icon={Users}
                        />
                        <StatsCard
                            title="This Month"
                            value={statistics.this_month_activities}
                            icon={Calendar}
                        />
                    </div>

                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center mb-4">
                                <Filter className="h-5 w-5 mr-2" />
                                <h3 className="text-lg font-semibold">Filter Activities</h3>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <TextInput
                                        placeholder="Search activities..."
                                        value={searchTerm}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
                                        onKeyPress={(e: React.KeyboardEvent) => e.key === 'Enter' && handleSearch()}
                                        className="w-full"
                                    />
                                </div>

                                <div>
                                    <select
                                        value={selectedType}
                                        onChange={(e) => setSelectedType(e.target.value)}
                                        className="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">All Types</option>
                                        {Object.entries(activityTypes).map(([key, label]) => (
                                            <option key={key} value={key}>
                                                {label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <select
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">All Statuses</option>
                                        {Object.entries(statuses).map(([key, label]) => (
                                            <option key={key} value={key}>
                                                {label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex gap-2">
                                    <PrimaryButton onClick={handleSearch}>
                                        <Search className="h-4 w-4 mr-2" />
                                        Search
                                    </PrimaryButton>
                                    <SecondaryButton onClick={clearFilters}>
                                        Clear
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Activities List */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <h3 className="text-lg font-semibold">Activities ({activities.meta.total})</h3>
                        </div>
                        <div className="p-6">
                            <div className="space-y-4">
                                {activities.data.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500">
                                        No activities found. {auth.permissions.includes('manage activities') && (
                                            <Link href={route('activities.create')} className="text-blue-600 hover:underline">
                                                Create your first activity
                                            </Link>
                                        )}
                                    </div>
                                ) : (
                                    activities.data.map((activity) => (
                                        <div
                                            key={activity.id}
                                            className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                        >
                                            <div className="flex justify-between items-start mb-2">
                                                <div className="flex-1">
                                                    <h3 className="font-semibold text-lg mb-1">
                                                        <Link
                                                            href={route('activities.show', activity.id)}
                                                            className="hover:text-blue-600"
                                                        >
                                                            {activity.title}
                                                        </Link>
                                                    </h3>
                                                    {activity.description && (
                                                        <p className="text-gray-600 mb-2">{activity.description}</p>
                                                    )}
                                                </div>
                                                <div className="flex flex-col gap-2 ml-4">
                                                    <span className={`px-2 py-1 text-xs rounded-full ${getTypeColor(activity.activity_type)}`}>
                                                        {activityTypes[activity.activity_type]}
                                                    </span>
                                                    <span className={`px-2 py-1 text-xs rounded-full ${getStatusColor(activity.status)}`}>
                                                        {statuses[activity.status]}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                                <div className="flex items-center">
                                                    <Calendar className="h-4 w-4 mr-2" />
                                                    {new Date(activity.start_date).toLocaleDateString()}
                                                    {activity.start_time && (
                                                        <span className="ml-1">
                                                            at {activity.start_time}
                                                        </span>
                                                    )}
                                                </div>

                                                {activity.location && (
                                                    <div className="flex items-center">
                                                        <MapPin className="h-4 w-4 mr-2" />
                                                        {activity.location}
                                                    </div>
                                                )}

                                                <div className="flex items-center">
                                                    <Users className="h-4 w-4 mr-2" />
                                                    {activity.participant_count} participants
                                                </div>
                                            </div>

                                            {activity.community_group && (
                                                <div className="mt-2">
                                                    <span className="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded border">
                                                        {activity.community_group.name}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    ))
                                )}
                            </div>

                            {/* Pagination */}
                            {activities.meta.last_page > 1 && (
                                <div className="flex justify-center mt-6">
                                    <div className="flex space-x-1">
                                        {activities.links.map((link: any, index: number) => (
                                            <button
                                                key={index}
                                                onClick={() => link.url && router.visit(link.url)}
                                                className={`px-3 py-2 rounded ${
                                                    link.active
                                                        ? 'bg-blue-600 text-white'
                                                        : link.url
                                                        ? 'bg-gray-200 hover:bg-gray-300 text-gray-700'
                                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                }`}
                                                disabled={!link.url}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
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