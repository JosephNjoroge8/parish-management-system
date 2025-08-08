import React, { useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    ArrowLeft, 
    Edit, 
    Phone, 
    Mail, 
    MapPin, 
    Calendar, 
    Users, 
    Heart,
    Church,
    Cross,
    BookOpen,
    Star,
    User,
    Shield,
    GraduationCap,
    Briefcase,
    Clock,
    UserCheck,
    Contact,
    RefreshCw
} from 'lucide-react';

// Define proper interfaces based on your database schema
interface Family {
    id: number;
    family_name: string;
    family_code?: string;
    parish_section?: string;
    address?: string;
    phone?: string;
    email?: string;
    deanery?: string;
    parish?: string;
    head_of_family_id?: number;
    created_by?: number;
    created_at?: string;
    updated_at?: string;
}

interface Member {
    id: number;
    local_church?: string;
    church_group?: string;
    first_name: string;
    middle_name?: string;
    last_name: string;
    date_of_birth?: string;
    gender?: string;
    phone?: string;
    email?: string;
    id_number?: string;
    sponsor?: string;
    occupation?: 'employed' | 'self_employed' | 'not_employed';
    education_level?: string;
    residence?: string;
    family_id?: number;
    parent?: string;
    minister?: string;
    tribe?: string;
    clan?: string;
    baptism_date?: string;
    confirmation_date?: string;
    matrimony_status?: 'single' | 'married' | 'divorced' | 'widowed';
    emergency_contact?: string;
    emergency_phone?: string;
    notes?: string;
    membership_date?: string;
    membership_status?: 'active' | 'inactive' | 'transferred' | 'deceased';
    created_at?: string;
    updated_at?: string;
    family?: Family;
}

interface PageProps {
    auth?: any;
    flash?: any;
    errors?: any;
}

interface MemberShowProps extends PageProps {
    member: Member;
}

