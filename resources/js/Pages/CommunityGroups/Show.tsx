import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    ArrowLeft,
    Users, 
    Plus, 
    Search, 
    Edit,
    Trash2,
    Phone,
    Mail,
    MapPin,
    UserPlus,
    Eye
} from 'lucide-react';

interface Member {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    email?: string;
    phone?: string;
    date_of_birth?: string;
    gender: string;
    membership_status: string;
    residence?: string;
    created_at: string;
}

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
}

interface PaginatedMembers {
    data: Member[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
}

interface Props {
    group: ChurchGroup;
    members: PaginatedMembers;
    filters: {
        search?: string;
    };
}

export default function Show({ group, members, filters }: Props) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('community-groups.show', encodeURIComponent(group.name)), {
            search: searchTerm,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const getStatusBadge = (status: string) => {
        const statusStyles = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-red-100 text-red-800',
            transferred: 'bg-yellow-100 text-yellow-800',
            deceased: 'bg-gray-100 text-gray-800',
        };

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                statusStyles[status as keyof typeof statusStyles] || 'bg-gray-100 text-gray-800'
            }`}>
                {status}
            </span>
        );
    };

    const calculateAge = (dateOfBirth: string) => {
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link 
                            href={route('community-groups.index')}
                            className="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5 mr-1" />
                            Back to Groups
                        </Link>
                        <div className="flex items-center space-x-3">
                            <div className="text-3xl">{group.icon}</div>
                            <div>
                                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                    {group.name} Group
                                </h2>
                                <p className="text-sm text-gray-600 mt-1">
                                    {group.description}
                                </p>
                            </div>
                        </div>
                    </div>
                    <Link 
                        href={route('members.create', { church_group: group.name })}
                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <UserPlus className="w-4 h-4 mr-2" />
                        Add Member to {group.name}
                    </Link>
                </div>
            }
        >
            <Head title={`${group.name} Group Members`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Group Statistics */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-500">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Total Members</p>
                                        <p className="text-2xl font-bold text-gray-900">{group.members_count}</p>
                                    </div>
                                    <Users className="h-8 w-8 text-blue-400" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Active Members</p>
                                        <p className="text-2xl font-bold text-green-600">{group.active_members}</p>
                                    </div>
                                    <div className="text-2xl">✅</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-red-500">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Inactive Members</p>
                                        <p className="text-2xl font-bold text-red-600">{group.inactive_members}</p>
                                    </div>
                                    <div className="text-2xl">❌</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-purple-500">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Latest Member</p>
                                        <p className="text-sm font-bold text-purple-600">
                                            {group.latest_member_joined 
                                                ? new Date(group.latest_member_joined).toLocaleDateString()
                                                : 'No members yet'
                                            }
                                        </p>
                                    </div>
                                    <div className="text-2xl">📅</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search Bar */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="flex gap-4">
                                <div className="flex-1 relative">
                                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder={`Search ${group.name} members...`}
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    className="inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Search
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Members List */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium text-gray-900">
                                    {group.name} Members ({members.total})
                                </h3>
                                <p className="text-sm text-gray-600">
                                    Showing {members.from || 0} to {members.to || 0} of {members.total} members
                                </p>
                            </div>

                            {members.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <div className="text-6xl mb-4">{group.icon}</div>
                                    <p className="text-gray-500 font-medium mb-2">No members found in {group.name}</p>
                                    <p className="text-sm text-gray-400 mb-6">
                                        {searchTerm ? 'Try adjusting your search terms' : 'Be the first to add a member to this group'}
                                    </p>
                                    <Link 
                                        href={route('members.create', { church_group: group.name })}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <Plus className="w-4 h-4 mr-2" />
                                        Add First Member
                                    </Link>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {members.data.map((member) => (
                                        <div key={member.id} className="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex-1">
                                                    <h4 className="font-semibold text-gray-900">
                                                        {member.first_name} {member.middle_name} {member.last_name}
                                                    </h4>
                                                    <div className="flex items-center gap-2 mt-1">
                                                        {getStatusBadge(member.membership_status)}
                                                        <span className="text-xs text-gray-500">
                                                            {member.gender}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="space-y-2 text-sm text-gray-600 mb-4">
                                                {member.date_of_birth && (
                                                    <div className="flex items-center">
                                                        <span className="text-xs">🎂</span>
                                                        <span className="ml-2">
                                                            Age {calculateAge(member.date_of_birth)}
                                                        </span>
                                                    </div>
                                                )}
                                                
                                                {member.phone && (
                                                    <div className="flex items-center">
                                                        <Phone className="w-3 h-3" />
                                                        <span className="ml-2">{member.phone}</span>
                                                    </div>
                                                )}
                                                
                                                {member.email && (
                                                    <div className="flex items-center">
                                                        <Mail className="w-3 h-3" />
                                                        <span className="ml-2 truncate">{member.email}</span>
                                                    </div>
                                                )}
                                                
                                                {member.residence && (
                                                    <div className="flex items-center">
                                                        <MapPin className="w-3 h-3" />
                                                        <span className="ml-2 truncate">{member.residence}</span>
                                                    </div>
                                                )}
                                                
                                                <div className="text-xs text-gray-400">
                                                    Joined: {new Date(member.created_at).toLocaleDateString()}
                                                </div>
                                            </div>

                                            <div className="flex gap-2">
                                                <Link 
                                                    href={route('members.show', member.id)}
                                                    className="flex-1 inline-flex justify-center items-center px-2 py-1 text-xs border border-gray-300 rounded text-gray-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                                >
                                                    <Eye className="w-3 h-3 mr-1" />
                                                    View
                                                </Link>
                                                <Link 
                                                    href={route('members.edit', member.id)}
                                                    className="flex-1 inline-flex justify-center items-center px-2 py-1 text-xs border border-gray-300 rounded text-gray-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                                >
                                                    <Edit className="w-3 h-3 mr-1" />
                                                    Edit
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Pagination */}
                            {members.last_page > 1 && (
                                <div className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-6">
                                    <div className="flex flex-1 justify-between sm:hidden">
                                        <button
                                            onClick={() => {
                                                const url = members.links[0]?.url;
                                                if (url) router.get(url);
                                            }}
                                            disabled={!members.links[0]?.url}
                                            className="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Previous
                                        </button>
                                        <button
                                            onClick={() => {
                                                const url = members.links[members.links.length - 1]?.url;
                                                if (url) router.get(url);
                                            }}
                                            disabled={!members.links[members.links.length - 1]?.url}
                                            className="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Next
                                        </button>
                                    </div>
                                    <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing <span className="font-medium">{members.from}</span> to{' '}
                                                <span className="font-medium">{members.to}</span> of{' '}
                                                <span className="font-medium">{members.total}</span> results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                                {members.links.map((link, index) => (
                                                    <button
                                                        key={index}
                                                        onClick={() => link.url && router.get(link.url)}
                                                        disabled={!link.url}
                                                        className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold ${
                                                            link.active
                                                                ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'
                                                        } ${
                                                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                                        } ${
                                                            index === 0 ? 'rounded-l-md' : ''
                                                        } ${
                                                            index === members.links.length - 1 ? 'rounded-r-md' : ''
                                                        }`}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
                                            </nav>
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