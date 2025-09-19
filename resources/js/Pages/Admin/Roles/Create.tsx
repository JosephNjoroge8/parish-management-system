import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Save, 
    ArrowLeft, 
    Shield, 
    Settings, 
    Users, 
    Eye, 
    Edit, 
    Trash2,
    Check,
    X,
    AlertCircle,
    Info,
    Lock,
    Unlock,
    Database,
    FileText,
    DollarSign,
    Activity,
    UserCheck,
    BarChart3,
    Home
} from 'lucide-react';

interface Permission {
    id: number;
    name: string;
    display_name?: string;
}

interface PermissionGroup {
    [key: string]: Permission[];
}

interface CreateRoleProps {
    permissions: PermissionGroup;
}

const getModuleIcon = (module: string) => {
    switch (module.toLowerCase()) {
        case 'members': return Users;
        case 'families': return Home;
        case 'users': return UserCheck;
        case 'sacraments': return Shield;
        case 'tithes': return DollarSign;
        case 'activities': return Activity;
        case 'groups': return Users;
        case 'reports': return BarChart3;
        case 'settings': return Settings;
        case 'dashboard': return Database;
        default: return FileText;
    }
};

const getModuleColor = (module: string) => {
    switch (module.toLowerCase()) {
        case 'members': return 'bg-blue-50 border-blue-200 text-blue-800';
        case 'families': return 'bg-green-50 border-green-200 text-green-800';
        case 'users': return 'bg-purple-50 border-purple-200 text-purple-800';
        case 'sacraments': return 'bg-yellow-50 border-yellow-200 text-yellow-800';
        case 'tithes': return 'bg-emerald-50 border-emerald-200 text-emerald-800';
        case 'activities': return 'bg-orange-50 border-orange-200 text-orange-800';
        case 'groups': return 'bg-pink-50 border-pink-200 text-pink-800';
        case 'reports': return 'bg-indigo-50 border-indigo-200 text-indigo-800';
        case 'settings': return 'bg-gray-50 border-gray-200 text-gray-800';
        case 'dashboard': return 'bg-teal-50 border-teal-200 text-teal-800';
        default: return 'bg-gray-50 border-gray-200 text-gray-800';
    }
};

const predefinedRoles = [
    {
        name: 'super-admin',
        display_name: 'Super Administrator',
        description: 'Full system access with all permissions',
        level: 5,
        permissions: '*'
    },
    {
        name: 'admin',
        display_name: 'Administrator',
        description: 'Administrative access with most permissions',
        level: 4,
        permissions: ['access *', 'manage *', 'view *', 'export *']
    },
    {
        name: 'manager',
        display_name: 'Parish Manager',
        description: 'Management level access for operations',
        level: 3,
        permissions: ['access members', 'manage members', 'access families', 'manage families', 'access sacraments', 'manage sacraments', 'access reports', 'view reports']
    },
    {
        name: 'secretary',
        display_name: 'Parish Secretary',
        description: 'Staff level access for daily operations',
        level: 2,
        permissions: ['access members', 'manage members', 'access families', 'manage families', 'access sacraments', 'create sacraments', 'edit sacraments']
    },
    {
        name: 'treasurer',
        display_name: 'Parish Treasurer',
        description: 'Financial management and reporting access',
        level: 2,
        permissions: ['access tithes', 'manage tithes', 'access reports', 'view financial reports', 'export reports']
    },
    {
        name: 'viewer',
        display_name: 'Viewer',
        description: 'Read-only access to basic information',
        level: 1,
        permissions: ['view members', 'view families', 'view sacraments', 'access dashboard']
    }
];

