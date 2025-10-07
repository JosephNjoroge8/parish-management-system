import { memo, useMemo } from 'react';
import { Users, UserCheck, UserPlus, TrendingUp } from 'lucide-react';
import { Stats } from './types';
import { STATS_CARDS_CONFIG, COLOR_SCHEMES, LOADING_STATES } from './constants';

interface MembersStatsProps {
    stats: Stats;
    isLoading?: boolean;
}

interface StatCardProps {
    title: string;
    value: string | number;
    icon: React.ComponentType<any>;
    colorScheme: keyof typeof COLOR_SCHEMES;
    description?: string;
    isLoading?: boolean;
}

const StatCard = memo<StatCardProps>(({ 
    title, 
    value, 
    icon: IconComponent, 
    colorScheme, 
    description,
    isLoading = false 
}) => {
    const colors = COLOR_SCHEMES[colorScheme];
    
    if (isLoading) {
        return (
            <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 animate-pulse">
                <div className="flex items-center">
                    <div className={`p-3 rounded-full ${LOADING_STATES.skeleton.bg} w-12 h-12`}></div>
                    <div className="ml-4 flex-1">
                        <div className={`${LOADING_STATES.skeleton.height} ${LOADING_STATES.skeleton.bg} rounded w-20 mb-2`}></div>
                        <div className={`h-6 ${LOADING_STATES.skeleton.bg} rounded w-16`}></div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-200 hover:scale-[1.02]">
            <div className="flex items-center">
                <div className={`p-3 rounded-full ${colors.bg} ring-1 ring-black/5`}>
                    <IconComponent className={`h-6 w-6 ${colors.icon}`} />
                </div>
                <div className="ml-4 flex-1">
                    <p className="text-sm font-medium text-gray-600 truncate" title={description || title}>
                        {title}
                    </p>
                    <p className="text-2xl font-bold text-gray-900 tracking-tight">
                        {typeof value === 'number' ? value.toLocaleString() : value}
                    </p>
                </div>
            </div>
        </div>
    );
});

StatCard.displayName = 'StatCard';

const getIconComponent = (iconName: string) => {
    switch (iconName) {
        case 'Users': return Users;
        case 'UserCheck': return UserCheck;
        case 'UserPlus': return UserPlus;
        case 'TrendingUp': return TrendingUp;
        default: return Users;
    }
};

const MembersStats = memo<MembersStatsProps>(({ stats, isLoading = false }) => {
    const statCards = useMemo(() => {
        return STATS_CARDS_CONFIG.map(config => {
            let value: string | number;
            
            if (config.key === 'active_percentage') {
                const percentage = stats.statistics?.active_percentage || 0;
                value = `${Math.round(percentage)}%`;
            } else if (config.key === 'total_members') {
                value = stats.total_members || 0;
            } else if (config.key === 'active_members') {
                value = stats.active_members || 0;
            } else if (config.key === 'new_this_month') {
                value = stats.new_this_month || 0;
            } else {
                value = 0;
            }

            return {
                ...config,
                value,
                icon: getIconComponent(config.icon),
            };
        });
    }, [stats]);

    if (isLoading) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                {Array.from({ length: 4 }).map((_, index) => (
                    <StatCard
                        key={index}
                        title=""
                        value=""
                        icon={Users}
                        colorScheme="blue"
                        isLoading={true}
                    />
                ))}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            {statCards.map((stat, index) => (
                <StatCard
                    key={stat.key}
                    title={stat.title}
                    value={stat.value}
                    icon={stat.icon}
                    colorScheme={stat.color as keyof typeof COLOR_SCHEMES}
                    description={stat.description}
                    isLoading={false}
                />
            ))}
        </div>
    );
});

MembersStats.displayName = 'MembersStats';

export default MembersStats;