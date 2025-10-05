import React, { memo } from 'react';
import { Users, UserCheck, UserPlus, Calendar } from 'lucide-react';
import { Stats } from './types';

interface MembersStatsProps {
    stats: Stats;
    isLoading?: boolean;
}

const MembersStats = memo<MembersStatsProps>(({ stats, isLoading = false }) => {
    const statCards = [
        {
            title: 'Total Members',
            value: stats.total_members || 0,
            icon: Users,
            color: 'bg-blue-500',
            bgColor: 'bg-blue-50',
            textColor: 'text-blue-700',
        },
        {
            title: 'Active Members',
            value: stats.active_members || 0,
            icon: UserCheck,
            color: 'bg-green-500',
            bgColor: 'bg-green-50',
            textColor: 'text-green-700',
        },
        {
            title: 'New This Month',
            value: stats.new_this_month || 0,
            icon: UserPlus,
            color: 'bg-purple-500',
            bgColor: 'bg-purple-50',
            textColor: 'text-purple-700',
        },
        {
            title: 'Active Rate',
            value: stats.statistics?.active_percentage 
                ? `${Math.round(stats.statistics.active_percentage)}%` 
                : '0%',
            icon: Calendar,
            color: 'bg-indigo-500',
            bgColor: 'bg-indigo-50',
            textColor: 'text-indigo-700',
        },
    ];

    if (isLoading) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                {Array.from({ length: 4 }).map((_, index) => (
                    <div key={index} className="bg-white rounded-lg shadow p-6 animate-pulse">
                        <div className="flex items-center">
                            <div className="p-3 rounded-full bg-gray-200 w-12 h-12"></div>
                            <div className="ml-4 flex-1">
                                <div className="h-4 bg-gray-200 rounded w-20 mb-2"></div>
                                <div className="h-6 bg-gray-200 rounded w-16"></div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            {statCards.map((stat, index) => {
                const IconComponent = stat.icon;
                return (
                    <div key={index} className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
                        <div className="flex items-center">
                            <div className={`p-3 rounded-full ${stat.bgColor}`}>
                                <IconComponent className={`h-6 w-6 ${stat.textColor}`} />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                                <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
});

MembersStats.displayName = 'MembersStats';

export default MembersStats;