import { memo, useState, useCallback, useMemo } from 'react';
import { Eye, Edit, Trash2, Phone, Mail, MapPin, Calendar } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Member } from './types';
import { memberRoute } from './SafeRouteHelper';

interface MemberCardProps {
    member: Member;
    isSelected: boolean;
    onToggleSelection: (id: number) => void;
    onDelete: (member: Member) => void;
    onStatusChange?: (id: number, status: string) => void;
    canChangeStatus?: boolean;
}

const MemberCard = memo<MemberCardProps>(({ 
    member, 
    isSelected, 
    onToggleSelection, 
    onDelete, 
    onStatusChange,
    canChangeStatus = true 
}) => {
    const [isChangingStatus, setIsChangingStatus] = useState(false);
    
    // Memoize computed values
    const memberName = useMemo(() => {
        return `${member.first_name || ''} ${member.last_name || ''}`.trim() || 'Unknown Member';
    }, [member.first_name, member.last_name]);

    const memberAge = useMemo(() => {
        if (!member.date_of_birth) return null;
        try {
            const birthDate = new Date(member.date_of_birth);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                return age - 1;
            }
            return age;
        } catch {
            return null;
        }
    }, [member.date_of_birth]);

    const handleToggleSelection = useCallback(() => {
        onToggleSelection(member.id);
    }, [onToggleSelection, member.id]);

    const handleDelete = useCallback(() => {
        onDelete(member);
    }, [onDelete, member]);

    return (
        <div className={`
            bg-white rounded-lg shadow-sm border transition-all duration-200 hover:shadow-md
            ${isSelected ? 'ring-2 ring-indigo-500 border-indigo-200' : 'border-gray-200 hover:border-gray-300'}
        `}>
            <div className="p-6">
                {/* Header with selection */}
                <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-3">
                        <input
                            type="checkbox"
                            checked={isSelected}
                            onChange={handleToggleSelection}
                            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            aria-label={`Select ${memberName}`}
                        />
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 truncate" title={memberName}>
                                {memberName}
                            </h3>
                            <p className="text-sm text-gray-500">
                                {member.member_number ? `#${member.member_number}` : `ID: ${member.id}`}
                            </p>
                        </div>
                    </div>
                    
                    <div className="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">
                        {member.membership_status || 'Active'}
                    </div>
                </div>

                {/* Member Details */}
                <div className="space-y-2 mb-4">
                    {member.phone && (
                        <div className="flex items-center text-sm text-gray-600">
                            <Phone className="w-4 h-4 mr-2 text-gray-400" />
                            <span className="truncate">{member.phone}</span>
                        </div>
                    )}
                    
                    {member.email && (
                        <div className="flex items-center text-sm text-gray-600">
                            <Mail className="w-4 h-4 mr-2 text-gray-400" />
                            <span className="truncate">{member.email}</span>
                        </div>
                    )}
                    
                    {member.residence && (
                        <div className="flex items-center text-sm text-gray-600">
                            <MapPin className="w-4 h-4 mr-2 text-gray-400" />
                            <span className="truncate">{member.residence}</span>
                        </div>
                    )}
                    
                    {memberAge && (
                        <div className="flex items-center text-sm text-gray-600">
                            <Calendar className="w-4 h-4 mr-2 text-gray-400" />
                            <span>{memberAge} years old</span>
                        </div>
                    )}
                </div>

                {/* Church Information */}
                <div className="bg-gray-50 rounded-lg p-3 mb-4">
                    <div className="text-sm">
                        <p className="font-medium text-gray-900 truncate" title={member.local_church}>
                            {member.local_church || 'No church assigned'}
                        </p>
                        <p className="text-gray-600 truncate" title={member.church_group}>
                            {member.church_group || 'No group assigned'}
                        </p>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div className="flex space-x-2">
                        <Link
                            href={memberRoute('show', member.id)}
                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            title={`View ${memberName} details`}
                        >
                            <Eye className="w-3 h-3 mr-1" />
                            View
                        </Link>
                        
                        <Link
                            href={memberRoute('edit', member.id)}
                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            title={`Edit ${memberName}`}
                        >
                            <Edit className="w-3 h-3 mr-1" />
                            Edit
                        </Link>
                    </div>
                    
                    <button
                        onClick={handleDelete}
                        className="inline-flex items-center px-3 py-1.5 border border-red-300 rounded-md text-xs font-medium text-red-700 bg-white hover:bg-red-50 transition-colors"
                        title={`Delete ${memberName}`}
                    >
                        <Trash2 className="w-3 h-3 mr-1" />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    );
});

MemberCard.displayName = 'MemberCard';

export default MemberCard;