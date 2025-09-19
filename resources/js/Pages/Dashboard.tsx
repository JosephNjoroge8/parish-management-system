// resources/js/Pages/Dashboard.tsx
import React, { useEffect, useState, useCallback } from 'react';
import SidebarLayout from '@/Layouts/SidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { 
    Users, 
    Home, 
    Calendar, 
    TrendingUp, 
    DollarSign, 
    Star, 
    UserPlus,
    AlertTriangle,
    Activity,
    BarChart3,
    Church,
    Heart,
    Sparkles,
    Clock,
    MapPin,
    ArrowUpRight,
    Plus,
    RefreshCw
} from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: {
        can_manage_users: boolean;
        can_access_members: boolean;
        can_manage_members: boolean;
        can_access_families: boolean;
        can_manage_families: boolean;
        can_access_sacraments: boolean;
        can_manage_sacraments: boolean;
        can_access_tithes: boolean;
        can_manage_tithes: boolean;
        can_access_reports: boolean;
        can_view_financial_reports: boolean;
        can_access_community_groups: boolean;
        can_manage_community_groups: boolean;
    };
}

interface Stats {
    total_members?: number;
    active_members?: number;
    new_members_this_month?: number;
    member_growth_rate?: number;
    total_families?: number;
    active_families?: number;
    new_families_this_month?: number;
    total_tithes_this_month?: number;
    total_tithes_this_year?: number;
    tithe_contributors_this_month?: number;
    average_tithe_amount?: number;
    sacraments_this_month?: number;
    sacraments_this_year?: number;
    active_community_groups?: number;
    total_group_members?: number;
    group_participation_rate?: number;
    total_users?: number;
    active_users?: number;
    church_distribution?: Record<string, number>;
    group_distribution?: Record<string, number>;
    status_distribution?: Record<string, number>;
    gender_distribution?: {
        male: number;
        female: number;
    };
    age_groups?: {
        children: number;
        youth: number;
        adults: number;
        seniors: number;
    };
}

interface Activity {
    id: string;
    type: string;
    title: string;
    description: string;
    time: string;
    icon: string;
    color: string;
    link?: string;
}

interface Event {
    id: string;
    name: string;
    date: string;
    location: string;
    type: string;
    description: string;
}

interface QuickAction {
    name: string;
    description: string;
    icon: string;
    color: string;
    link: string;
}

interface Alert {
    type: 'warning' | 'info' | 'error' | 'success';
    title: string;
    message: string;
    action: string;
    link: string;
}

interface DashboardProps extends PageProps {
    user: User;
    stats: Stats;
    recentActivities: Activity[];
    upcomingEvents: Event[];
    analytics: {
        membershipTrends: any[];
        financialTrends: any[];
    };
    quickActions: QuickAction[];
    alerts: Alert[];
    welcome_message: string;
}

// Catholic-inspired color palette
type CatholicColorType = 'royal-purple' | 'sacred-gold' | 'divine-blue' | 'blessed-white' | 'holy-red' | 'peaceful-green' | 'angelic-silver';

