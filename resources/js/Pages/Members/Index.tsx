// resources/js/Pages/Members/Index.jsx
import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Search, Plus, Edit, Eye, Trash2, Filter, Users } from 'lucide-react';

// Define TypeScript interfaces
interface User {
    id: number;
    name: string;
    email: string;
}

interface Family {
    id: number;
    family_name: string;
}

interface Member {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    family?: Family;
    status: 'active' | 'inactive' | 'deceased' | 'transferred';
    phone?: string;
    age?: number;
    gender: 'male' | 'female';
    member_type: 'adult' | 'youth' | 'child';
    date_of_birth: string;
}

interface PaginatedMembers {
    data: Member[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    from: number;
    to: number;
    total: number;
}

interface Filters {
    search?: string;
    family?: string;
    gender?: string;
    status?: string;
}

interface MembersIndexProps {
    auth: {
        user: User;
    };
    members: PaginatedMembers;
    families: Family[];
    filters: Filters;
}

const MemberCard = ({ 
    member, 
    onEdit, 
    onView, 
    onDelete 
}: {
    member: Member;
    onEdit: (member: Member) => void;
    onView: (member: Member) => void;
    onDelete: (member: Member) => void;
}) => (
    <div className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
        <div className="flex items-center justify-between mb-4">
            <div className="flex items-center space-x-3">
                <div className="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <span className="text-white font-semibold">
                        {member.first_name.charAt(0)}{member.last_name.charAt(0)}
                    </span>
                </div>
                <div>
                    <h3 className="font-semibold text-gray-900">
                        {member.first_name} {member.middle_name} {member.last_name}
                    </h3>
                    <p className="text-sm text-gray-600">{member.family?.family_name || 'No Family'}</p>
                </div>
            </div>
            <span className={`px-2 py-1 rounded-full text-xs ${
                member.status === 'active'
                    ? 'bg-green-100 text-green-800' 
                    : member.status === 'inactive'
                    ? 'bg-yellow-100 text-yellow-800'
                    : member.status === 'deceased'
                    ? 'bg-gray-100 text-gray-800'
                    : 'bg-blue-100 text-blue-800'
            }`}>
                {member.status.charAt(0).toUpperCase() + member.status.slice(1)}
            </span>
        </div>
        
        <div className="space-y-2 mb-4">
            <p className="text-sm text-gray-600">
                <span className="font-medium">Phone:</span> {member.phone || 'N/A'}
            </p>
            <p className="text-sm text-gray-600">
                <span className="font-medium">Age:</span> {member.age || 'N/A'}
            </p>
            <p className="text-sm text-gray-600">
                <span className="font-medium">Gender:</span> {member.gender.charAt(0).toUpperCase() + member.gender.slice(1)}
            </p>
            <p className="text-sm text-gray-600">
                <span className="font-medium">Type:</span> {member.member_type.charAt(0).toUpperCase() + member.member_type.slice(1)}
            </p>
        </div>
        
        <div className="flex space-x-2">
            <button
                onClick={() => onView(member)}
                className="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm flex items-center justify-center space-x-1 transition-colors"
            >
                <Eye className="w-4 h-4" />
                <span>View</span>
            </button>
            <button
                onClick={() => onEdit(member)}
                className="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg text-sm flex items-center justify-center space-x-1 transition-colors"
            >
                <Edit className="w-4 h-4" />
                <span>Edit</span>
            </button>
            <button
                onClick={() => onDelete(member)}
                className="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm transition-colors"
            >
                <Trash2 className="w-4 h-4" />
            </button>
        </div>
    </div>
);

export default function MembersIndex({ auth, members, families, filters }: MembersIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedFamily, setSelectedFamily] = useState(filters.family || '');
    const [selectedGender, setSelectedGender] = useState(filters.gender || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleSearch = () => {
        router.get(route('members.index'), {
            search: searchTerm,
            family: selectedFamily,
            gender: selectedGender,
            status: selectedStatus,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleView = (member: Member) => {
        router.visit(route('members.show', member.id));
    };

    const handleEdit = (member: Member) => {
        router.visit(route('members.edit', member.id));
    };

    const handleDelete = (member: Member) => {
        if (confirm(`Are you sure you want to delete ${member.first_name} ${member.last_name}?`)) {
            router.delete(route('members.destroy', member.id), {
                onSuccess: () => {
                    // Optionally show success message
                }
            });
        }
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedFamily('');
        setSelectedGender('');
        setSelectedStatus('');
        router.get(route('members.index'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Parish Members ({members.total})
                    </h2>
                    <Link
                        href={route('members.create')}
                        className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Add Member</span>
                    </Link>
                </div>
            }
        >
            <Head title="Members" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <input
                                    type="text"
                                    placeholder="Search members..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            
                            <select
                                value={selectedFamily}
                                onChange={(e) => setSelectedFamily(e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Families</option>
                                {families.map(family => (
                                    <option key={family.id} value={family.id}>
                                        {family.family_name}
                                    </option>
                                ))}
                            </select>
                            
                            <select
                                value={selectedGender}
                                onChange={(e) => setSelectedGender(e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Genders</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>

                            <select
                                value={selectedStatus}
                                onChange={(e) => setSelectedStatus(e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="deceased">Deceased</option>
                                <option value="transferred">Transferred</option>
                            </select>
                            
                            <div className="flex space-x-2">
                                <button
                                    onClick={handleSearch}
                                    className="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2 transition-colors"
                                >
                                    <Filter className="w-4 h-4" />
                                    <span>Filter</span>
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

                    {/* Members Grid */}
                    {members.data.length > 0 ? (
                        <>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {members.data.map(member => (
                                    <MemberCard
                                        key={member.id}
                                        member={member}
                                        onView={handleView}
                                        onEdit={handleEdit}
                                        onDelete={handleDelete}
                                    />
                                ))}
                            </div>

                            {/* Pagination */}
                            {members.links && (
                                <div className="mt-6 bg-white rounded-lg shadow-md p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="text-sm text-gray-700">
                                            Showing {members.from} to {members.to} of {members.total} results
                                        </div>
                                        <div className="flex space-x-2">
                                            {members.links.map((link, index) => (
                                                <button
                                                    key={index}
                                                    onClick={() => link.url && router.visit(link.url)}
                                                    disabled={!link.url}
                                                    className={`px-3 py-2 rounded-lg text-sm ${
                                                        link.active
                                                            ? 'bg-blue-500 text-white'
                                                            : link.url
                                                            ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                            : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                                    } transition-colors`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="bg-white rounded-lg shadow-md p-12 text-center">
                            <Users className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No members found</h3>
                            <p className="text-gray-600 mb-6">
                                {Object.values(filters).some(filter => filter) ? 
                                    'Try adjusting your search criteria or clear the filters.' :
                                    'Get started by adding your first parish member.'
                                }
                            </p>
                            <div className="flex justify-center space-x-4">
                                {Object.values(filters).some(filter => filter) && (
                                    <button
                                        onClick={clearFilters}
                                        className="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg inline-flex items-center space-x-2 transition-colors"
                                    >
                                        <span>Clear Filters</span>
                                    </button>
                                )}
                                <Link
                                    href={route('members.create')}
                                    className="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg inline-flex items-center space-x-2 transition-colors"
                                >
                                    <Plus className="w-4 h-4" />
                                    <span>Add Member</span>
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}