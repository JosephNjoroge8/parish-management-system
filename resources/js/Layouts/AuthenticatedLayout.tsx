import { useState, PropsWithChildren, ReactNode } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { 
    Home, 
    Users, 
    UserPlus,
    DollarSign, 
    Star, 
    BarChart3, 
    Settings,
    Users2,
    Menu,
    Shield,
    X
} from 'lucide-react';

interface AuthenticatedLayoutProps {
    header?: ReactNode;
}

export default function AuthenticatedLayout({ 
    header, 
    children 
}: PropsWithChildren<AuthenticatedLayoutProps>) {
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const { auth } = usePage().props as any;
    const user = auth.user;

    const navigationItems = [
        {
            name: 'Dashboard',
            short: 'Home',
            href: route('dashboard'),
            icon: Home,
            active: route().current('dashboard'),
            permission: true,
        },
        {
            name: 'Members',
            short: 'Members',
            href: route('members.index'),
            icon: Users,
            active: route().current('members.*'),
            permission: user.permissions?.can_access_members,
        },
        {
            name: 'Families',
            short: 'Families',
            href: route('families.index'),
            icon: Users2,
            active: route().current('families.*'),
            permission: user.permissions?.can_access_families,
        },
        {
            name: 'Sacraments',
            short: 'Sacraments',
            href: route('sacraments.index'),
            icon: Star,
            active: route().current('sacraments.*'),
            permission: user.permissions?.can_access_sacraments,
        },
        {
            name: 'Tithes & Offerings',
            short: 'Tithes',
            href: route('tithes.index'),
            icon: DollarSign,
            active: route().current('tithes.*'),
            permission: user.permissions?.can_access_tithes,
        },
        {
            name: 'Community Groups',
            short: 'Groups',
            href: route('community-groups.index'),
            icon: Users2,
            active: route().current('community-groups.*'),
            permission: user.permissions?.can_access_community_groups,
        },
        {
            name: 'Reports',
            short: 'Reports',
            href: route('reports.index'),
            icon: BarChart3,
            active: route().current('reports.*'),
            permission: user.permissions?.can_access_reports,
        },
    ];

    const adminItems = [
        {
            name: 'User Management',
            short: 'Users',
            href: route('admin.users.index'),
            icon: UserPlus,
            active: route().current('admin.users.*'),
            permission: user.permissions?.can_manage_users,
        },
        {
            name: 'Role & Permission Management',
            short: 'Roles',
            href: route('admin.roles.index'),
            icon: Shield,
            active: route().current('admin.roles.*'),
            permission: user.permissions?.can_manage_roles || user.permissions?.can_manage_users,
        },
    ];

    return (
        <div className="min-h-screen bg-gray-100 flex flex-col">
            {/* Responsive Navigation */}
            <nav className="bg-white border-b border-gray-100">
                <div className="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
                    <div className="flex justify-between h-14 md:h-16">
                        {/* Logo & Desktop Nav */}
                        <div className="flex items-center">
                            <Link href="/" className="shrink-0 flex items-center">
                                 <img src="/logo.jpg" alt="Parish Logo" className="h-8 w-auto" />
                            </Link>
                            {/* Desktop Navigation */}
                            <div className="hidden md:flex md:space-x-2 lg:space-x-4 ml-2 lg:ml-4">
                                {navigationItems.map((item) => (
                                    item.permission && (
                                        <NavLink
                                            key={item.name}
                                            href={item.href}
                                            active={item.active}
                                            className="inline-flex items-center space-x-1 md:space-x-2 px-2 py-1 md:px-3 md:py-2 text-xs md:text-sm font-medium transition-colors hover:bg-gray-100 rounded"
                                        >
                                            <item.icon className="w-4 h-4 md:w-5 md:h-5" />
                                            <span className="truncate hidden xs:inline">{item.name}</span>
                                            <span className="truncate inline xs:hidden">{item.short}</span>
                                        </NavLink>
                                    )
                                ))}
                                {/* Admin Section */}
                                {adminItems.some(item => item.permission) && (
                                    <div className="hidden md:flex items-center ml-2">
                                        <Dropdown>
                                            <Dropdown.Trigger>
                                                <button
                                                    type="button"
                                                    className="inline-flex items-center px-2 py-1 md:px-3 md:py-2 text-xs md:text-sm font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none"
                                                >
                                                    <Settings className="w-4 h-4 mr-1" />
                                                    <span className="hidden xs:inline">Admin</span>
                                                </button>
                                            </Dropdown.Trigger>
                                            <Dropdown.Content>
                                                {adminItems.map((item) => (
                                                    item.permission && (
                                                        <Dropdown.Link 
                                                            key={item.name}
                                                            href={item.href}
                                                            className="flex items-center space-x-2"
                                                        >
                                                            <item.icon className="w-4 h-4" />
                                                            <span>{item.name}</span>
                                                        </Dropdown.Link>
                                                    )
                                                ))}
                                            </Dropdown.Content>
                                        </Dropdown>
                                    </div>
                                )}
                            </div>
                        </div>
                        {/* User Dropdown */}
                        <div className="hidden md:flex items-center">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="inline-flex items-center px-2 py-1 md:px-3 md:py-2 text-xs md:text-sm font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none"
                                    >
                                        <div className="w-7 h-7 md:w-8 md:h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs md:text-sm font-medium mr-2">
                                            {user.name.charAt(0).toUpperCase()}
                                        </div>
                                        <span className="truncate max-w-[80px]">{user.name}</span>
                                        <svg className="ml-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                        </svg>
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        Profile
                                    </Dropdown.Link>
                                    <Dropdown.Link 
                                        href={route('logout')} 
                                        method="post" 
                                        as="button"
                                    >
                                        Log Out
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                        {/* Mobile hamburger */}
                        <div className="flex items-center md:hidden">
                            <button
                                onClick={() => setShowingNavigationDropdown(!showingNavigationDropdown)}
                                className="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition"
                                aria-label="Open main menu"
                            >
                                {showingNavigationDropdown ? (
                                    <X className="h-6 w-6" />
                                ) : (
                                    <Menu className="h-6 w-6" />
                                )}
                            </button>
                        </div>
                    </div>
                </div>
                {/* Mobile Navigation Menu */}
                {showingNavigationDropdown && (
                    <div className="md:hidden bg-white border-t border-gray-200 shadow-lg">
                        <div className="pt-2 pb-3 space-y-1 max-h-96 overflow-y-auto">
                            {navigationItems.map((item) => (
                                item.permission && (
                                    <ResponsiveNavLink
                                        key={item.name}
                                        href={item.href}
                                        active={item.active}
                                        className="flex items-center space-x-2 px-4 py-3 text-base font-medium transition-colors hover:bg-gray-100 rounded"
                                    >
                                        <item.icon className="w-5 h-5 flex-shrink-0" />
                                        <span className="truncate">{item.short}</span>
                                    </ResponsiveNavLink>
                                )
                            ))}
                            {/* Admin Section */}
                            {adminItems.some(item => item.permission) && (
                                <div className="border-t border-gray-200 mt-2 pt-2">
                                    {adminItems.map((item) => (
                                        item.permission && (
                                            <ResponsiveNavLink
                                                key={item.name}
                                                href={item.href}
                                                active={item.active}
                                                className="flex items-center space-x-2 px-4 py-3 text-base font-medium transition-colors hover:bg-gray-100 rounded"
                                            >
                                                <item.icon className="w-5 h-5 flex-shrink-0" />
                                                <span className="truncate">{item.short}</span>
                                            </ResponsiveNavLink>
                                        )
                                    ))}
                                </div>
                            )}
                        </div>
                        {/* User quick menu */}
                        <div className="border-t border-gray-200 px-4 py-3 flex items-center space-x-3">
                            <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                {user.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="font-medium text-gray-800 truncate">{user.name}</div>
                                <div className="text-xs text-gray-500 truncate">{user.roles?.join(', ') || 'Member'}</div>
                            </div>
                            <Link href={route('profile.edit')} className="text-xs text-purple-600 hover:underline">Profile</Link>
                            <form method="POST" action={route('logout')}>
                                <button type="submit" className="text-xs text-red-600 hover:underline">Log Out</button>
                            </form>
                        </div>
                    </div>
                )}
            </nav>

            {/* Responsive Page Header */}
            {header && (
                <header className="bg-white shadow">
                    <div className="max-w-7xl mx-auto py-2 md:py-4 px-2 sm:px-4 lg:px-8">
                        <div className="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-2">
                            <div className="text-base xs:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900 truncate">
                                {header}
                            </div>
                        </div>
                    </div>
                </header>
            )}

            {/* Responsive Page Content */}
            <main className="flex-1 w-full max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-2 md:py-4">
                {children}
            </main>
        </div>
    );
}