export default function CreateRole({ permissions }: CreateRoleProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        display_name: '',
        description: '',
        permissions: [] as string[],
        clearance_level: 1
    });

    const [selectedTemplate, setSelectedTemplate] = useState<string>('');
    const [expandedModules, setExpandedModules] = useState<string[]>(['members', 'dashboard']);
    const [selectAllModules, setSelectAllModules] = useState<{[key: string]: boolean}>({});

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.roles.store'), {
            onSuccess: () => {
                reset();
            }
        });
    };

    const handleTemplateSelect = (template: any) => {
        setSelectedTemplate(template.name);
        setData({
            ...data,
            name: template.name,
            display_name: template.display_name,
            description: template.description,
            clearance_level: template.level,
            permissions: template.permissions === '*' ? getAllPermissions() : 
                        Array.isArray(template.permissions) ? template.permissions : []
        });
    };

    const getAllPermissions = (): string[] => {
        return Object.values(permissions).flat().map(p => p.name);
    };

    const toggleModule = (module: string) => {
        if (expandedModules.includes(module)) {
            setExpandedModules(expandedModules.filter(m => m !== module));
        } else {
            setExpandedModules([...expandedModules, module]);
        }
    };

    const toggleModulePermissions = (module: string, modulePermissions: Permission[]) => {
        const permissionNames = modulePermissions.map(p => p.name);
        const currentPermissions = data.permissions || [];
        const hasAll = permissionNames.every(p => currentPermissions.includes(p));
        
        if (hasAll) {
            // Remove all module permissions
            setData('permissions', currentPermissions.filter(p => !permissionNames.includes(p)));
            setSelectAllModules({ ...selectAllModules, [module]: false });
        } else {
            // Add all module permissions
            const newPermissions = [...new Set([...currentPermissions, ...permissionNames])];
            setData('permissions', newPermissions);
            setSelectAllModules({ ...selectAllModules, [module]: true });
        }
    };

    const togglePermission = (permission: string) => {
        const currentPermissions = data.permissions || [];
        if (currentPermissions.includes(permission)) {
            setData('permissions', currentPermissions.filter(p => p !== permission));
        } else {
            setData('permissions', [...currentPermissions, permission]);
        }
    };

    const getPermissionType = (permissionName: string): string => {
        if (permissionName.startsWith('manage') || permissionName.startsWith('create') || 
            permissionName.startsWith('edit') || permissionName.startsWith('delete')) {
            return 'write';
        }
        if (permissionName.startsWith('view') || permissionName.startsWith('access')) {
            return 'read';
        }
        if (permissionName.startsWith('export')) {
            return 'export';
        }
        return 'other';
    };

    const getPermissionTypeColor = (type: string): string => {
        switch (type) {
            case 'write': return 'bg-red-100 text-red-800 border-red-200';
            case 'read': return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'export': return 'bg-green-100 text-green-800 border-green-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const permissionStats = {
        total: Object.values(permissions).flat().length,
        selected: data.permissions?.length || 0,
        readOnly: (data.permissions || []).filter(p => getPermissionType(p) === 'read').length,
        writeAccess: (data.permissions || []).filter(p => getPermissionType(p) === 'write').length,
        exportAccess: (data.permissions || []).filter(p => getPermissionType(p) === 'export').length
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link 
                            href={route('admin.roles.index')} 
                            className="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                                <Shield className="w-6 h-6 mr-2 text-blue-600" />
                                Create New Role
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Define a new role with specific permissions and access levels
                            </p>
                        </div>
                    </div>
                    <div className="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-lg">
                        {permissionStats.selected} of {permissionStats.total} permissions selected
                    </div>
                </div>
            }
        >
            <Head title="Create Role" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Form */}
                        <div className="lg:col-span-2">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Role Basic Information */}
                                <div className="bg-white shadow-sm rounded-lg p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Role Information</h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                                Role Name <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="name"
                                                value={data.name}
                                                onChange={e => setData('name', e.target.value)}
                                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                    errors.name ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                                placeholder="e.g., parish-secretary"
                                                required
                                            />
                                            {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                                            <p className="text-xs text-gray-500 mt-1">Use lowercase with hyphens (e.g., parish-manager)</p>
                                        </div>

                                        <div>
                                            <label htmlFor="display_name" className="block text-sm font-medium text-gray-700 mb-2">
                                                Display Name <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="display_name"
                                                value={data.display_name}
                                                onChange={e => setData('display_name', e.target.value)}
                                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                    errors.display_name ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                                placeholder="e.g., Parish Secretary"
                                                required
                                            />
                                            {errors.display_name && <p className="text-red-500 text-sm mt-1">{errors.display_name}</p>}
                                        </div>
                                    </div>

                                    <div className="mt-6">
                                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                            Description
                                        </label>
                                        <textarea
                                            id="description"
                                            rows={3}
                                            value={data.description}
                                            onChange={e => setData('description', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Brief description of this role's responsibilities and access level..."
                                        />
                                    </div>

                                    <div className="mt-6">
                                        <label htmlFor="clearance_level" className="block text-sm font-medium text-gray-700 mb-2">
                                            Clearance Level
                                        </label>
                                        <select
                                            id="clearance_level"
                                            value={data.clearance_level}
                                            onChange={e => setData('clearance_level', parseInt(e.target.value))}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value={1}>Level 1 - Viewer (Read-only access)</option>
                                            <option value={2}>Level 2 - Staff (Basic operations)</option>
                                            <option value={3}>Level 3 - Manager (Management access)</option>
                                            <option value={4}>Level 4 - Admin (Administrative access)</option>
                                            <option value={5}>Level 5 - Super Admin (Full access)</option>
                                        </select>
                                    </div>
                                </div>

                                {/* Permission Selection */}
                                <div className="bg-white shadow-sm rounded-lg p-6">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-medium text-gray-900">Permissions</h3>
                                        <div className="flex items-center space-x-4">
                                            <div className="text-sm text-gray-600">
                                                <span className="font-medium text-blue-600">{permissionStats.selected}</span> of {permissionStats.total} selected
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => setData('permissions', data.permissions?.length === permissionStats.total ? [] : getAllPermissions())}
                                                className="text-sm text-blue-600 hover:text-blue-800"
                                            >
                                                {data.permissions?.length === permissionStats.total ? 'Deselect All' : 'Select All'}
                                            </button>
                                        </div>
                                    </div>

                                    {/* Permission Statistics */}
                                    <div className="grid grid-cols-4 gap-4 mb-6">
                                        <div className="bg-blue-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-blue-600">{permissionStats.readOnly}</div>
                                            <div className="text-xs text-blue-800">Read Access</div>
                                        </div>
                                        <div className="bg-red-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-red-600">{permissionStats.writeAccess}</div>
                                            <div className="text-xs text-red-800">Write Access</div>
                                        </div>
                                        <div className="bg-green-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-green-600">{permissionStats.exportAccess}</div>
                                            <div className="text-xs text-green-800">Export Access</div>
                                        </div>
                                        <div className="bg-gray-50 p-3 rounded-lg text-center">
                                            <div className="text-lg font-bold text-gray-600">{permissionStats.selected}</div>
                                            <div className="text-xs text-gray-800">Total Selected</div>
                                        </div>
                                    </div>

                                    {/* Permissions by Module */}
                                    <div className="space-y-4">
                                        {Object.entries(permissions).map(([module, modulePermissions]) => {
                                            const IconComponent = getModuleIcon(module);
                                            const isExpanded = expandedModules.includes(module);
                                            const selectedInModule = modulePermissions.filter(p => data.permissions?.includes(p.name)).length;
                                            
                                            return (
                                                <div key={module} className={`border border-gray-200 rounded-lg ${getModuleColor(module)}`}>
                                                    <div 
                                                        className="p-4 cursor-pointer hover:bg-opacity-80 transition-colors"
                                                        onClick={() => toggleModule(module)}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex items-center space-x-3">
                                                                <IconComponent className="w-5 h-5" />
                                                                <h4 className="font-medium capitalize">{module}</h4>
                                                                <span className="text-sm">
                                                                    {selectedInModule}/{modulePermissions.length} selected
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center space-x-2">
                                                                <button
                                                                    type="button"
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        toggleModulePermissions(module, modulePermissions);
                                                                    }}
                                                                    className="text-xs px-2 py-1 bg-white bg-opacity-50 rounded hover:bg-opacity-75 transition-colors"
                                                                >
                                                                    {selectedInModule === modulePermissions.length ? 'Deselect All' : 'Select All'}
                                                                </button>
                                                                <div className={`transform transition-transform ${isExpanded ? 'rotate-180' : ''}`}>
                                                                    <ArrowLeft className="w-4 h-4 rotate-90" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    {isExpanded && (
                                                        <div className="px-4 pb-4">
                                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                {modulePermissions.map(permission => {
                                                                    const isSelected = data.permissions?.includes(permission.name);
                                                                    const permissionType = getPermissionType(permission.name);
                                                                    
                                                                    return (
                                                                        <label 
                                                                            key={permission.id}
                                                                            className="flex items-center space-x-2 p-2 bg-white bg-opacity-50 rounded hover:bg-opacity-75 cursor-pointer transition-colors"
                                                                        >
                                                                            <input
                                                                                type="checkbox"
                                                                                checked={isSelected}
                                                                                onChange={() => togglePermission(permission.name)}
                                                                                className="rounded text-blue-600 focus:ring-blue-500"
                                                                            />
                                                                            <span className="text-sm flex-1">{permission.name}</span>
                                                                            <span className={`text-xs px-2 py-0.5 rounded-full border ${getPermissionTypeColor(permissionType)}`}>
                                                                                {permissionType}
                                                                            </span>
                                                                        </label>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {errors.permissions && (
                                        <p className="text-red-500 text-sm mt-2">{errors.permissions}</p>
                                    )}
                                </div>

                                {/* Submit Button */}
                                <div className="flex justify-end space-x-4">
                                    <Link
                                        href={route('admin.roles.index')}
                                        className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center"
                                    >
                                        <Save className="w-4 h-4 mr-2" />
                                        {processing ? 'Creating...' : 'Create Role'}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {/* Role Templates Sidebar */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow-sm rounded-lg p-6 sticky top-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Role Templates</h3>
                                <p className="text-sm text-gray-600 mb-4">
                                    Quick start with predefined role templates
                                </p>
                                
                                <div className="space-y-3">
                                    {predefinedRoles.map(template => (
                                        <div 
                                            key={template.name}
                                            className={`p-3 border rounded-lg cursor-pointer transition-colors hover:bg-gray-50 ${
                                                selectedTemplate === template.name ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                                            }`}
                                            onClick={() => handleTemplateSelect(template)}
                                        >
                                            <div className="flex items-center justify-between mb-1">
                                                <h4 className="font-medium text-sm">{template.display_name}</h4>
                                                <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">
                                                    Level {template.level}
                                                </span>
                                            </div>
                                            <p className="text-xs text-gray-600">{template.description}</p>
                                        </div>
                                    ))}
                                </div>

                                {/* Current Role Preview */}
                                {data.name && (
                                    <div className="mt-6 p-4 bg-gray-50 rounded-lg">
                                        <h4 className="font-medium text-sm mb-2">Current Role Preview</h4>
                                        <div className="space-y-2 text-xs">
                                            <div><span className="font-medium">Name:</span> {data.name}</div>
                                            <div><span className="font-medium">Display:</span> {data.display_name}</div>
                                            <div><span className="font-medium">Level:</span> {data.clearance_level}</div>
                                            <div><span className="font-medium">Permissions:</span> {data.permissions?.length || 0}</div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