const StatCard = ({ 
    title, 
    value, 
    icon: Icon, 
    color = 'royal-purple',
    subtitle,
    trend,
    link
}: {
    title: string;
    value: number | undefined | null;
    icon: any;
    color?: CatholicColorType;
    subtitle?: string;
    trend?: number;
    link?: string;
}) => {
    const safeValue = typeof value === 'number' ? value : 0;
    
    const colorClasses = {
        'royal-purple': 'from-purple-600 to-purple-800 text-white',
        'sacred-gold': 'from-yellow-500 to-yellow-600 text-white',
        'divine-blue': 'from-blue-600 to-blue-800 text-white',
        'blessed-white': 'from-gray-50 to-white text-gray-900 border border-gray-200',
        'holy-red': 'from-red-600 to-red-800 text-white',
        'peaceful-green': 'from-green-600 to-green-800 text-white',
        'angelic-silver': 'from-gray-400 to-gray-600 text-white',
    };

    const iconBgClasses = {
        'royal-purple': 'bg-white/20',
        'sacred-gold': 'bg-white/20',
        'divine-blue': 'bg-white/20',
        'blessed-white': 'bg-purple-100',
        'holy-red': 'bg-white/20',
        'peaceful-green': 'bg-white/20',
        'angelic-silver': 'bg-white/20',
    };

    const content = (
        <div className={`
            relative overflow-hidden rounded-xl shadow-lg bg-gradient-to-br ${colorClasses[color]} 
            p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-xl
        `}>
            {/* Decorative elements */}
            <div className="absolute top-0 right-0 w-32 h-32 opacity-10">
                <div className="absolute inset-0 bg-white rounded-full transform translate-x-16 -translate-y-16"></div>
            </div>
            
            <div className="relative">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <p className={`text-sm font-medium ${color === 'blessed-white' ? 'text-gray-600' : 'text-white/80'} mb-1`}>
                            {title}
                        </p>
                        <p className="text-3xl font-bold mb-2">
                            {title.toLowerCase().includes('amount') || title.toLowerCase().includes('tithe') ? 
                                `KES ${safeValue.toLocaleString()}` : 
                                safeValue.toLocaleString()
                            }
                        </p>
                        {subtitle && (
                            <p className={`text-sm ${color === 'blessed-white' ? 'text-gray-500' : 'text-white/70'}`}>
                                {subtitle}
                            </p>
                        )}
                        {trend !== undefined && (
                            <div className={`flex items-center text-xs mt-2 ${
                                trend >= 0 ? 'text-green-200' : 'text-red-200'
                            }`}>
                                <ArrowUpRight className={`w-3 h-3 mr-1 ${trend < 0 ? 'rotate-90' : ''}`} />
                                {Math.abs(trend)}% from last month
                            </div>
                        )}
                    </div>
                    <div className={`p-3 rounded-xl ${iconBgClasses[color]}`}>
                        <Icon className={`w-6 h-6 ${color === 'blessed-white' ? 'text-purple-600' : 'text-white'}`} />
                    </div>
                </div>
            </div>
        </div>
    );

    return link ? (
        <Link href={link} className="block">
            {content}
        </Link>
    ) : content;
};

const ActivityCard = ({ activity }: { activity: Activity }) => {
    const getIcon = (iconName: string) => {
        const icons: { [key: string]: any } = {
            'user-plus': UserPlus,
            'dollar-sign': DollarSign,
            'star': Star,
            'home': Home,
            'users': Users,
            'chart-bar': BarChart3,
            'church': Church,
            'heart': Heart,
        };
        return icons[iconName] || Activity;
    };

    const Icon = getIcon(activity.icon);

    const colorClasses = {
        green: 'bg-green-50 text-green-700 border-green-200',
        emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        blue: 'bg-blue-50 text-blue-700 border-blue-200',
        purple: 'bg-purple-50 text-purple-700 border-purple-200',
        orange: 'bg-orange-50 text-orange-700 border-orange-200',
        gold: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    };

    const content = (
        <div className="flex items-start space-x-4 p-4 bg-white rounded-lg border border-gray-100 hover:border-purple-200 hover:shadow-md transition-all duration-200">
            <div className={`p-2 rounded-lg border ${colorClasses[activity.color as keyof typeof colorClasses] || colorClasses.purple}`}>
                <Icon className="w-5 h-5" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-gray-900 mb-1">
                    {activity.title}
                </p>
                <p className="text-xs text-gray-600">
                    {activity.description}
                </p>
                <div className="flex items-center mt-2">
                    <Clock className="w-3 h-3 text-gray-400 mr-1" />
                    <span className="text-xs text-gray-500">{activity.time}</span>
                </div>
            </div>
        </div>
    );

    return activity.link ? (
        <Link href={activity.link} className="block">
            {content}
        </Link>
    ) : content;
};

