import React, { useState } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Save, ArrowLeft, Calendar, Search, Plus, Edit, Eye, Trash2, Users, Home } from 'lucide-react';

// Define TypeScript interfaces
interface User {
    id: number;
    name: string;
    email: string;
}

interface Family {
    id: number;
    family_name: string;
    head_of_family?: string;
    phone?: string;
    email?: string;
    members_count?: number;
    registration_date?: string;
    status?: 'active' | 'inactive';
}

interface CreateMemberProps {
    auth: {
        user: User;
    };
    families?: Family[]; // Make this optional
}

// Form data interface matching Laravel expectations
interface MemberFormData {
    first_name: string;
    middle_name: string;
    last_name: string;
    email: string;
    phone: string;
    date_of_birth: string;
    gender: string;
    marital_status: string;
    occupation: string;
    address: string;
    family_id: string;
    id_number: string;
    member_type: string;
    status: string;
    baptism_date: string;
    confirmation_date: string;
    first_communion_date: string;
    notes: string;
}

export default function CreateMember({ auth, families }: CreateMemberProps) {
    const { data, setData, post, processing, errors } = useForm<MemberFormData>({
        first_name: '',
        middle_name: '',
        last_name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        gender: '',
        marital_status: 'single',
        occupation: '',
        address: '',
        family_id: '',
        id_number: '',
        member_type: 'adult',
        status: 'active',
        baptism_date: '',
        confirmation_date: '',
        first_communion_date: '',
        notes: '',
    });

    // Safely handle families - ensure it's always an array
    const safeFamilies = Array.isArray(families) ? families : [];

    // Debug log to see what we're receiving
    console.log('Families prop received:', families);
    console.log('Safe families array:', safeFamilies);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('members.store'), {
            onSuccess: () => {
                // Handle success
                console.log('Member created successfully');
            },
            onError: (errors) => {
                console.log('Validation errors:', errors);
            }
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('members.index')}
                        className="text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Add New Parish Member
                    </h2>
                </div>
            }
        >
            <Head title="Add Member" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white rounded-lg shadow-md p-6">
                        <form onSubmit={handleSubmit} className="space-y-8">
                            {/* Personal Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <span className="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">1</span>
                                    Personal Information
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label htmlFor="first_name" className="block text-sm font-medium text-gray-700 mb-2">
                                            First Name *
                                        </label>
                                        <input
                                            type="text"
                                            id="first_name"
                                            value={data.first_name}
                                            onChange={(e) => setData('first_name', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.first_name ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            required
                                        />
                                        {errors.first_name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.first_name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="middle_name" className="block text-sm font-medium text-gray-700 mb-2">
                                            Middle Name
                                        </label>
                                        <input
                                            type="text"
                                            id="middle_name"
                                            value={data.middle_name}
                                            onChange={(e) => setData('middle_name', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.middle_name ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.middle_name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.middle_name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="last_name" className="block text-sm font-medium text-gray-700 mb-2">
                                            Last Name *
                                        </label>
                                        <input
                                            type="text"
                                            id="last_name"
                                            value={data.last_name}
                                            onChange={(e) => setData('last_name', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.last_name ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            required
                                        />
                                        {errors.last_name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.last_name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="date_of_birth" className="block text-sm font-medium text-gray-700 mb-2">
                                            Date of Birth *
                                        </label>
                                        <input
                                            type="date"
                                            id="date_of_birth"
                                            value={data.date_of_birth}
                                            onChange={(e) => setData('date_of_birth', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.date_of_birth ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            required
                                        />
                                        {errors.date_of_birth && (
                                            <p className="mt-1 text-sm text-red-600">{errors.date_of_birth}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="gender" className="block text-sm font-medium text-gray-700 mb-2">
                                            Gender *
                                        </label>
                                        <select
                                            id="gender"
                                            value={data.gender}
                                            onChange={(e) => setData('gender', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.gender ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            required
                                        >
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                        {errors.gender && (
                                            <p className="mt-1 text-sm text-red-600">{errors.gender}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="id_number" className="block text-sm font-medium text-gray-700 mb-2">
                                            ID Number
                                        </label>
                                        <input
                                            type="text"
                                            id="id_number"
                                            value={data.id_number}
                                            onChange={(e) => setData('id_number', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.id_number ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            placeholder="National ID or Passport"
                                        />
                                        {errors.id_number && (
                                            <p className="mt-1 text-sm text-red-600">{errors.id_number}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Contact Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <span className="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">2</span>
                                    Contact Information
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                            Email Address
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.email ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.email && (
                                            <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                                            Phone Number
                                        </label>
                                        <input
                                            type="tel"
                                            id="phone"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                                                errors.phone ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            placeholder="+254 700 000 000"
                                        />
                                        {errors.phone && (
                                            <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                        )}
                                    </div>

                                    <div className="md:col-span-2">
                                        <label htmlFor="address" className="block text-sm font-medium text-gray-700 mb-2">
                                            Address
                                        </label>
                                        <textarea
                                            id="address"
                                            rows={3}
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Enter full address..."
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Parish Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <span className="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">3</span>
                                    Parish Information
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label htmlFor="family_id" className="block text-sm font-medium text-gray-700 mb-2">
                                            Family
                                        </label>
                                        <select
                                            id="family_id"
                                            value={data.family_id}
                                            onChange={(e) => setData('family_id', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Select Family</option>
                                            {safeFamilies.map(family => (
                                                <option key={family.id} value={family.id.toString()}>
                                                    {family.family_name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label htmlFor="member_type" className="block text-sm font-medium text-gray-700 mb-2">
                                            Member Type *
                                        </label>
                                        <select
                                            id="member_type"
                                            value={data.member_type}
                                            onChange={(e) => setData('member_type', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            required
                                        >
                                            <option value="adult">Adult</option>
                                            <option value="youth">Youth</option>
                                            <option value="child">Child</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-2">
                                            Status *
                                        </label>
                                        <select
                                            id="status"
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            required
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="transferred">Transferred</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label htmlFor="marital_status" className="block text-sm font-medium text-gray-700 mb-2">
                                            Marital Status
                                        </label>
                                        <select
                                            id="marital_status"
                                            value={data.marital_status}
                                            onChange={(e) => setData('marital_status', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="single">Single</option>
                                            <option value="married">Married</option>
                                            <option value="divorced">Divorced</option>
                                            <option value="widowed">Widowed</option>
                                        </select>
                                    </div>

                                    <div className="md:col-span-2">
                                        <label htmlFor="occupation" className="block text-sm font-medium text-gray-700 mb-2">
                                            Occupation
                                        </label>
                                        <input
                                            type="text"
                                            id="occupation"
                                            value={data.occupation}
                                            onChange={(e) => setData('occupation', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Current occupation or profession"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Sacramental Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <Calendar className="w-5 h-5 mr-2" />
                                    Sacramental Records (Optional)
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label htmlFor="baptism_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Baptism Date
                                        </label>
                                        <input
                                            type="date"
                                            id="baptism_date"
                                            value={data.baptism_date}
                                            onChange={(e) => setData('baptism_date', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div>
                                        <label htmlFor="first_communion_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            First Communion Date
                                        </label>
                                        <input
                                            type="date"
                                            id="first_communion_date"
                                            value={data.first_communion_date}
                                            onChange={(e) => setData('first_communion_date', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div>
                                        <label htmlFor="confirmation_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Confirmation Date
                                        </label>
                                        <input
                                            type="date"
                                            id="confirmation_date"
                                            value={data.confirmation_date}
                                            onChange={(e) => setData('confirmation_date', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Additional Notes */}
                            <div>
                                <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                    Additional Notes
                                </label>
                                <textarea
                                    id="notes"
                                    rows={4}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Any additional information about this member..."
                                />
                            </div>

                            {/* Submit Buttons */}
                            <div className="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                                <Link
                                    href={route('members.index')}
                                    className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg flex items-center space-x-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Save className="w-4 h-4" />
                                    <span>{processing ? 'Saving Member...' : 'Save Member'}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}