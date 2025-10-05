import React, { memo, useState } from 'react';
import { Eye, Edit, Trash2, Phone, Mail, MapPin, Calendar, ChevronDown, CheckCircle, AlertCircle, UserX, Users } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Member } from './types';

interface MemberCardProps {
    member: Member;
    isSelected: boolean;
    onToggleSelection: (id: number) => void;
    onDelete: (member: Member) => void;
    onStatusChange?: (id: number, status: string) => void;
    canChangeStatus?: boolean;
}

// Status configuration
const STATUS_CONFIG = {
    active: {
        label: 'Active',
        icon: CheckCircle,
        bgColor: 'bg-green-100',
        textColor: 'text-green-800',
        borderColor: 'border-green-200',
    },
    inactive: {
        label: 'Inactive',
        icon: AlertCircle,
        bgColor: 'bg-yellow-100',
        textColor: 'text-yellow-800',
        borderColor: 'border-yellow-200',
    },
    transferred: {
        label: 'Transferred',
        icon: Users,
        bgColor: 'bg-blue-100',
        textColor: 'text-blue-800',
        borderColor: 'border-blue-200',
    },
    deceased: {
        label: 'Deceased',
        icon: UserX,
        bgColor: 'bg-gray-100',
        textColor: 'text-gray-800',
        borderColor: 'border-gray-200',
    },
};

const MemberCard = memo<MemberCardProps>(({ 
    member, 
    isSelected, 
    onToggleSelection, 
    onDelete, 
    onStatusChange,
    canChangeStatus = true 
}) => {
    const [showStatusDropdown, setShowStatusDropdown] = useState(false);
    const [isChangingStatus, setIsChangingStatus] = useState(false);

    const currentStatus = member.membership_status?.toLowerCase() || 'active';
    const statusConfig = STATUS_CONFIG[currentStatus as keyof typeof STATUS_CONFIG] || STATUS_CONFIG.active;
    const StatusIcon = statusConfig.icon;

    const handleStatusChange = async (newStatus: string) => {
        if (!onStatusChange || isChangingStatus) return;
        
        setIsChangingStatus(true);
        setShowStatusDropdown(false);
        
        try {
            await onStatusChange(member.id, newStatus);
        } finally {
            setIsChangingStatus(false);
        }
    };
    return (
        <div className="bg-white rounded-lg shadow hover:shadow-md transition-shadow p-6">
            {/* Selection Checkbox */}
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center">
                    <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => onToggleSelection(member.id)}
                        className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <div className="ml-3">
                        <h3 className="text-lg font-medium text-gray-900">
                            {member.full_name}
                        </h3>
                        <p className="text-sm text-gray-500">
                            ID: {member.id_number || `#${member.id}`}
                        </p>
                    </div>
                </div>
                
                {/* Status badge with dropdown */}
                <div className="relative">
                    {canChangeStatus && onStatusChange ? (
                        <button
                            type="button"
                            onClick={() => setShowStatusDropdown(!showStatusDropdown)}
                            disabled={isChangingStatus}
                            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors ${statusConfig.bgColor} ${statusConfig.textColor} ${statusConfig.borderColor} hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ${
                                isChangingStatus ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                            }`}
                        >
                            <StatusIcon className="w-3 h-3 mr-1" />
                            {isChangingStatus ? 'Updating...' : statusConfig.label}
                            {!isChangingStatus && <ChevronDown className="w-3 h-3 ml-1" />}
                        </button>
                    ) : (
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${statusConfig.bgColor} ${statusConfig.textColor} ${statusConfig.borderColor}`}>
                            <StatusIcon className="w-3 h-3 mr-1" />
                            {statusConfig.label}
                        </span>
                    )}

                    {/* Status dropdown */}
                    {showStatusDropdown && (
                        <div className="absolute right-0 mt-1 w-36 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                            <div className="py-1">
                                {Object.entries(STATUS_CONFIG).map(([status, config]) => {
                                    const Icon = config.icon;
                                    const isCurrentStatus = status === currentStatus;
                                    
                                    return (
                                        <button
                                            key={status}
                                            onClick={() => handleStatusChange(status)}
                                            disabled={isCurrentStatus || isChangingStatus}
                                            className={`w-full text-left px-3 py-2 text-sm flex items-center transition-colors ${
                                                isCurrentStatus
                                                    ? 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                                    : 'text-gray-700 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none'
                                            }`}
                                        >
                                            <Icon className="w-3 h-3 mr-2" />
                                            {config.label}
                                            {isCurrentStatus && (
                                                <span className="ml-auto text-xs">(Current)</span>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Member Details */}
            <div className="space-y-2 mb-4">
                <div className="flex items-center text-sm text-gray-600">
                    <Calendar className="h-4 w-4 mr-2" />
                    {member.date_of_birth && (
                        <>Age: {new Date().getFullYear() - new Date(member.date_of_birth).getFullYear()}</>
                    )}
                    {member.gender && (
                        <>{member.date_of_birth ? ' • ' : ''}{member.gender}</>
                    )}
                </div>
                
                {member.phone && (
                    <div className="flex items-center text-sm text-gray-600">
                        <Phone className="h-4 w-4 mr-2" />
                        {member.phone}
                    </div>
                )}
                
                {member.email && (
                    <div className="flex items-center text-sm text-gray-600">
                        <Mail className="h-4 w-4 mr-2" />
                        {member.email}
                    </div>
                )}
                
                {member.residence && (
                    <div className="flex items-center text-sm text-gray-600">
                        <MapPin className="h-4 w-4 mr-2" />
                        {member.residence}
                    </div>
                )}
            </div>

            {/* Church Information */}
            <div className="border-t pt-3 mb-4">
                <div className="text-sm">
                    <p><span className="font-medium">Church:</span> {member.local_church}</p>
                    <p><span className="font-medium">Group:</span> {member.church_group}</p>
                    {member.family && (
                        <p><span className="font-medium">Family:</span> {member.family.family_name}</p>
                    )}
                </div>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-end space-x-2">
                <Link
                    href={route('members.show', member.id)}
                    className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <Eye className="h-3 w-3 mr-1" />
                    View
                </Link>
                
                <Link
                    href={route('members.edit', member.id)}
                    className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <Edit className="h-3 w-3 mr-1" />
                    Edit
                </Link>
                
                <button
                    type="button"
                    onClick={() => onDelete(member)}
                    className="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                    <Trash2 className="h-3 w-3 mr-1" />
                    Delete
                </button>
            </div>
            
            {/* Click outside to close dropdown */}
            {showStatusDropdown && (
                <div
                    className="fixed inset-0 z-0"
                    onClick={() => setShowStatusDropdown(false)}
                />
            )}
        </div>
    );
});

MemberCard.displayName = 'MemberCard';

export default MemberCard;