export default function ShowMember({ member, auth, flash }: MemberShowProps) {
    // Status change handler
    const handleStatusChange = useCallback((newStatus: string) => {
        if (confirm(`Are you sure you want to change the member status to ${newStatus}?`)) {
            router.post(route('quick.member-status-toggle'), {
                member_id: member.id,
                status: newStatus
            }, {
                onSuccess: () => {
                    // The page will auto-refresh with updated data
                },
                onError: (errors) => {
                    console.error('Status update failed:', errors);
                    alert('Failed to update member status. Please try again.');
                }
            });
        }
    }, [member.id]);

    // Calculate age from date of birth with proper error handling
    const calculateAge = (dateOfBirth?: string): number | null => {
        if (!dateOfBirth) return null;
        
        try {
            const today = new Date();
            const birthDate = new Date(dateOfBirth);
            
            // Check if the date is valid
            if (isNaN(birthDate.getTime())) return null;
            
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age >= 0 ? age : null;
        } catch (error) {
            console.warn('Error calculating age:', error);
            return null;
        }
    };

    // Build full name safely
    const buildFullName = (firstName: string, lastName: string, middleName?: string): string => {
        const parts = [firstName];
        if (middleName?.trim()) {
            parts.push(middleName.trim());
        }
        parts.push(lastName);
        return parts.join(' ');
    };

    const age = calculateAge(member.date_of_birth);
    const fullName = buildFullName(member.first_name || 'Unknown', member.last_name || 'Member', member.middle_name);

    // Status color mapping with default fallback
    const getStatusColor = (status?: string): string => {
        const statusColors: Record<string, string> = {
            'active': 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'inactive': 'bg-amber-50 text-amber-700 border-amber-200',
            'transferred': 'bg-blue-50 text-blue-700 border-blue-200',
            'deceased': 'bg-gray-50 text-gray-700 border-gray-200'
        };
        
        return statusColors[status || 'active'] || 'bg-gray-50 text-gray-700 border-gray-200';
    };

    // Occupation label mapping
    const getOccupationLabel = (occupation?: string): string => {
        const occupationLabels: Record<string, string> = {
            'employed': 'Employed',
            'self_employed': 'Self Employed',
            'not_employed': 'Not Employed'
        };
        
        return occupationLabels[occupation || ''] || 'Not specified';
    };

    // Format date with proper error handling
    const formatDate = (dateString?: string): string => {
        if (!dateString) return 'Not provided';
        
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Invalid date';
            
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (error) {
            console.warn('Error formatting date:', error);
            return 'Invalid date';
        }
    };

    // Safe route helper with better error handling
    const safeRoute = (name: string, params?: any): string => {
        try {
            // Check if route function exists (it's a global function in Inertia)
            if (typeof route === 'function') {
                return route(name, params);
            }
            console.warn(`Route function not available for '${name}'`);
            return '#';
        } catch (error) {
            console.warn(`Route '${name}' not found:`, error);
            return '#';
        }
    };

    // Capitalize first letter
    const capitalize = (str?: string): string => {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    };

    // Get initials for avatar
    const getInitials = (): string => {
        const firstInitial = member.first_name?.charAt(0)?.toUpperCase() || '?';
        const lastInitial = member.last_name?.charAt(0)?.toUpperCase() || '?';
        return `${firstInitial}${lastInitial}`;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={safeRoute('members.index')}
                            className="text-gray-600 hover:text-gray-800 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg flex items-center justify-center">
                                <Church className="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h2 className="font-bold text-2xl text-gray-800 leading-tight">
                                    {fullName}
                                </h2>
                                <p className="text-sm text-gray-600 flex items-center">
                                    <BookOpen className="w-4 h-4 mr-1" />
                                    Member ID: {member.id}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Link
                            href={safeRoute('members.edit', member.id)}
                            className="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-4 py-2.5 rounded-lg flex items-center space-x-2 transition-all duration-200 shadow-sm hover:shadow-md"
                        >
                            <Edit className="w-4 h-4" />
                            <span>Edit Member</span>
                        </Link>
                        
                        {/* Status Change Dropdown */}
                        <div className="relative group">
                            <button className="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2.5 rounded-lg flex items-center space-x-2 transition-all duration-200 shadow-sm hover:shadow-md">
                                <RefreshCw className="w-4 h-4" />
                                <span>Change Status</span>
                            </button>
                            
                            <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div className="py-1">
                                    <div className="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                                        Current: <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(member.membership_status)}`}>
                                            {capitalize(member.membership_status || 'active')}
                                        </span>
                                    </div>
                                    {['active', 'inactive', 'transferred', 'deceased'].map((status) => (
                                        <button
                                            key={status}
                                            onClick={() => handleStatusChange(status)}
                                            disabled={status === member.membership_status}
                                            className={`w-full text-left px-3 py-2 text-sm transition-colors duration-150 ${
                                                status === member.membership_status 
                                                    ? 'text-gray-400 cursor-not-allowed' 
                                                    : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                                            }`}
                                        >
                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mr-2 ${getStatusColor(status)}`}>
                                                {capitalize(status)}
                                            </span>
                                            {status === 'active' && 'Set as Active'}
                                            {status === 'inactive' && 'Set as Inactive'}
                                            {status === 'transferred' && 'Mark as Transferred'}
                                            {status === 'deceased' && 'Mark as Deceased'}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`${fullName} - Member Details`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Flash Messages */}
                    {flash?.success && (
                        <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <UserCheck className="h-5 w-5 text-green-400" />
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-green-800">
                                        {flash.success}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                    
                    {flash?.error && (
                        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-red-800">
                                        {flash.error}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Information */}
                        <div className="lg:col-span-2 space-y-8">
                            {/* Basic Info Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div className="h-2 bg-gradient-to-r from-amber-600 via-yellow-500 to-amber-600"></div>
                                <div className="p-6">
                                    <div className="flex items-center space-x-6 mb-6">
                                        <div className="w-20 h-20 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                                            {getInitials()}
                                        </div>
                                        <div className="flex-1">
                                            <h3 className="text-2xl font-bold text-gray-900 mb-2">
                                                {fullName}
                                            </h3>
                                            <div className="flex items-center space-x-4 flex-wrap">
                                                <span className={`px-3 py-1 rounded-full text-sm font-semibold border ${getStatusColor(member.membership_status)}`}>
                                                    {capitalize(member.membership_status || 'active')}
                                                </span>
                                                <span className="text-amber-600 font-medium">
                                                    ID: {member.id}
                                                </span>
                                                {member.id_number && (
                                                    <span className="text-gray-600 text-sm">
                                                        National ID: {member.id_number}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Personal Information */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                                <p className="text-gray-900 font-medium">{member.first_name || 'Not provided'}</p>
                                            </div>
                                            {member.middle_name && (
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                                    <p className="text-gray-900 font-medium">{member.middle_name}</p>
                                                </div>
                                            )}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                                <p className="text-gray-900 font-medium">{member.last_name || 'Not provided'}</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                                <div className="flex items-center space-x-2">
                                                    <User className="w-4 h-4 text-amber-500" />
                                                    <p className="text-gray-900 capitalize">{member.gender || 'Not specified'}</p>
                                                </div>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                                <div className="flex items-center space-x-2">
                                                    <Calendar className="w-4 h-4 text-amber-500" />
                                                    <span className="text-gray-900">
                                                        {formatDate(member.date_of_birth)}
                                                        {age !== null && <span className="text-gray-500 ml-2">({age} years old)</span>}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="space-y-4">
                                            {member.matrimony_status && (
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">Marital Status</label>
                                                    <div className="flex items-center space-x-2">
                                                        <Heart className="w-4 h-4 text-rose-500" />
                                                        <p className="text-gray-900 capitalize">{member.matrimony_status}</p>
                                                    </div>
                                                </div>
                                            )}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Membership Date</label>
                                                <div className="flex items-center space-x-2">
                                                    <Star className="w-4 h-4 text-amber-500" />
                                                    <span className="text-gray-900">
                                                        {formatDate(member.membership_date)}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Occupation Status</label>
                                                <div className="flex items-center space-x-2">
                                                    <Briefcase className="w-4 h-4 text-amber-500" />
                                                    <p className="text-gray-900">{getOccupationLabel(member.occupation)}</p>
                                                </div>
                                            </div>
                                            {member.education_level && (
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">Education Level</label>
                                                    <div className="flex items-center space-x-2">
                                                        <GraduationCap className="w-4 h-4 text-amber-500" />
                                                        <p className="text-gray-900">{member.education_level}</p>
                                                    </div>
                                                </div>
                                            )}
                                            {member.id_number && (
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">National ID Number</label>
                                                    <div className="flex items-center space-x-2">
                                                        <Shield className="w-4 h-4 text-amber-500" />
                                                        <p className="text-gray-900">{member.id_number}</p>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Church Information */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Church className="w-5 h-5 text-amber-500 mr-2" />
                                    Church Information
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Local Church</label>
                                            <p className="text-gray-900 font-medium">{member.local_church || 'Not specified'}</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Church Group</label>
                                            <p className="text-gray-900">{member.church_group || 'Not specified'}</p>
                                        </div>
                                        {member.minister && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Minister</label>
                                                <p className="text-gray-900">{member.minister}</p>
                                            </div>
                                        )}
                                        {member.sponsor && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Sponsor</label>
                                                <p className="text-gray-900">{member.sponsor}</p>
                                            </div>
                                        )}
                                    </div>
                                    <div className="space-y-4">
                                        {member.baptism_date && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Baptism Date</label>
                                                <div className="flex items-center space-x-2">
                                                    <Cross className="w-4 h-4 text-blue-500" />
                                                    <span className="text-gray-900">{formatDate(member.baptism_date)}</span>
                                                </div>
                                            </div>
                                        )}
                                        {member.confirmation_date && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Confirmation Date</label>
                                                <div className="flex items-center space-x-2">
                                                    <UserCheck className="w-4 h-4 text-purple-500" />
                                                    <span className="text-gray-900">{formatDate(member.confirmation_date)}</span>
                                                </div>
                                            </div>
                                        )}
                                        {member.parent && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Parent/Guardian</label>
                                                <p className="text-gray-900">{member.parent}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Contact Information */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Phone className="w-5 h-5 text-amber-500 mr-2" />
                                    Contact Information
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-4">
                                        {member.phone && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                                <div className="flex items-center space-x-2">
                                                    <Phone className="w-4 h-4 text-amber-500" />
                                                    <a href={`tel:${member.phone}`} className="text-blue-600 hover:text-blue-700">
                                                        {member.phone}
                                                    </a>
                                                </div>
                                            </div>
                                        )}
                                        {member.email && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                                <div className="flex items-center space-x-2">
                                                    <Mail className="w-4 h-4 text-amber-500" />
                                                    <a href={`mailto:${member.email}`} className="text-blue-600 hover:text-blue-700">
                                                        {member.email}
                                                    </a>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    <div>
                                        {member.residence && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Residence</label>
                                                <div className="flex items-start space-x-2">
                                                    <MapPin className="w-4 h-4 text-amber-500 mt-1 flex-shrink-0" />
                                                    <p className="text-gray-900">{member.residence}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Cultural Information */}
                            {(member.tribe || member.clan) && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                    <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <Users className="w-5 h-5 text-amber-500 mr-2" />
                                        Cultural Information
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {member.tribe && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Tribe</label>
                                                <p className="text-gray-900">{member.tribe}</p>
                                            </div>
                                        )}
                                        {member.clan && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Clan</label>
                                                <p className="text-gray-900">{member.clan}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Emergency Contact */}
                            {(member.emergency_contact || member.emergency_phone) && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                    <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <Contact className="w-5 h-5 text-red-500 mr-2" />
                                        Emergency Contact
                                    </h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {member.emergency_contact && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                                <p className="text-gray-900">{member.emergency_contact}</p>
                                            </div>
                                        )}
                                        {member.emergency_phone && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                                                <a href={`tel:${member.emergency_phone}`} className="text-blue-600 hover:text-blue-700">
                                                    {member.emergency_phone}
                                                </a>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* System Information */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Clock className="w-5 h-5 text-gray-500 mr-2" />
                                    System Information
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Created At</label>
                                        <p className="text-gray-900 text-sm">{formatDate(member.created_at)}</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                                        <p className="text-gray-900 text-sm">{formatDate(member.updated_at)}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Family Information */}
                            {member.family && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                    <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <Heart className="w-5 h-5 text-rose-500 mr-2" />
                                        Family
                                    </h4>
                                    <div className="space-y-3">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Family Name</label>
                                            <Link 
                                                href={safeRoute('families.show', member.family.id)}
                                                className="text-blue-600 hover:text-blue-700 font-medium"
                                            >
                                                {member.family.family_name}
                                            </Link>
                                        </div>
                                        {member.family.family_code && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Family Code</label>
                                                <p className="text-gray-900">{member.family.family_code}</p>
                                            </div>
                                        )}
                                        {member.family.parish_section && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">Parish Section</label>
                                                <p className="text-gray-900">{member.family.parish_section}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Quick Stats */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Star className="w-5 h-5 text-amber-500 mr-2" />
                                    Quick Stats
                                </h4>
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-600">Member ID</span>
                                        <span className="font-semibold text-gray-900">#{member.id}</span>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-600">Membership Status</span>
                                        <span className={`px-2 py-1 rounded-full text-xs font-semibold ${getStatusColor(member.membership_status)}`}>
                                            {capitalize(member.membership_status || 'active')}
                                        </span>
                                    </div>
                                    {age !== null && (
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600">Age</span>
                                            <span className="font-semibold text-gray-900">{age} years</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm text-gray-600">Member Since</span>
                                        <span className="font-semibold text-gray-900">
                                            {member.membership_date ? new Date(member.membership_date).getFullYear() : 'N/A'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">Actions</h4>
                                <div className="space-y-3">
                                    <Link
                                        href={safeRoute('members.edit', member.id)}
                                        className="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-4 py-2.5 rounded-lg flex items-center justify-center space-x-2 transition-all duration-200"
                                    >
                                        <Edit className="w-4 h-4" />
                                        <span>Edit Member</span>
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => console.log('Add Sacrament for member:', member.id)}
                                        className="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2.5 rounded-lg flex items-center justify-center space-x-2 transition-all duration-200"
                                    >
                                        <Cross className="w-4 h-4" />
                                        <span>Add Sacrament</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => console.log('Record Tithe for member:', member.id)}
                                        className="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-4 py-2.5 rounded-lg flex items-center justify-center space-x-2 transition-all duration-200"
                                    >
                                        <BookOpen className="w-4 h-4" />
                                        <span>Record Tithe</span>
                                    </button>
                                </div>
                            </div>

                            {/* Notes */}
                            {member.notes && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                    <h4 className="text-lg font-semibold text-gray-900 mb-4">Notes</h4>
                                    <div className="max-h-32 overflow-y-auto">
                                        <p className="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{member.notes}</p>
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