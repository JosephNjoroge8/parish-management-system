// resources/js/Pages/Dashboard.jsx
import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Users, Home, Calendar, TrendingUp } from 'lucide-react';

interface DashboardProps extends PageProps {
    stats: {
        total_members: number;
        active_members: number;
        total_families: number;
        active_families: number;
    };
    recentActivities: Array<{
        title: string;
        time: string;
    }>;
    upcomingEvents: Array<{
        name: string;
        date: string;
        location: string;
    }>;
    analytics: {
        sacramentStats: any;
        membersByGender: any;
        activityStats: any;
    };
    welcome_message: string;
}

// Define valid color types
type ColorType = 'blue' | 'green' | 'purple' | 'orange';

// Safe StatCard component that handles undefined values
const StatCard = ({ 
    title, 
    value, 
    icon: Icon, 
    color = 'blue',
    subtitle 
}: {
    title: string;
    value: number | undefined | null;
    icon: any;
    color?: ColorType;
    subtitle?: string;
}) => {
    // Safely handle the value - ensure it's a number
    const safeValue = typeof value === 'number' ? value : 0;
    
    const colorClasses = {
        blue: 'bg-blue-50 text-blue-600 border-blue-200',
        green: 'bg-green-50 text-green-600 border-green-200',
        purple: 'bg-purple-50 text-purple-600 border-purple-200',
        orange: 'bg-orange-50 text-orange-600 border-orange-200',
    };

    return (
        <div className="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-gray-600">{title}</p>
                    <p className="text-3xl font-bold text-gray-900">
                        {safeValue.toLocaleString()}
                    </p>
                    {subtitle && (
                        <p className="text-sm text-gray-500 mt-1">{subtitle}</p>
                    )}
                </div>
                <div className={`p-3 rounded-full ${colorClasses[color] || colorClasses.blue}`}>
                    <Icon className="w-6 h-6" />
                </div>
            </div>
        </div>
    );
};

export default function Dashboard({ 
    auth, 
    stats, 
    recentActivities, 
    upcomingEvents, 
    analytics, 
    welcome_message 
}: DashboardProps) {
    // Provide default values if stats is undefined
    const safeStats = {
        total_members: stats?.total_members ?? 0,
        active_members: stats?.active_members ?? 0,
        total_families: stats?.total_families ?? 0,
        active_families: stats?.active_families ?? 0,
    };

    const safeRecentActivities = recentActivities ?? [
        { title: 'Welcome to Parish System', time: 'Just now' }
    ];

    const safeUpcomingEvents = upcomingEvents ?? [
        { name: 'Sunday Mass', date: 'Every Sunday 8:00 AM', location: 'Main Cathedral' }
    ];

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Welcome Message */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-2xl font-bold mb-2">
                                Welcome back, {auth.user.name}!
                            </h3>
                            <p className="text-gray-600">
                                {welcome_message || 'Welcome to St. Mary\'s Parish Management System!'}
                            </p>
                        </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <StatCard
                            title="Total Members"
                            value={safeStats.total_members}
                            icon={Users}
                            color="blue"
                        />
                        <StatCard
                            title="Active Members"
                            value={safeStats.active_members}
                            icon={Users}
                            color="green"
                        />
                        <StatCard
                            title="Total Families"
                            value={safeStats.total_families}
                            icon={Home}
                            color="purple"
                        />
                        <StatCard
                            title="Active Families"
                            value={safeStats.active_families}
                            icon={Home}
                            color="orange"
                        />
                    </div>

                    {/* Recent Activities and Upcoming Events */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Recent Activities */}
                        <div className="bg-white rounded-lg shadow-md p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <TrendingUp className="w-5 h-5 mr-2" />
                                Recent Activities
                            </h3>
                            <div className="space-y-3">
                                {safeRecentActivities.slice(0, 5).map((activity, index) => (
                                    <div key={index} className="flex justify-between items-start">
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-900">
                                                {activity.title}
                                            </p>
                                        </div>
                                        <span className="text-xs text-gray-500 ml-2">
                                            {activity.time}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Upcoming Events */}
                        <div className="bg-white rounded-lg shadow-md p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <Calendar className="w-5 h-5 mr-2" />
                                Upcoming Events
                            </h3>
                            <div className="space-y-4">
                                {safeUpcomingEvents.slice(0, 5).map((event, index) => (
                                    <div key={index} className="border-l-4 border-blue-500 pl-4">
                                        <p className="text-sm font-medium text-gray-900">
                                            {event.name}
                                        </p>
                                        <p className="text-xs text-gray-600">
                                            {event.date}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            📍 {event.location}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="mt-8 bg-white rounded-lg shadow-md p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Quick Actions
                        </h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <button className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Add Member
                            </button>
                            <button className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                Register Family
                            </button>
                            <button className="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                                Schedule Activity
                            </button>
                            <button className="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                                View Reports
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}