const QuickActionCard = ({ action }: { action: QuickAction }) => {
    const getIcon = (iconName: string) => {
        const icons: { [key: string]: any } = {
            'user-plus': UserPlus,
            'users': Users,
            'dollar-sign': DollarSign,
            'star': Star,
            'chart-bar': BarChart3,
            'user-group': Users,
            'home': Home,
            'church': Church,
            'heart': Heart,
            'plus': Plus,
        };
        return icons[iconName] || Plus;
    };

    const Icon = getIcon(action.icon);

    const colorClasses = {
        blue: 'from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800',
        green: 'from-green-600 to-green-700 hover:from-green-700 hover:to-green-800',
        emerald: 'from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800',
        purple: 'from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800',
        indigo: 'from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800',
        orange: 'from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800',
        gray: 'from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800',
        gold: 'from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700',
    };

    return (
        <Link
            href={action.link}
            className={`
                relative overflow-hidden bg-gradient-to-br ${colorClasses[action.color as keyof typeof colorClasses] || colorClasses.purple} 
                text-white p-6 rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-lg
                flex flex-col items-center text-center space-y-3 group
            `}
        >
            {/* Decorative background */}
            <div className="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            
            <div className="relative">
                <div className="p-3 bg-white/20 rounded-full group-hover:bg-white/30 transition-colors">
                    <Icon className="w-6 h-6" />
                </div>
                <div className="mt-3">
                    <p className="font-semibold text-sm">{action.name}</p>
                    <p className="text-xs opacity-90 mt-1">{action.description}</p>
                </div>
            </div>
        </Link>
    );
};

