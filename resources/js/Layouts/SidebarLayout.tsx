import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { 
    Home, 
    Users, 
    UserPlus,
    Heart,
    Calendar,
    DollarSign,
    Star,
    BookOpen,
    Settings,
    LogOut,
    Menu,
    X,
    ChevronDown,
    ChevronRight,
    FileText,
    Shield,
    Church,
    Bell,
    Search,
    MessageSquare,
    Award,
    TrendingUp,
    Gift
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

interface SidebarLayoutProps {
    children: React.ReactNode;
    header?: React.ReactNode;
}

interface MenuItem {
    name: string;
    href: string;
    icon: React.ComponentType<any>;
    permission?: keyof User['permissions'];
    badge?: string;
    children?: MenuItem[];
}

const SidebarLayout: React.FC<SidebarLayoutProps> = ({ children, header }) => {
    const { props } = usePage();
    const user = props.auth?.user as User;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [expandedMenus, setExpandedMenus] = useState<string[]>(['parish-management']);
    const [notifications] = useState(3); // Mock notification count
    const [searchQuery, setSearchQuery] = useState('');

    // Close sidebar on route change (mobile)
    useEffect(() => {
        setSidebarOpen(false);
    }, [props.url]);

    const toggleMenu = (menuName: string) => {
        setExpandedMenus(prev => 
            prev.includes(menuName) 
                ? prev.filter(name => name !== menuName)
                : [...prev, menuName]
        );
    };

    const navigation: MenuItem[] = [
        {
            name: 'Dashboard',
            href: '/dashboard',
            icon: Home,
        },
        {
            name: 'Parish Management',
            href: '#',
            icon: Church,
            children: [
                {
                    name: 'Members',
                    href: '/members',
                    icon: Users,
                    permission: 'can_access_members',
                },
                {
                    name: 'Families',
                    href: '/families',
                    icon: Heart,
                    permission: 'can_access_families',
                },
                {
                    name: 'Community Groups',
                    href: '/community-groups',
                    icon: Users,
                    permission: 'can_access_community_groups',
                },
            ]
        },
        {
            name: 'Sacraments',
            href: '/sacraments',
            icon: Star,
            permission: 'can_access_sacraments',
        },
        {
            name: 'Financial',
            href: '#',
            icon: DollarSign,
            permission: 'can_access_tithes',
            children: [
                {
                    name: 'Tithes & Offerings',
                    href: '/tithes',
                    icon: DollarSign,
                    permission: 'can_access_tithes',
                },
                {
                    name: 'Financial Reports',
                    href: '/reports/financial',
                    icon: FileText,
                    permission: 'can_view_financial_reports',
                },
            ]
        },
        {
            name: 'Activities',
            href: '/activities',
            icon: Calendar,
        },
        {
            name: 'Reports',
            href: '/reports',
            icon: FileText,
            permission: 'can_access_reports',
        },
    ];

    const adminNavigation: MenuItem[] = [
        {
            name: 'User Management',
            href: '/admin/users',
            icon: Shield,
            permission: 'can_manage_users',
        },
        {
            name: 'System Settings',
            href: '/admin/settings',
            icon: Settings,
            permission: 'can_manage_users',
        },
    ];

    const hasPermission = (permission?: keyof User['permissions']): boolean => {
        if (!permission || !user?.permissions) return true;
        return user.permissions[permission] === true;
    };

    const isCurrentRoute = (href: string): boolean => {
        if (href === '#') return false;
        const currentPath = window.location.pathname;
        if (href === '/dashboard') return currentPath === '/dashboard';
        return currentPath.startsWith(href) && href !== '/';
    };

    const NavigationItem: React.FC<{
        item: MenuItem;
        hasPermission: (permission?: keyof User['permissions']) => boolean;
        isCurrentRoute: (href: string) => boolean;
        expandedMenus: string[];
        toggleMenu: (menuName: string) => void;
    }> = ({ item, hasPermission, isCurrentRoute, expandedMenus, toggleMenu }) => {
        if (!hasPermission(item.permission)) return null;

        const hasChildren = item.children && item.children.length > 0;
        const menuKey = item.name.toLowerCase().replace(/\s+/g, '-');
        const isExpanded = expandedMenus.includes(menuKey);
        const isCurrent = isCurrentRoute(item.href);
        const hasActiveChild = hasChildren && item.children?.some(child => 
            hasPermission(child.permission) && isCurrentRoute(child.href)
        );

        if (hasChildren) {
            return (
                <div>
                    <button
                        onClick={() => toggleMenu(menuKey)}
                        className={`
                            w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg transition-all duration-200
                            ${hasActiveChild || isCurrent
                                ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-md' 
                                : 'text-gray-700 hover:bg-purple-50 hover:text-purple-700 hover:shadow-sm'
                            }
                        `}
                    >
                        <div className="flex items-center">
                            <item.icon className="w-5 h-5 mr-3" />
                            {item.name}
                        </div>
                        <ChevronDown className={`w-4 h-4 transform transition-transform duration-200 ${isExpanded ? 'rotate-180' : ''}`} />
                    </button>
                    
                    <div className={`ml-6 mt-1 space-y-1 overflow-hidden transition-all duration-300 ${
                        isExpanded ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                    }`}>
                        {item.children?.map((child) => (
                            hasPermission(child.permission) && (
                                <Link
                                    key={child.name}
                                    href={child.href}
                                    className={`
                                        flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200
                                        ${isCurrentRoute(child.href)
                                            ? 'bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-md transform scale-105'
                                            : 'text-gray-600 hover:bg-purple-50 hover:text-purple-600 hover:translate-x-1'
                                        }
                                    `}
                                >
                                    <child.icon className="w-4 h-4 mr-3" />
                                    {child.name}
                                    {child.badge && (
                                        <span className="ml-auto px-2 py-1 text-xs bg-red-100 text-red-600 rounded-full">
                                            {child.badge}
                                        </span>
                                    )}
                                </Link>
                            )
                        ))}
                    </div>
                </div>
            );
        }

        return (
            <Link
                href={item.href}
                className={`
                    flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200
                    ${isCurrent
                        ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-md transform scale-105'
                        : 'text-gray-700 hover:bg-purple-50 hover:text-purple-700 hover:shadow-sm hover:translate-x-1'
                    }
                `}
            >
                <item.icon className="w-5 h-5 mr-3" />
                {item.name}
                {item.badge && (
                    <span className="ml-auto px-2 py-1 text-xs bg-red-100 text-red-600 rounded-full">
                        {item.badge}
                    </span>
                )}
            </Link>
        );
    };

    const SidebarContent = () => (
        <div className="flex flex-col h-full bg-white">
            {/* Enhanced Logo Section */}
            <div className="flex items-center justify-center h-16 px-4 bg-gradient-to-r from-purple-800 via-purple-700 to-blue-800 relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
                <Church className="w-8 h-8 text-yellow-300 mr-2 relative z-10" />
                <span className="text-xl font-bold text-white relative z-10">Parish MS</span>
            </div>

            {/* Enhanced User Info */}
            <div className="p-4 bg-gradient-to-r from-purple-50 to-blue-50 border-b border-purple-200">
                <div className="flex items-center">
                    <div className="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold shadow-lg">
                        {user?.name?.charAt(0).toUpperCase()}
                    </div>
                    <div className="ml-3">
                        <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                        <p className="text-xs text-purple-600 font-medium">{user?.roles?.[0] || 'User'}</p>
                    </div>
                </div>
            </div>

            {/* Enhanced Navigation */}
            <nav className="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
                {navigation.map((item) => (
                    <NavigationItem
                        key={item.name}
                        item={item}
                        hasPermission={hasPermission}
                        isCurrentRoute={isCurrentRoute}
                        expandedMenus={expandedMenus}
                        toggleMenu={toggleMenu}
                    />
                ))}

                {/* Enhanced Admin Section */}
                {user?.permissions?.can_manage_users && (
                    <>
                        <div className="pt-6 mt-4 border-t border-gray-200">
                            <p className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                Administration
                            </p>
                        </div>
                        {adminNavigation.map((item) => (
                            <NavigationItem
                                key={item.name}
                                item={item}
                                hasPermission={hasPermission}
                                isCurrentRoute={isCurrentRoute}
                                expandedMenus={expandedMenus}
                                toggleMenu={toggleMenu}
                            />
                        ))}
                    </>
                )}
            </nav>

            {/* Enhanced Logout */}
            <div className="p-4 border-t border-gray-200 bg-gray-50">
                <Link
                    href="/logout"
                    method="post"
                    className="flex items-center w-full px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition-all duration-200 hover:shadow-md"
                >
                    <LogOut className="w-5 h-5 mr-3" />
                    Sign Out
                </Link>
            </div>
        </div>
    );

    return (
        <div className="h-screen flex bg-gray-50">
            {/* Enhanced Mobile sidebar */}
            <div className={`fixed inset-0 z-50 lg:hidden transition-opacity duration-300 ${
                sidebarOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
            }`}>
                <div 
                    className="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" 
                    onClick={() => setSidebarOpen(false)} 
                />
                <div className={`fixed inset-y-0 left-0 flex flex-col w-64 bg-white shadow-2xl transform transition-transform duration-300 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}>
                    <div className="absolute top-0 right-0 -mr-12 pt-2">
                        <button
                            type="button"
                            className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white hover:bg-gray-600 transition-colors"
                            onClick={() => setSidebarOpen(false)}
                        >
                            <X className="h-6 w-6 text-white" />
                        </button>
                    </div>
                    <SidebarContent />
                </div>
            </div>

            {/* Enhanced Desktop sidebar */}
            <div className="hidden lg:flex lg:flex-shrink-0">
                <div className="flex flex-col w-64 bg-white shadow-xl border-r border-gray-200">
                    <SidebarContent />
                </div>
            </div>

            {/* Enhanced Main content */}
            <div className="flex-1 overflow-hidden flex flex-col">
                {/* Enhanced Mobile header */}
                <div className="lg:hidden bg-white shadow-sm border-b border-gray-200">
                    <div className="flex items-center justify-between px-4 py-3">
                        <button
                            type="button"
                            className="text-gray-500 hover:text-gray-700 hover:bg-gray-100 p-2 rounded-lg transition-colors"
                            onClick={() => setSidebarOpen(true)}
                        >
                            <Menu className="h-6 w-6" />
                        </button>
                        <div className="flex items-center">
                            <Church className="w-6 h-6 text-purple-600 mr-2" />
                            <span className="text-lg font-bold text-gray-900">Parish MS</span>
                        </div>
                        <div className="w-10"></div>
                    </div>
                </div>

                {/* Enhanced Page header */}
                {header && (
                    <div className="bg-white shadow-sm border-b border-gray-200">
                        <div className="px-4 sm:px-6 lg:px-8 py-4">
                            {header}
                        </div>
                    </div>
                )}

                {/* Enhanced Main content */}
                <main className="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100">
                    {children}
                </main>
            </div>
        </div>
    );
};

export default SidebarLayout;