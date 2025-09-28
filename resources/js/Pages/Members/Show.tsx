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
    RefreshCw,
    Home,
    Award,
    Crown,
    ChevronRight,
    Download,
    FileText,
    ScrollText
} from 'lucide-react';

// Enhanced interfaces to match our new structure
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
    head_of_family_name?: string;
    created_by?: number;
    created_at?: string;
    updated_at?: string;
}

interface Member {
    id: number;
    local_church?: string;
    small_christian_community?: string;
    church_group?: string;
    additional_church_groups?: string[];
    first_name: string;
    middle_name?: string;
    last_name: string;
    date_of_birth?: string;
    gender?: string;
    phone?: string;
    email?: string;
    id_number?: string;
    godparent?: string;
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
    matrimony_status?: 'single' | 'married' | 'widowed';
    marriage_type?: 'customary' | 'church';
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

    // Safe route helper
    const safeRoute = (routeName: string, params?: any) => {
        try {
            return route(routeName, params);
        } catch (error) {
            console.warn(`Route ${routeName} not found, falling back to members.index`);
            return route('members.index');
        }
    };

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

    // Format date for display
    const formatDate = (dateString?: string): string => {
        if (!dateString) return 'Not specified';
        try {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (error) {
            return 'Invalid date';
        }
    };

    // Get church group label
    const getChurchGroupLabel = (groupValue?: string): string => {
        const groupLabels: Record<string, string> = {
            'PMC': 'PMC (Pontifical Missionary Childhood)',
            'Youth': 'Youth',
            'C.W.A': 'C.W.A (Catholic Women Association)',
            'CMA': 'CMA (Catholic Men Association)',
            'Choir': 'Choir',
            'Catholic Action': 'Catholic Action',
            'Pioneer': 'Pioneer'
        };
        return groupLabels[groupValue || ''] || groupValue || 'Not specified';
    };

    // Get education level label
    const getEducationLevelLabel = (level?: string): string => {
        const educationLabels: Record<string, string> = {
            'none': 'No Formal Education',
            'primary': 'Primary Education',
            'kcpe': 'KCPE',
            'secondary': 'Secondary Education',
            'kcse': 'KCSE',
            'certificate': 'Certificate',
            'diploma': 'Diploma',
            'degree': 'Degree',
            'masters': 'Masters',
            'phd': 'PhD'
        };
        return educationLabels[level || ''] || level || 'Not specified';
    };

    // Get occupation label
    const getOccupationLabel = (occupation?: string): string => {
        const occupationLabels: Record<string, string> = {
            'employed': 'Employed',
            'self_employed': 'Self-employed',
            'not_employed': 'Not Employed'
        };
        return occupationLabels[occupation || ''] || occupation || 'Not specified';
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
        return statusColors[status || 'active'] || statusColors.active;
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
                        
                        {/* Certificate Downloads Dropdown */}
                        <div className="relative group">
                            <button className="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-2.5 rounded-lg flex items-center space-x-2 transition-all duration-200 shadow-sm hover:shadow-md">
                                <Download className="w-4 h-4" />
                                <span>Certificates</span>
                            </button>
                            
                            <div className="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div className="py-1">
                                    <div className="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                                        Available Certificates
                                    </div>
                                    
                                    {member.baptism_date ? (
                                        <a
                                            href={safeRoute('members.baptism-certificate', member.id)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="w-full text-left px-3 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-900 flex items-center space-x-2 transition-colors duration-150"
                                        >
                                            <ScrollText className="w-4 h-4 text-purple-500" />
                                            <div>
                                                <div className="font-medium">Baptism Certificate</div>
                                                <div className="text-xs text-gray-500">Baptized: {formatDate(member.baptism_date)}</div>
                                            </div>
                                        </a>
                                    ) : (
                                        <div className="px-3 py-3 text-sm text-gray-400 cursor-not-allowed flex items-center space-x-2">
                                            <ScrollText className="w-4 h-4 text-gray-300" />
                                            <div>
                                                <div>Baptism Certificate</div>
                                                <div className="text-xs">Not baptized yet</div>
                                            </div>
                                        </div>
                                    )}
                                    
                                    {member.matrimony_status && ['married', 'separated', 'divorced', 'widowed'].includes(member.matrimony_status) ? (
                                        <a
                                            href={safeRoute('members.marriage-certificate', member.id)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="w-full text-left px-3 py-3 text-sm text-gray-700 hover:bg-pink-50 hover:text-pink-900 flex items-center space-x-2 transition-colors duration-150"
                                        >
                                            <Heart className="w-4 h-4 text-pink-500" />
                                            <div>
                                                <div className="font-medium">Marriage Certificate</div>
                                                <div className="text-xs text-gray-500">Church Marriage</div>
                                            </div>
                                        </a>
                                    ) : (
                                        <div className="px-3 py-3 text-sm text-gray-400 cursor-not-allowed flex items-center space-x-2">
                                            <Heart className="w-4 h-4 text-gray-300" />
                                            <div>
                                                <div>Marriage Certificate</div>
                                                <div className="text-xs">
                                                    {member.matrimony_status === 'married' && member.marriage_type !== 'church' 
                                                        ? 'Customary marriage only' 
                                                        : 'Not married in church'}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                        
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
                                                    <span className="text-gray-600 font-medium">
                                                        National ID: {member.id_number}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="flex items-center space-x-3">
                                            <Calendar className="w-5 h-5 text-amber-500" />
                                            <div>
                                                <p className="text-sm text-gray-600">Date of Birth</p>
                                                <p className="font-semibold text-gray-900">
                                                    {formatDate(member.date_of_birth)}
                                                    {age && <span className="text-sm text-gray-600 ml-2">({age} years old)</span>}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center space-x-3">
                                            <User className="w-5 h-5 text-amber-500" />
                                            <div>
                                                <p className="text-sm text-gray-600">Gender</p>
                                                <p className="font-semibold text-gray-900">{member.gender || 'Not specified'}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-center space-x-3">
                                            <Clock className="w-5 h-5 text-amber-500" />
                                            <div>
                                                <p className="text-sm text-gray-600">Member Since</p>
                                                <p className="font-semibold text-gray-900">{formatDate(member.membership_date)}</p>
                                            </div>
                                        </div>

                                        {member.matrimony_status && (
                                            <div className="flex items-center space-x-3">
                                                <Heart className="w-5 h-5 text-amber-500" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Matrimony Status</p>
                                                    <p className="font-semibold text-gray-900">
                                                        {capitalize(member.matrimony_status)}
                                                        {member.matrimony_status === 'married' && member.marriage_type && (
                                                            <span className="text-sm text-gray-600 ml-2">
                                                                ({capitalize(member.marriage_type)} Marriage)
                                                            </span>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Church Information Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div className="h-2 bg-gradient-to-r from-blue-600 via-purple-500 to-blue-600"></div>
                                <div className="p-6">
                                    <div className="flex items-center space-x-3 mb-6">
                                        <Church className="w-6 h-6 text-blue-600" />
                                        <h3 className="text-xl font-bold text-gray-900">Church Information</h3>
                                    </div>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="flex items-start space-x-3">
                                            <Church className="w-5 h-5 text-blue-500 mt-0.5" />
                                            <div>
                                                <p className="text-sm text-gray-600">Local Church</p>
                                                <p className="font-semibold text-gray-900">{member.local_church || 'Not specified'}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-start space-x-3">
                                            <Users className="w-5 h-5 text-blue-500 mt-0.5" />
                                            <div>
                                                <p className="text-sm text-gray-600">Small Christian Community</p>
                                                <p className="font-semibold text-gray-900">{member.small_christian_community || 'Not specified'}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-start space-x-3">
                                            <Crown className="w-5 h-5 text-blue-500 mt-0.5" />
                                            <div>
                                                <p className="text-sm text-gray-600">Primary Church Group</p>
                                                <p className="font-semibold text-gray-900">{getChurchGroupLabel(member.church_group)}</p>
                                            </div>
                                        </div>

                                        {member.additional_church_groups && member.additional_church_groups.length > 0 && (
                                            <div className="flex items-start space-x-3">
                                                <Star className="w-5 h-5 text-blue-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Additional Church Groups</p>
                                                    <div className="flex flex-wrap gap-2 mt-1">
                                                        {member.additional_church_groups.map((group, index) => (
                                                            <span key={index} className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                {getChurchGroupLabel(group)}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {member.baptism_date && (
                                            <div className="flex items-start space-x-3">
                                                <Cross className="w-5 h-5 text-blue-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Baptism Date</p>
                                                    <p className="font-semibold text-gray-900">{formatDate(member.baptism_date)}</p>
                                                    {member.minister && (
                                                        <p className="text-sm text-gray-600">By: {member.minister}</p>
                                                    )}
                                                </div>
                                            </div>
                                        )}

                                        {member.confirmation_date && (
                                            <div className="flex items-start space-x-3">
                                                <Award className="w-5 h-5 text-blue-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Confirmation Date</p>
                                                    <p className="font-semibold text-gray-900">{formatDate(member.confirmation_date)}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.godparent && (
                                            <div className="flex items-start space-x-3">
                                                <Shield className="w-5 h-5 text-blue-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Godparent</p>
                                                    <p className="font-semibold text-gray-900">{member.godparent}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Personal Information Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div className="h-2 bg-gradient-to-r from-green-600 via-teal-500 to-green-600"></div>
                                <div className="p-6">
                                    <div className="flex items-center space-x-3 mb-6">
                                        <User className="w-6 h-6 text-green-600" />
                                        <h3 className="text-xl font-bold text-gray-900">Personal Information</h3>
                                    </div>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {member.education_level && (
                                            <div className="flex items-start space-x-3">
                                                <GraduationCap className="w-5 h-5 text-green-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Education Level</p>
                                                    <p className="font-semibold text-gray-900">{getEducationLevelLabel(member.education_level)}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.occupation && (
                                            <div className="flex items-start space-x-3">
                                                <Briefcase className="w-5 h-5 text-green-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Occupation</p>
                                                    <p className="font-semibold text-gray-900">{getOccupationLabel(member.occupation)}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.tribe && (
                                            <div className="flex items-start space-x-3">
                                                <Users className="w-5 h-5 text-green-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Tribe</p>
                                                    <p className="font-semibold text-gray-900">{member.tribe}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.clan && (
                                            <div className="flex items-start space-x-3">
                                                <Home className="w-5 h-5 text-green-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Clan</p>
                                                    <p className="font-semibold text-gray-900">{member.clan}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.parent && (
                                            <div className="flex items-start space-x-3">
                                                <Users className="w-5 h-5 text-green-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Parent/Guardian</p>
                                                    <p className="font-semibold text-gray-900">{member.parent}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Family Information Card */}
                            {member.family && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div className="h-2 bg-gradient-to-r from-purple-600 via-pink-500 to-purple-600"></div>
                                    <div className="p-6">
                                        <div className="flex items-center justify-between mb-6">
                                            <div className="flex items-center space-x-3">
                                                <Home className="w-6 h-6 text-purple-600" />
                                                <h3 className="text-xl font-bold text-gray-900">Family Information</h3>
                                            </div>
                                            {/* <Link
                                                href={safeRoute('families.show', member.family.id)}
                                                className="text-purple-600 hover:text-purple-800 flex items-center space-x-1 text-sm font-medium"
                                            >
                                                <span>View Family</span>
                                                <ChevronRight className="w-4 h-4" />
                                            </Link> */}
                                        </div>
                                        
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="flex items-start space-x-3">
                                                <Home className="w-5 h-5 text-purple-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Family Name</p>
                                                    <p className="font-semibold text-gray-900">
                                                        {member.family.family_name}
                                                        {member.family.family_code && (
                                                            <span className="text-sm text-gray-600 ml-2">({member.family.family_code})</span>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>

                                            {member.family.head_of_family_name && (
                                                <div className="flex items-start space-x-3">
                                                    <Crown className="w-5 h-5 text-purple-500 mt-0.5" />
                                                    <div>
                                                        <p className="text-sm text-gray-600">Head of Family</p>
                                                        <p className="font-semibold text-gray-900">{member.family.head_of_family_name}</p>
                                                    </div>
                                                </div>
                                            )}

                                            {member.family.parish_section && (
                                                <div className="flex items-start space-x-3">
                                                    <MapPin className="w-5 h-5 text-purple-500 mt-0.5" />
                                                    <div>
                                                        <p className="text-sm text-gray-600">Parish Section</p>
                                                        <p className="font-semibold text-gray-900">{member.family.parish_section}</p>
                                                    </div>
                                                </div>
                                            )}

                                            {member.family.address && (
                                                <div className="flex items-start space-x-3">
                                                    <MapPin className="w-5 h-5 text-purple-500 mt-0.5" />
                                                    <div>
                                                        <p className="text-sm text-gray-600">Family Address</p>
                                                        <p className="font-semibold text-gray-900">{member.family.address}</p>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Notes */}
                            {member.notes && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div className="h-2 bg-gradient-to-r from-indigo-600 via-blue-500 to-indigo-600"></div>
                                    <div className="p-6">
                                        <div className="flex items-center space-x-3 mb-4">
                                            <BookOpen className="w-6 h-6 text-indigo-600" />
                                            <h3 className="text-xl font-bold text-gray-900">Additional Notes</h3>
                                        </div>
                                        <p className="text-gray-700 leading-relaxed">{member.notes}</p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Contact Information */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div className="h-2 bg-gradient-to-r from-emerald-600 via-green-500 to-emerald-600"></div>
                                <div className="p-6">
                                    <div className="flex items-center space-x-3 mb-6">
                                        <Contact className="w-6 h-6 text-emerald-600" />
                                        <h3 className="text-xl font-bold text-gray-900">Contact</h3>
                                    </div>
                                    
                                    <div className="space-y-4">
                                        {member.phone && (
                                            <div className="flex items-center space-x-3">
                                                <Phone className="w-5 h-5 text-emerald-500" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Phone</p>
                                                    <a href={`tel:${member.phone}`} className="font-semibold text-emerald-600 hover:text-emerald-800">
                                                        {member.phone}
                                                    </a>
                                                </div>
                                            </div>
                                        )}
                                        
                                        {member.email && (
                                            <div className="flex items-center space-x-3">
                                                <Mail className="w-5 h-5 text-emerald-500" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Email</p>
                                                    <a href={`mailto:${member.email}`} className="font-semibold text-emerald-600 hover:text-emerald-800">
                                                        {member.email}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {member.residence && (
                                            <div className="flex items-start space-x-3">
                                                <MapPin className="w-5 h-5 text-emerald-500 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-600">Residence</p>
                                                    <p className="font-semibold text-gray-900">{member.residence}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Emergency Contact */}
                            {(member.emergency_contact || member.emergency_phone) && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div className="h-2 bg-gradient-to-r from-red-600 via-orange-500 to-red-600"></div>
                                    <div className="p-6">
                                        <div className="flex items-center space-x-3 mb-6">
                                            <Shield className="w-6 h-6 text-red-600" />
                                            <h3 className="text-xl font-bold text-gray-900">Emergency Contact</h3>
                                        </div>
                                        
                                        <div className="space-y-4">
                                            {member.emergency_contact && (
                                                <div className="flex items-center space-x-3">
                                                    <User className="w-5 h-5 text-red-500" />
                                                    <div>
                                                        <p className="text-sm text-gray-600">Contact Name</p>
                                                        <p className="font-semibold text-gray-900">{member.emergency_contact}</p>
                                                    </div>
                                                </div>
                                            )}
                                            
                                            {member.emergency_phone && (
                                                <div className="flex items-center space-x-3">
                                                    <Phone className="w-5 h-5 text-red-500" />
                                                    <div>
                                                        <p className="text-sm text-gray-600">Phone</p>
                                                        <a href={`tel:${member.emergency_phone}`} className="font-semibold text-red-600 hover:text-red-800">
                                                            {member.emergency_phone}
                                                        </a>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Quick Actions */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div className="h-2 bg-gradient-to-r from-gray-600 via-slate-500 to-gray-600"></div>
                                <div className="p-6">
                                    <h3 className="text-xl font-bold text-gray-900 mb-4">Quick Actions</h3>
                                    <div className="space-y-3">
                                        <Link
                                            href={safeRoute('members.edit', member.id)}
                                            className="w-full bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                                        >
                                            <Edit className="w-4 h-4" />
                                            <span>Edit Member</span>
                                        </Link>
                                        
                                        {member.phone && (
                                            <a
                                                href={`tel:${member.phone}`}
                                                className="w-full bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                                            >
                                                <Phone className="w-4 h-4" />
                                                <span>Call Member</span>
                                            </a>
                                        )}
                                        
                                        {member.email && (
                                            <a
                                                href={`mailto:${member.email}`}
                                                className="w-full bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                                            >
                                                <Mail className="w-4 h-4" />
                                                <span>Send Email</span>
                                            </a>
                                        )}
                                        
                                        {/* Certificate Downloads */}
                                        {member.baptism_date && (
                                            <a
                                                href={safeRoute('members.baptism-certificate', member.id)}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="w-full bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                                            >
                                                <ScrollText className="w-4 h-4" />
                                                <span>Download Baptism Certificate</span>
                                            </a>
                                        )}
                                        
                                        {member.matrimony_status && ['married', 'separated', 'divorced', 'widowed'].includes(member.matrimony_status) && (
                                            <a
                                                href={safeRoute('members.marriage-certificate', member.id)}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="w-full bg-pink-50 hover:bg-pink-100 border border-pink-200 text-pink-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                                            >
                                                <Heart className="w-4 h-4" />
                                                <span>Download Marriage Certificate</span>
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
