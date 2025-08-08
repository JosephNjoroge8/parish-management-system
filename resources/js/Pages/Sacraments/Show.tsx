import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeft, Calendar, MapPin, User, FileText, Users, Edit, Church, Crown, Cross, Heart, Baby } from 'lucide-react';
import { PageProps, User as UserType } from '@/types';

interface Member {
    id: number;
    name: string;
    full_name: string;
    id_number?: string;
    date_of_birth?: string;
    gender?: string;
    phone?: string;
    email?: string;
}

interface Sacrament {
    id: number;
    member_id_display: string;
    member: Member;
    sacrament_type: 'baptism' | 'eucharist' | 'confirmation' | 'reconciliation' | 'anointing' | 'marriage' | 'holy_orders';
    date_administered: string;
    administered_by: string;
    location: string;
    certificate_number?: string;
    witness_1?: string;
    witness_2?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface ShowSacramentProps extends PageProps {
    auth: {
        user: UserType;
    };
    sacrament: Sacrament;
}

const sacramentIcons = {
    baptism: Baby,
    eucharist: Cross,
    confirmation: Crown,
    reconciliation: Heart,
    anointing: Cross,
    marriage: Heart,
    holy_orders: Church,
};

const sacramentLabels = {
    baptism: 'Baptism',
    eucharist: 'First Holy Communion',
    confirmation: 'Confirmation',
    reconciliation: 'Reconciliation',
    anointing: 'Anointing of the Sick',
    marriage: 'Marriage',
    holy_orders: 'Holy Orders',
};

const sacramentColors = {
    baptism: 'text-blue-600 bg-blue-100',
    eucharist: 'text-green-600 bg-green-100',
    confirmation: 'text-purple-600 bg-purple-100',
    reconciliation: 'text-orange-600 bg-orange-100',
    anointing: 'text-yellow-600 bg-yellow-100',
    marriage: 'text-pink-600 bg-pink-100',
    holy_orders: 'text-indigo-600 bg-indigo-100',
};

export default function ShowSacrament({ auth, sacrament }: ShowSacramentProps) {
    const Icon = sacramentIcons[sacrament.sacrament_type];
    const colorClasses = sacramentColors[sacrament.sacrament_type];
    const label = sacramentLabels[sacrament.sacrament_type];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center space-x-4">
                    <Link 
                        href={route('sacraments.index')} 
                        className="text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Sacramental Record Details
                        </h2>
                        <p className="text-sm text-gray-600">
                            {label} for {sacrament.member.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`${label} - ${sacrament.member.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        {/* Header Section */}
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    <div className={`p-3 rounded-full ${colorClasses.split(' ')[1]}`}>
                                        <Icon className={`w-8 h-8 ${colorClasses.split(' ')[0]}`} />
                                    </div>
                                    <div>
                                        <h3 className="text-2xl font-bold text-gray-900">
                                            {label}
                                        </h3>
                                        <p className="text-lg text-gray-600">
                                            {sacrament.member.full_name}
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    href={route('sacraments.edit', sacrament.id)}
                                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors"
                                >
                                    <Edit className="w-4 h-4" />
                                    <span>Edit</span>
                                </Link>
                            </div>
                        </div>

                        {/* Main Content */}
                        <div className="p-6">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                {/* Member Information */}
                                <div className="space-y-6">
                                    <div>
                                        <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <Users className="w-5 h-5 mr-2 text-blue-600" />
                                            Member Information
                                        </h4>
                                        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Full Name:</span>
                                                <span className="text-gray-900">{sacrament.member.full_name}</span>
                                            </div>
                                            {sacrament.member.id_number && (
                                                <div className="flex justify-between">
                                                    <span className="font-medium text-gray-700">ID Number:</span>
                                                    <span className="text-gray-900">{sacrament.member.id_number}</span>
                                                </div>
                                            )}
                                            {sacrament.member.date_of_birth && (
                                                <div className="flex justify-between">
                                                    <span className="font-medium text-gray-700">Date of Birth:</span>
                                                    <span className="text-gray-900">
                                                        {new Date(sacrament.member.date_of_birth).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            )}
                                            {sacrament.member.gender && (
                                                <div className="flex justify-between">
                                                    <span className="font-medium text-gray-700">Gender:</span>
                                                    <span className="text-gray-900 capitalize">{sacrament.member.gender}</span>
                                                </div>
                                            )}
                                            {sacrament.member.phone && (
                                                <div className="flex justify-between">
                                                    <span className="font-medium text-gray-700">Phone:</span>
                                                    <span className="text-gray-900">{sacrament.member.phone}</span>
                                                </div>
                                            )}
                                            {sacrament.member.email && (
                                                <div className="flex justify-between">
                                                    <span className="font-medium text-gray-700">Email:</span>
                                                    <span className="text-gray-900">{sacrament.member.email}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Sacrament Details */}
                                <div className="space-y-6">
                                    <div>
                                        <h4 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <Church className="w-5 h-5 mr-2 text-purple-600" />
                                            Sacrament Details
                                        </h4>
                                        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Date Administered:</span>
                                                <span className="text-gray-900 flex items-center">
                                                    <Calendar className="w-4 h-4 mr-1" />
                                                    {new Date(sacrament.date_administered).toLocaleDateString()}
                                                </span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Administered By:</span>
                                                <span className="text-gray-900 flex items-center">
                                                    <User className="w-4 h-4 mr-1" />
                                                    {sacrament.administered_by}
                                                </span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="font-medium text-gray-700">Location:</span>
                                                <span className="text-gray-900 flex items-center">
                                                    <MapPin className="w-4 h-4 mr-1" />
                                                    {sacrament.location}
                                                </span>
                                            </div>
                                            {sacrament.certificate_number && (
                                                <div className="flex justify-between">
                                                    <span className="font-medium text-gray-700">Certificate Number:</span>
                                                    <span className="text-gray-900 flex items-center">
                                                        <FileText className="w-4 h-4 mr-1" />
                                                        {sacrament.certificate_number}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Witnesses */}
                                    {(sacrament.witness_1 || sacrament.witness_2) && (
                                        <div>
                                            <h4 className="text-lg font-semibold text-gray-900 mb-4">
                                                Witnesses
                                            </h4>
                                            <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                                {sacrament.witness_1 && (
                                                    <div className="flex justify-between">
                                                        <span className="font-medium text-gray-700">Witness 1:</span>
                                                        <span className="text-gray-900">{sacrament.witness_1}</span>
                                                    </div>
                                                )}
                                                {sacrament.witness_2 && (
                                                    <div className="flex justify-between">
                                                        <span className="font-medium text-gray-700">Witness 2:</span>
                                                        <span className="text-gray-900">{sacrament.witness_2}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Notes Section */}
                            {sacrament.notes && (
                                <div className="mt-8">
                                    <h4 className="text-lg font-semibold text-gray-900 mb-4">
                                        Additional Notes
                                    </h4>
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <p className="text-gray-900 whitespace-pre-wrap">{sacrament.notes}</p>
                                    </div>
                                </div>
                            )}

                            {/* Record Information */}
                            <div className="mt-8 pt-8 border-t border-gray-200">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">
                                    Record Information
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                    <div>
                                        <span className="font-medium">Created:</span> {new Date(sacrament.created_at).toLocaleString()}
                                    </div>
                                    <div>
                                        <span className="font-medium">Last Updated:</span> {new Date(sacrament.updated_at).toLocaleString()}
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
