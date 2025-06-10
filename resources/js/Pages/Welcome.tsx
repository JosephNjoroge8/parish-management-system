import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Church, Users, Calendar, BookOpen, Heart, Shield } from 'lucide-react';

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
}: PageProps<{ laravelVersion: string; phpVersion: string }>) {
    return (
        <>
            <Head title="Welcome to OUR LADY OF CONSOLATA CATHEDRAL 
 Parish" />
            <div className="bg-gradient-to-br from-blue-50 via-white to-green-50 min-h-screen">
                {/* Header */}
                <header className="relative bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center py-6">
                            {/* Logo and Parish Name */}
                            <div className="flex items-center space-x-3">
                                <div className="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                                    <Church className="w-8 h-8 text-white" />
                                </div>
                                <div>
                                    <h1 className="text-2xl font-bold text-gray-900">OUR LADY OF CONSOLATA CATHEDRAL 
</h1>
                                    <p className="text-sm text-gray-600">Management System</p>
                                </div>
                            </div>

                            {/* Navigation */}
                            <nav className="flex items-center space-x-4">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <div className="flex space-x-3">
                                        <Link
                                            href={route('login')}
                                            className="text-gray-700 hover:text-blue-600 px-4 py-2 rounded-lg border border-gray-300 hover:border-blue-300 transition-colors"
                                        >
                                            Sign In
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            Join Parish
                                        </Link>
                                    </div>
                                )}
                            </nav>
                        </div>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="relative py-20">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <div className="max-w-3xl mx-auto">
                            <h2 className="text-5xl font-bold text-gray-900 mb-6">
                                Welcome to Our
                                <span className="text-blue-600 block">Parish Community</span>
                            </h2>
                            <p className="text-xl text-gray-600 mb-8 leading-relaxed">
                                Join our vibrant Catholic community where faith, fellowship, and service 
                                come together. Manage your parish involvement, track sacraments, and 
                                stay connected with our church family.
                            </p>
                            
                            {!auth.user && (
                                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                    <Link
                                        href={route('register')}
                                        className="bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2"
                                    >
                                        <Users className="w-5 h-5" />
                                        <span>Register as Member</span>
                                    </Link>
                                    <Link
                                        href={route('login')}
                                        className="border-2 border-blue-600 text-blue-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-50 transition-colors"
                                    >
                                        Sign In
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section className="py-16 bg-white">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h3 className="text-3xl font-bold text-gray-900 mb-4">
                                Parish Management Features
                            </h3>
                            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
                                Our comprehensive system helps manage all aspects of parish life efficiently and transparently.
                            </p>
                        </div>

                        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {/* Member Management */}
                            <div className="bg-gradient-to-br from-blue-50 to-blue-100 p-8 rounded-xl">
                                <div className="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">
                                    <Users className="w-6 h-6 text-white" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 mb-3">
                                    Member Management
                                </h4>
                                <p className="text-gray-600 mb-4">
                                    Complete member registration, family management, and demographic tracking for all parishioners.
                                </p>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• Family registration and grouping</li>
                                    <li>• Individual member profiles</li>
                                    <li>• Contact information management</li>
                                    <li>• Membership status tracking</li>
                                </ul>
                            </div>

                            {/* Sacrament Records */}
                            <div className="bg-gradient-to-br from-green-50 to-green-100 p-8 rounded-xl">
                                <div className="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">
                                    <BookOpen className="w-6 h-6 text-white" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 mb-3">
                                    Sacrament Records
                                </h4>
                                <p className="text-gray-600 mb-4">
                                    Digital tracking of all sacramental milestones for every parish member.
                                </p>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• Baptism certificates</li>
                                    <li>• First Communion records</li>
                                    <li>• Confirmation tracking</li>
                                    <li>• Marriage documentation</li>
                                </ul>
                            </div>

                            {/* Activities & Groups */}
                            <div className="bg-gradient-to-br from-purple-50 to-purple-100 p-8 rounded-xl">
                                <div className="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mb-4">
                                    <Calendar className="w-6 h-6 text-white" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 mb-3">
                                    Activities & Groups
                                </h4>
                                <p className="text-gray-600 mb-4">
                                    Organize and manage parish activities, groups, and community events.
                                </p>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• Youth groups management</li>
                                    <li>• Prayer groups coordination</li>
                                    <li>• Event participation tracking</li>
                                    <li>• Volunteer management</li>
                                </ul>
                            </div>

                            {/* Security & Privacy */}
                            <div className="bg-gradient-to-br from-red-50 to-red-100 p-8 rounded-xl">
                                <div className="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center mb-4">
                                    <Shield className="w-6 h-6 text-white" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 mb-3">
                                    Security & Privacy
                                </h4>
                                <p className="text-gray-600 mb-4">
                                    Secure data management with role-based access and privacy protection.
                                </p>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• Role-based permissions</li>
                                    <li>• Data encryption</li>
                                    <li>• Privacy compliance</li>
                                    <li>• Audit trails</li>
                                </ul>
                            </div>

                            {/* Reports & Analytics */}
                            <div className="bg-gradient-to-br from-orange-50 to-orange-100 p-8 rounded-xl">
                                <div className="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mb-4">
                                    <BookOpen className="w-6 h-6 text-white" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 mb-3">
                                    Reports & Analytics
                                </h4>
                                <p className="text-gray-600 mb-4">
                                    Comprehensive reporting and analytics for parish administration.
                                </p>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• Membership statistics</li>
                                    <li>• Sacrament reports</li>
                                    <li>• Activity participation</li>
                                    <li>• Financial tracking</li>
                                </ul>
                            </div>

                            {/* Community Building */}
                            <div className="bg-gradient-to-br from-teal-50 to-teal-100 p-8 rounded-xl">
                                <div className="w-12 h-12 bg-teal-600 rounded-lg flex items-center justify-center mb-4">
                                    <Heart className="w-6 h-6 text-white" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 mb-3">
                                    Community Building
                                </h4>
                                <p className="text-gray-600 mb-4">
                                    Foster stronger parish community through enhanced communication and engagement.
                                </p>
                                <ul className="text-sm text-gray-600 space-y-1">
                                    <li>• Directory access</li>
                                    <li>• Communication tools</li>
                                    <li>• Event notifications</li>
                                    <li>• Community involvement</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                {!auth.user && (
                    <section className="py-16 bg-blue-600">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                            <div className="max-w-3xl mx-auto">
                                <h3 className="text-3xl font-bold text-white mb-6">
                                    Ready to Join Our Parish Community?
                                </h3>
                                <p className="text-xl text-blue-100 mb-8">
                                    Register today to access parish services, track your sacramental journey, 
                                    and stay connected with our faith community.
                                </p>
                                <Link
                                    href={route('register')}
                                    className="bg-white text-blue-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-50 transition-colors inline-flex items-center space-x-2"
                                >
                                    <Users className="w-5 h-5" />
                                    <span>Start Registration</span>
                                </Link>
                            </div>
                        </div>
                    </section>
                )}

                {/* Footer */}
                <footer className="bg-gray-900 text-white py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid md:grid-cols-3 gap-8">
                            <div>
                                <div className="flex items-center space-x-3 mb-4">
                                    <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                        <Church className="w-5 h-5 text-white" />
                                    </div>
                                    <h4 className="text-lg font-semibold">OUR LADY OF CONSOLATA CATHEDRAL 
</h4>
                                </div>
                                <p className="text-gray-400">
                                    A welcoming Catholic community dedicated to faith, service, and fellowship.
                                </p>
                            </div>
                            
                            <div>
                                <h5 className="font-semibold mb-3">Quick Links</h5>
                                <ul className="space-y-2 text-gray-400">
                                    <li><a href="#" className="hover:text-white transition-colors">Mass Schedule</a></li>
                                    <li><a href="#" className="hover:text-white transition-colors">Parish Calendar</a></li>
                                    <li><a href="#" className="hover:text-white transition-colors">Contact Us</a></li>
                                    <li><a href="#" className="hover:text-white transition-colors">Donate</a></li>
                                </ul>
                            </div>
                            
                            <div>
                                <h5 className="font-semibold mb-3">Contact Information</h5>
                                <div className="text-gray-400 space-y-2">
                                    <p>123 Parish Street</p>
                                    <p>Nairobi, Kenya</p>
                                    <p>Phone: +254 700 000 000</p>
                                    <p>Email: infparish.org</p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                            <p>© 2025 St. Mary's Parish Management System. Built with Laravel v{laravelVersion} (PHP v{phpVersion})</p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
