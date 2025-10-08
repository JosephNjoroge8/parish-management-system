/**
 * Members Component Constants
 * Centralized configuration for all Members-related components
 */

// Member Status Configuration
export const MEMBER_STATUS_CONFIG = {
    active: {
        label: 'Active',
        bgColor: 'bg-green-50',
        textColor: 'text-green-700',
        borderColor: 'border-green-200',
        iconColor: 'text-green-600',
    },
    inactive: {
        label: 'Inactive',
        bgColor: 'bg-yellow-50',
        textColor: 'text-yellow-700',
        borderColor: 'border-yellow-200',
        iconColor: 'text-yellow-600',
    },
    transferred: {
        label: 'Transferred',
        bgColor: 'bg-blue-50',
        textColor: 'text-blue-700',
        borderColor: 'border-blue-200',
        iconColor: 'text-blue-600',
    },
    deceased: {
        label: 'Deceased',
        bgColor: 'bg-gray-50',
        textColor: 'text-gray-700',
        borderColor: 'border-gray-200',
        iconColor: 'text-gray-600',
    },
} as const;

// Default pagination settings
export const PAGINATION_DEFAULTS = {
    perPage: 15,
    maxResults: 1000,
    showPerPageOptions: [10, 15, 25, 50, 100],
} as const;

// Search and filter settings
export const SEARCH_DEFAULTS = {
    debounceMs: 300,
    minSearchLength: 2,
    maxSearchLength: 100,
} as const;

// Route mapping for fallbacks (based on actual Laravel routes)
export const ROUTE_MAP = {
    'members.index': '/members',
    'members.create': '/members/create',
    'members.show': '/members/:id',
    'members.edit': '/members/:id/edit',
    'members.destroy': '/members/:id',
    'members.update': '/members/:id',
    'members.update.patch': '/members/:id',
    'members.update-status': '/members/:id/update-status',
    'members.toggle-status': '/members/:id/toggle-status',
    'members.bulk-delete': '/members/bulk-delete',
    'members.export': '/members/export',
    'members.import': '/members/import',
    'members.stats': '/members/stats',
    'members.statistics': '/members/statistics',
} as const;

// Statistics card configuration
export const STATS_CARDS_CONFIG = [
    {
        key: 'total_members',
        title: 'Total Members',
        icon: 'Users',
        color: 'blue',
        description: 'All registered members',
    },
    {
        key: 'active_members',
        title: 'Active Members',
        icon: 'UserCheck',
        color: 'green',
        description: 'Currently active members',
    },
    {
        key: 'new_this_month',
        title: 'New This Month',
        icon: 'UserPlus',
        color: 'purple',
        description: 'New registrations this month',
    },
    {
        key: 'active_percentage',
        title: 'Active Rate',
        icon: 'TrendingUp',
        color: 'indigo',
        description: 'Percentage of active members',
        isPercentage: true,
    },
] as const;

// Color schemes for consistent theming
export const COLOR_SCHEMES = {
    blue: {
        bg: 'bg-blue-50',
        text: 'text-blue-700',
        border: 'border-blue-200',
        button: 'bg-blue-600 hover:bg-blue-700',
        icon: 'text-blue-600',
    },
    green: {
        bg: 'bg-green-50',
        text: 'text-green-700',
        border: 'border-green-200',
        button: 'bg-green-600 hover:bg-green-700',
        icon: 'text-green-600',
    },
    purple: {
        bg: 'bg-purple-50',
        text: 'text-purple-700',
        border: 'border-purple-200',
        button: 'bg-purple-600 hover:bg-purple-700',
        icon: 'text-purple-600',
    },
    indigo: {
        bg: 'bg-indigo-50',
        text: 'text-indigo-700',
        border: 'border-indigo-200',
        button: 'bg-indigo-600 hover:bg-indigo-700',
        icon: 'text-indigo-600',
    },
    yellow: {
        bg: 'bg-yellow-50',
        text: 'text-yellow-700',
        border: 'border-yellow-200',
        button: 'bg-yellow-600 hover:bg-yellow-700',
        icon: 'text-yellow-600',
    },
    red: {
        bg: 'bg-red-50',
        text: 'text-red-700',
        border: 'border-red-200',
        button: 'bg-red-600 hover:bg-red-700',
        icon: 'text-red-600',
    },
    gray: {
        bg: 'bg-gray-50',
        text: 'text-gray-700',
        border: 'border-gray-200',
        button: 'bg-gray-600 hover:bg-gray-700',
        icon: 'text-gray-600',
    },
} as const;

// Loading states
export const LOADING_STATES = {
    skeleton: {
        height: 'h-4',
        width: 'w-full',
        bg: 'bg-gray-200',
        animate: 'animate-pulse',
    },
    spinner: {
        size: 'w-5 h-5',
        color: 'text-indigo-600',
        animate: 'animate-spin',
    },
} as const;

// Performance settings
export const PERFORMANCE_CONFIG = {
    DEBOUNCE_DELAY: 300,
    MAX_RETRIES: 3,
    RETRY_DELAY: 1000,
    REQUEST_TIMEOUT: 30000,
    BULK_OPERATION_BATCH_SIZE: 50,
} as const;

// API endpoints validation
export const ROUTE_VALIDATION = {
    REQUIRED_PARAMS: {
        'members.show': ['id'],
        'members.edit': ['id'],
        'members.destroy': ['id'],
        'members.update-status': ['id'],
    },
    OPTIONAL_PARAMS: {
        'members.index': ['search', 'page', 'per_page', 'membership_status', 'local_church', 'church_group', 'age_group'],
        'members.export': ['format', 'search', 'membership_status', 'local_church', 'church_group', 'age_group'],
    }
} as const;
export const ERROR_MESSAGES = {
    LOAD_FAILED: 'Failed to load members data. Please try again.',
    SEARCH_FAILED: 'Search failed. Please check your connection and try again.',
    DELETE_FAILED: 'Failed to delete member. Please try again.',
    UPDATE_FAILED: 'Failed to update member status. Please try again.',
    EXPORT_FAILED: 'Failed to export data. Please try again.',
    IMPORT_FAILED: 'Failed to import data. Please check your file and try again.',
    NETWORK_ERROR: 'Network error. Please check your connection.',
    PERMISSION_DENIED: 'You do not have permission to perform this action.',
} as const;

// Success messages
export const SUCCESS_MESSAGES = {
    MEMBER_CREATED: 'Member successfully created!',
    MEMBER_UPDATED: 'Member successfully updated!',
    MEMBER_DELETED: 'Member successfully deleted!',
    STATUS_UPDATED: 'Member status successfully updated!',
    BULK_DELETED: 'Selected members successfully deleted!',
    DATA_EXPORTED: 'Data successfully exported!',
    DATA_IMPORTED: 'Data successfully imported!',
} as const;

export type MemberStatus = keyof typeof MEMBER_STATUS_CONFIG;
export type ColorScheme = keyof typeof COLOR_SCHEMES;
export type RouteKey = keyof typeof ROUTE_MAP;