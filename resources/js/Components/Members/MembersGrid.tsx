import { memo } from 'react';
import MemberCard from './MemberCard';
import { Member } from './types';

interface MembersGridProps {
    members: Member[];
    selectedMembers: number[];
    isLoading: boolean;
    onToggleSelection: (id: number) => void;
    onDelete: (member: Member) => void;
    onStatusChange?: (memberId: number, newStatus: string) => Promise<void>;
}

const MembersGrid = memo<MembersGridProps>(({
    members,
    selectedMembers,
    isLoading,
    onToggleSelection,
    onDelete,
    onStatusChange,
}) => {
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {Array.from({ length: 6 }).map((_, index) => (
                    <div key={index} className="bg-white rounded-lg shadow p-6 animate-pulse">
                        <div className="flex items-start justify-between mb-4">
                            <div className="flex items-center">
                                <div className="h-4 w-4 bg-gray-200 rounded"></div>
                                <div className="ml-3">
                                    <div className="h-5 bg-gray-200 rounded w-32 mb-2"></div>
                                    <div className="h-4 bg-gray-200 rounded w-20"></div>
                                </div>
                            </div>
                            <div className="h-6 bg-gray-200 rounded-full w-16"></div>
                        </div>
                        
                        <div className="space-y-2 mb-4">
                            <div className="h-4 bg-gray-200 rounded w-24"></div>
                            <div className="h-4 bg-gray-200 rounded w-32"></div>
                            <div className="h-4 bg-gray-200 rounded w-28"></div>
                        </div>
                        
                        <div className="border-t pt-3 mb-4">
                            <div className="h-4 bg-gray-200 rounded w-36 mb-2"></div>
                            <div className="h-4 bg-gray-200 rounded w-32 mb-2"></div>
                            <div className="h-4 bg-gray-200 rounded w-28"></div>
                        </div>
                        
                        <div className="flex justify-end space-x-2">
                            <div className="h-8 bg-gray-200 rounded w-16"></div>
                            <div className="h-8 bg-gray-200 rounded w-16"></div>
                            <div className="h-8 bg-gray-200 rounded w-20"></div>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (members.length === 0) {
        return (
            <div className="text-center py-12">
                <div className="mx-auto h-24 w-24 text-gray-400">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1L13.5 2.5L16.17 5.17C15.24 5.06 14.32 5 13.38 5C11.1 5 8.82 5.5 7 6.5C5.18 7.5 4 9.18 4 11V21C4 21.55 4.45 22 5 22H19C19.55 22 20 21.55 20 21V11C20 10.45 19.55 10 19 10H13V9C13 8.45 12.55 8 12 8C11.45 8 11 8.45 11 9V10H5C4.45 10 4 10.45 4 11V21C4 21.55 4.45 22 5 22H19C19.55 22 20 21.55 20 21V11C20 9.18 18.82 7.5 17 6.5C15.18 5.5 12.9 5 10.62 5C9.68 5 8.76 5.06 7.83 5.17L10.5 2.5L9 1L3 7V9H21Z" />
                    </svg>
                </div>
                <h3 className="mt-2 text-sm font-medium text-gray-900">No members found</h3>
                <p className="mt-1 text-sm text-gray-500">
                    No members match your current search and filter criteria.
                </p>
                <div className="mt-6">
                    <button
                        type="button"
                        onClick={() => window.location.reload()}
                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Clear filters and try again
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {members.map((member) => (
                <MemberCard
                    key={member.id}
                    member={member}
                    isSelected={selectedMembers.includes(member.id)}
                    onToggleSelection={onToggleSelection}
                    onDelete={onDelete}
                    onStatusChange={onStatusChange}
                />
            ))}
        </div>
    );
});

MembersGrid.displayName = 'MembersGrid';

export default MembersGrid;