import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Church, User, Mail, Phone, Lock, Calendar } from 'lucide-react';

interface RegistrationData {
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    date_of_birth: string;
    gender: string;
    address: string;
    occupation: string;
    emergency_contact: string;
    emergency_phone: string;
    how_did_you_hear: string;
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        date_of_birth: '',
        gender: '',
        address: '',
        occupation: '',
        emergency_contact: '',
        emergency_phone: '',
        how_did_you_hear: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
            onSuccess: () => {
                // User will be redirected to dashboard automatically
                console.log('Registration successful! Redirecting to dashboard...');
            },
            onError: (errors) => {
                console.log('Registration errors:', errors);
            }
        });
    };

    return (
        <GuestLayout>
            <Head title="Join Our Parish" />

            <div className="w-full max-w-4xl mx-auto">
                {/* Header */}
                <div className="text-center mb-8">
                    <div className="flex justify-center mb-4">
                        <div className="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
                            <Church className="w-10 h-10 text-white" />
                        </div>
                    </div>
                    <h1 className="text-3xl font-bold text-gray-900 mb-2">Join Our Parish Community</h1>
                    <p className="text-gray-600">Register to become a member of St. Mary's Parish</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-8">
                    {/* Personal Information */}
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <User className="w-5 h-5 mr-2 text-blue-600" />
                            Personal Information
                        </h3>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="name" value="Full Name *" />
                                <TextInput
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    autoComplete="name"
                                    isFocused={true}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="date_of_birth" value="Date of Birth *" />
                                <TextInput
                                    id="date_of_birth"
                                    type="date"
                                    name="date_of_birth"
                                    value={data.date_of_birth}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('date_of_birth', e.target.value)}
                                    required
                                />
                                <InputError message={errors.date_of_birth} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="gender" value="Gender *" />
                                <select
                                    id="gender"
                                    name="gender"
                                    value={data.gender}
                                    onChange={(e) => setData('gender', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <InputError message={errors.gender} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="occupation" value="Occupation" />
                                <TextInput
                                    id="occupation"
                                    name="occupation"
                                    value={data.occupation}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('occupation', e.target.value)}
                                    placeholder="Your profession or occupation"
                                />
                                <InputError message={errors.occupation} className="mt-2" />
                            </div>

                            <div className="md:col-span-2">
                                <InputLabel htmlFor="address" value="Address" />
                                <textarea
                                    id="address"
                                    name="address"
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows={3}
                                    placeholder="Your residential address"
                                />
                                <InputError message={errors.address} className="mt-2" />
                            </div>
                        </div>
                    </div>

                    {/* Contact Information */}
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <Phone className="w-5 h-5 mr-2 text-blue-600" />
                            Contact Information
                        </h3>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="email" value="Email Address *" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    autoComplete="username"
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="phone" value="Phone Number" />
                                <TextInput
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    value={data.phone}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('phone', e.target.value)}
                                    placeholder="+254 700 000 000"
                                />
                                <InputError message={errors.phone} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="emergency_contact" value="Emergency Contact Name" />
                                <TextInput
                                    id="emergency_contact"
                                    name="emergency_contact"
                                    value={data.emergency_contact}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('emergency_contact', e.target.value)}
                                    placeholder="Next of kin or emergency contact"
                                />
                                <InputError message={errors.emergency_contact} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="emergency_phone" value="Emergency Contact Phone" />
                                <TextInput
                                    id="emergency_phone"
                                    type="tel"
                                    name="emergency_phone"
                                    value={data.emergency_phone}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('emergency_phone', e.target.value)}
                                    placeholder="+254 700 000 000"
                                />
                                <InputError message={errors.emergency_phone} className="mt-2" />
                            </div>
                        </div>
                    </div>

                    {/* Account Security */}
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <Lock className="w-5 h-5 mr-2 text-blue-600" />
                            Account Security
                        </h3>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="password" value="Password *" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="password_confirmation" value="Confirm Password *" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    value={data.password_confirmation}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    required
                                />
                                <InputError message={errors.password_confirmation} className="mt-2" />
                            </div>
                        </div>
                    </div>

                    {/* Parish Information */}
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <Church className="w-5 h-5 mr-2 text-blue-600" />
                            Parish Information
                        </h3>
                        
                        <div>
                            <InputLabel htmlFor="how_did_you_hear" value="How did you hear about our parish?" />
                            <select
                                id="how_did_you_hear"
                                name="how_did_you_hear"
                                value={data.how_did_you_hear}
                                onChange={(e) => setData('how_did_you_hear', e.target.value)}
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Select an option</option>
                                <option value="friend_family">Friend or Family Member</option>
                                <option value="social_media">Social Media</option>
                                <option value="website">Parish Website</option>
                                <option value="walking_by">Walking By</option>
                                <option value="moved_to_area">Moved to the Area</option>
                                <option value="other">Other</option>
                            </select>
                            <InputError message={errors.how_did_you_hear} className="mt-2" />
                        </div>
                    </div>

                    {/* Submit */}
                    <div className="flex items-center justify-between pt-6 border-t border-gray-200">
                        <Link
                            href={route('login')}
                            className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Already have an account? Sign in
                        </Link>

                        <PrimaryButton 
                            className="ms-4 flex items-center space-x-2" 
                            disabled={processing}
                        >
                            <Church className="w-4 h-4" />
                            <span>{processing ? 'Registering...' : 'Join Parish'}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