const AlertCard = ({ alert }: { alert: Alert }) => {
    const alertStyles = {
        warning: 'bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-400 text-yellow-800',
        info: 'bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-400 text-blue-800',
        error: 'bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-400 text-red-800',
        success: 'bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 text-green-800',
    };

    return (
        <div className={`rounded-lg p-4 shadow-sm ${alertStyles[alert.type]}`}>
            <div className="flex items-start">
                <AlertTriangle className="w-5 h-5 mt-0.5 mr-3 flex-shrink-0" />
                <div className="flex-1">
                    <h4 className="font-semibold text-sm">{alert.title}</h4>
                    <p className="text-sm mt-1 opacity-90">{alert.message}</p>
                    {alert.link && (
                        <Link
                            href={alert.link}
                            className="text-sm font-medium underline mt-2 inline-flex items-center hover:no-underline"
                        >
                            {alert.action}
                            <ArrowUpRight className="w-3 h-3 ml-1" />
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
};

export default function Dashboard({ 
    user,
    stats, 
    recentActivities, 
    upcomingEvents, 
    analytics, 
    quickActions,
    alerts,
    welcome_message 
}: DashboardProps) {
    const safeStats = stats || {};
    const safeRecentActivities = recentActivities || [];
    const safeUpcomingEvents = upcomingEvents || [];
    const safeQuickActions = quickActions || [];
    const safeAlerts = alerts || [];

    // State for real-time stats updates
    const [liveStats, setLiveStats] = useState<Stats>(safeStats);
    const [lastUpdated, setLastUpdated] = useState<Date>(new Date());
    const [isRefreshing, setIsRefreshing] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    // Function to refresh dashboard data
    const refreshData = useCallback(async () => {
        setIsRefreshing(true);
        setError(null);
        try {
            // Use router.reload to refresh data from Laravel controller
            router.reload({
                only: ['stats', 'recentActivities', 'upcomingEvents'],
                onSuccess: (page) => {
                    setLiveStats(page.props.stats || safeStats);
                    setLastUpdated(new Date());
                },
                onError: (errors) => {
                    setError('Failed to refresh dashboard data');
                }
            });
        } catch (error) {
            console.error('Failed to refresh dashboard data:', error);
            setError('Network error occurred');
        } finally {
            setIsRefreshing(false);
        }
    }, [safeStats]);

    // Auto-refresh every 5 minutes
    useEffect(() => {
        const interval = setInterval(refreshData, 5 * 60 * 1000);
        return () => clearInterval(interval);
    }, [refreshData]);

    const getGreeting = () => {
        const hour = new Date().getHours();
        if (hour < 12) return '🌅 Good morning';
        if (hour < 17) return '☀️ Good afternoon';
        return '🌙 Good evening';
    };

    return (
        <SidebarLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 flex items-center">
                            <Church className="w-6 h-6 mr-2 text-purple-600" />
                            Our Lady of Consolata Cathedral Parish
                        </h1>
                        <p className="text-sm text-gray-600 mt-1">Manage your parish community with grace</p>
                    </div>
                    <div className="text-right">
                        <div className="flex items-center space-x-2">
                            {isRefreshing && (
                                <div className="flex items-center text-sm text-gray-500">
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-600 mr-2"></div>
                                    Updating...
                                </div>
                            )}
                            {error && (
                                <div className="flex items-center text-sm text-red-500" title={error}>
                                    <AlertTriangle className="w-4 h-4 mr-1" />
                                    Error
                                </div>
                            )}
                            <button
                                onClick={refreshData}
                                disabled={isRefreshing}
                                className="text-sm text-gray-500 hover:text-gray-700 disabled:opacity-50 transition-colors"
                                title="Refresh data"
                            >
                                <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                            </button>
                        </div>
                        <p className="text-sm text-gray-500">Welcome back,</p>
                        <p className="font-semibold text-gray-900">{user.name}</p>
                        <p className="text-xs text-gray-400">
                            Last updated: {lastUpdated.toLocaleTimeString()}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="p-6 space-y-6">
                {/* Welcome Section */}
                <div className="bg-gradient-to-r from-purple-600 via-blue-600 to-purple-800 rounded-2xl shadow-xl p-8 text-white relative overflow-hidden">
                    {/* Decorative elements */}
                    <div className="absolute top-0 right-0 w-64 h-64 opacity-10">
                        <Church className="w-full h-full" />
                    </div>
                    <div className="absolute -bottom-4 -left-4 w-32 h-32 bg-white/10 rounded-full"></div>
                    
                    <div className="relative">
                        <h2 className="text-3xl font-bold mb-2">
                            {getGreeting()}, {user.name}! ✨
                        </h2>
                        <p className="text-xl text-purple-100 mb-4">
                            {welcome_message || "May God's blessings guide your parish ministry today."}
                        </p>
                        <div className="flex items-center text-purple-200">
                            <Sparkles className="w-4 h-4 mr-2" />
                            <span className="text-sm">Serving with faith, leading with love</span>
                        </div>
                    </div>
                </div>

                {/* Alerts */}
                {safeAlerts.length > 0 && (
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                            <AlertTriangle className="w-5 h-5 mr-2 text-yellow-500" />
                            Attention Required
                        </h3>
                        <div className="grid gap-4">
                            {safeAlerts.map((alert, index) => (
                                <AlertCard key={index} alert={alert} />
                            ))}
                        </div>
                    </div>
                )}

                {/* Quick Actions */}
                {safeQuickActions.length > 0 && (
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                            <Plus className="w-5 h-5 mr-2 text-green-500" />
                            Quick Actions
                        </h3>
                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            {safeQuickActions.map((action, index) => (
                                <QuickActionCard key={index} action={action} />
                            ))}
                        </div>
                    </div>
                )}

                {/* Parish Overview Stats */}
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                        <BarChart3 className="w-5 h-5 mr-2 text-purple-500" />
                        Parish Overview
                    </h3>
                    
                    {/* Member & Family Stats */}
                    {(user.permissions.can_access_members || user.permissions.can_access_families) && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            {user.permissions.can_access_members && (
                                <>
                                    <StatCard
                                        title="Total Members"
                                        value={liveStats.total_members}
                                        icon={Users}
                                        color="royal-purple"
                                        trend={liveStats.member_growth_rate}
                                        link={route('members.index')}
                                    />
                                    <StatCard
                                        title="Active Members"
                                        value={liveStats.active_members}
                                        icon={Heart}
                                        color="peaceful-green"
                                        subtitle={`${liveStats.new_members_this_month || 0} new this month`}
                                        link={route('members.index')}
                                    />
                                </>
                            )}
                            
                            {user.permissions.can_access_families && (
                                <>
                                    <StatCard
                                        title="Parish Families"
                                        value={liveStats.total_families}
                                        icon={Home}
                                        color="divine-blue"
                                        link={route('families.index')}
                                    />
                                    <StatCard
                                        title="Active Families"
                                        value={liveStats.active_families}
                                        icon={Church}
                                        color="sacred-gold"
                                        subtitle={`${liveStats.new_families_this_month || 0} new this month`}
                                        link={route('families.index')}
                                    />
                                </>
                            )}
                        </div>
                    )}

                    {/* Financial Stats */}
                    {user.permissions.can_view_financial_reports && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <StatCard
                                title="Tithes This Month"
                                value={liveStats.total_tithes_this_month}
                                icon={DollarSign}
                                color="peaceful-green"
                                subtitle={`${liveStats.tithe_contributors_this_month || 0} contributors`}
                                link={route('tithes.index')}
                            />
                            <StatCard
                                title="Yearly Tithes"
                                value={liveStats.total_tithes_this_year}
                                icon={DollarSign}
                                color="sacred-gold"
                                link={route('reports.financial')}
                            />
                            <StatCard
                                title="Average Offering"
                                value={liveStats.average_tithe_amount}
                                icon={Heart}
                                color="holy-red"
                            />
                            <StatCard
                                title="Sacraments"
                                value={liveStats.sacraments_this_month}
                                icon={Star}
                                color="royal-purple"
                                subtitle={`${liveStats.sacraments_this_year || 0} this year`}
                                link={route('sacraments.index')}
                            />
                        </div>
                    )}

                    {/* Community Engagement */}
                    {user.permissions.can_access_community_groups && (
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <StatCard
                                title="Active Groups"
                                value={liveStats.active_community_groups}
                                icon={Users}
                                color="divine-blue"
                                link={route('community-groups.index')}
                            />
                            <StatCard
                                title="Group Members"
                                value={liveStats.total_group_members}
                                icon={Heart}
                                color="peaceful-green"
                            />
                            <StatCard
                                title="Participation Rate"
                                value={liveStats.group_participation_rate}
                                icon={TrendingUp}
                                color="sacred-gold"
                                subtitle="% in groups"
                            />
                        </div>
                    )}
                </div>

                {/* Recent Activities and Upcoming Events */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Activities */}
                    <div className="bg-white rounded-xl shadow-lg p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <Activity className="w-5 h-5 mr-2 text-purple-500" />
                            Recent Parish Activities
                        </h3>
                        <div className="space-y-4">
                            {safeRecentActivities.length > 0 ? (
                                safeRecentActivities.slice(0, 5).map((activity) => (
                                    <ActivityCard key={activity.id} activity={activity} />
                                ))
                            ) : (
                                <div className="text-center py-8">
                                    <Church className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p className="text-gray-500">No recent activities</p>
                                    <p className="text-sm text-gray-400">Parish activities will appear here</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Upcoming Events */}
                    <div className="bg-white rounded-xl shadow-lg p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <Calendar className="w-5 h-5 mr-2 text-blue-500" />
                            Upcoming Events
                        </h3>
                        <div className="space-y-4">
                            {safeUpcomingEvents.length > 0 ? (
                                safeUpcomingEvents.slice(0, 5).map((event) => (
                                    <div key={event.id} className="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-100 rounded-lg p-4">
                                        <div className="flex items-start space-x-3">
                                            <div className="p-2 bg-purple-100 rounded-lg">
                                                <Calendar className="w-4 h-4 text-purple-600" />
                                            </div>
                                            <div className="flex-1">
                                                <p className="font-semibold text-gray-900 text-sm">
                                                    {event.name}
                                                </p>
                                                <div className="flex items-center text-xs text-gray-600 mt-1">
                                                    <Clock className="w-3 h-3 mr-1" />
                                                    {event.date}
                                                </div>
                                                <div className="flex items-center text-xs text-gray-600">
                                                    <MapPin className="w-3 h-3 mr-1" />
                                                    {event.location}
                                                </div>
                                                <p className="text-xs text-gray-600 mt-2">
                                                    {event.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-center py-8">
                                    <Calendar className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p className="text-gray-500">No upcoming events</p>
                                    <p className="text-sm text-gray-400">Parish events will appear here</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Detailed Parish Breakdown */}
                {user.permissions.can_access_members && (
                    <div className="space-y-6">
                        <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                            <BarChart3 className="w-5 h-5 mr-2 text-indigo-500" />
                            Parish Breakdown
                        </h3>
                        
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Church Distribution */}
                            <div className="bg-white rounded-xl shadow-lg p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Church className="w-5 h-5 mr-2 text-purple-500" />
                                    By Local Church
                                </h4>
                                <div className="space-y-3">
                                    {liveStats.church_distribution && Object.keys(liveStats.church_distribution).length > 0 ? (
                                        Object.entries(liveStats.church_distribution).map(([church, count]) => (
                                            <div key={church} className="flex items-center justify-between">
                                                <span className="text-sm text-gray-600">{church || 'Unknown'}</span>
                                                <span className="font-semibold text-gray-900">{count || 0}</span>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500 text-sm">No church data available</p>
                                    )}
                                </div>
                            </div>

                            {/* Group Distribution */}
                            <div className="bg-white rounded-xl shadow-lg p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Users className="w-5 h-5 mr-2 text-blue-500" />
                                    By Church Group
                                </h4>
                                <div className="space-y-3">
                                    {liveStats.group_distribution && Object.keys(liveStats.group_distribution).length > 0 ? (
                                        Object.entries(liveStats.group_distribution).map(([group, count]) => (
                                            <div key={group} className="flex items-center justify-between">
                                                <span className="text-sm text-gray-600">{group || 'Unknown'}</span>
                                                <span className="font-semibold text-gray-900">{count || 0}</span>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500 text-sm">No group data available</p>
                                    )}
                                </div>
                            </div>

                            {/* Demographics */}
                            <div className="bg-white rounded-xl shadow-lg p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Heart className="w-5 h-5 mr-2 text-pink-500" />
                                    Demographics
                                </h4>
                                <div className="space-y-3">
                                    {/* Gender Distribution */}
                                    <div>
                                        <p className="text-sm font-medium text-gray-700 mb-2">Gender</p>
                                        {liveStats.gender_distribution && (
                                            <div className="space-y-1">
                                                <div className="flex justify-between">
                                                    <span className="text-xs text-gray-600">Male</span>
                                                    <span className="text-xs font-semibold">{liveStats.gender_distribution.male || 0}</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-xs text-gray-600">Female</span>
                                                    <span className="text-xs font-semibold">{liveStats.gender_distribution.female || 0}</span>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    
                                    {/* Age Groups */}
                                    <div>
                                        <p className="text-sm font-medium text-gray-700 mb-2">Age Groups</p>
                                        {liveStats.age_groups && (
                                            <div className="space-y-1">
                                                <div className="flex justify-between">
                                                    <span className="text-xs text-gray-600">Children (0-18)</span>
                                                    <span className="text-xs font-semibold">{liveStats.age_groups.children || 0}</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-xs text-gray-600">Youth (18-30)</span>
                                                    <span className="text-xs font-semibold">{liveStats.age_groups.youth || 0}</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-xs text-gray-600">Adults (30-60)</span>
                                                    <span className="text-xs font-semibold">{liveStats.age_groups.adults || 0}</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-xs text-gray-600">Seniors (60+)</span>
                                                    <span className="text-xs font-semibold">{liveStats.age_groups.seniors || 0}</span>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Membership Status */}
                                    <div>
                                        <p className="text-sm font-medium text-gray-700 mb-2">Status</p>
                                        {liveStats.status_distribution && Object.keys(liveStats.status_distribution).length > 0 && (
                                            <div className="space-y-1">
                                                {Object.entries(liveStats.status_distribution).map(([status, count]) => (
                                                    <div key={status} className="flex justify-between">
                                                        <span className="text-xs text-gray-600 capitalize">{status || 'Unknown'}</span>
                                                        <span className="text-xs font-semibold">{count || 0}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </SidebarLayout>
    );
}