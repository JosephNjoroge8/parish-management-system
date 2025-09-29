import { Link, Head } from '@inertiajs/react';
import { PageProps } from '@/types';
import { 
    Users, 
    Home, 
    Calendar, 
    DollarSign, 
    Star, 
    BarChart3,
    Shield,
    Clock,
    CheckCircle
} from 'lucide-react';

interface WelcomeProps extends PageProps {
    laravelVersion: string;
    phpVersion: string;
}

const FeatureCard = ({ 
    title, 
    description, 
    icon: Icon,
    color = 'blue'
}: {
    title: string;
    description: string;
    icon: any;
    color?: string;
}) => {
    const colorClasses = {
        blue: 'bg-blue-50 text-blue-600',
        green: 'bg-green-50 text-green-600',
        purple: 'bg-purple-50 text-purple-600',
        orange: 'bg-orange-50 text-orange-600',
        emerald: 'bg-emerald-50 text-emerald-600',
        indigo: 'bg-indigo-50 text-indigo-600',
    };

    return (
        <div className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div className={`inline-flex p-3 rounded-lg ${colorClasses[color as keyof typeof colorClasses] || colorClasses.blue} mb-4`}>
                <Icon className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
            <p className="text-gray-600">{description}</p>
        </div>
    );
};

export default function Welcome({ auth, laravelVersion, phpVersion }: WelcomeProps) {
    const features = [
        {
            title: "Member Management",
            description: "Comprehensive member registration, tracking, and communication tools for your parish community.",
            icon: Users,
            color: "blue"
        },
        {
            title: "Family Registration",
            description: "Organize and manage family units with detailed records and relationship tracking.",
            icon: Home,
            color: "green"
        },
        {
            title: "Sacrament Records",
            description: "Keep detailed records of baptisms, confirmations, marriages, and other sacraments.",
            icon: Star,
            color: "purple"
        },
        {
            title: "Financial Management",
            description: "Track tithes, offerings, and parish finances with comprehensive reporting tools.",
            icon: DollarSign,
            color: "emerald"
        },
        {
            title: "Event Planning",
            description: "Schedule and manage parish events, masses, and community activities.",
            icon: Calendar,
            color: "orange"
        },
        {
            title: "Detailed Reports",
            description: "Generate comprehensive reports for membership, finances, and parish activities.",
            icon: BarChart3,
            color: "indigo"
        }
    ];

    const benefits = [
        "Streamlined member registration and management",
        "Comprehensive family and relationship tracking",
        "Secure financial record keeping",
        "Automated sacrament certificate generation",
        "Real-time reporting and analytics",
        "Role-based access control",
        "Data backup and security",
        "Multi-user collaboration"
    ];

    return (
        <>
            <Head title="Welcome to Parish Management System" />
            <div className="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
                {/* Header */}
                <div className="relative min-h-screen flex flex-col items-center justify-center selection:bg-blue-500 selection:text-white">
                    <div className="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                        {/* Navigation */}
                        <header className="absolute top-0 right-0 p-6 text-right">
                            <div className="space-x-4">
                                <Link
                                    href={route('login')}
                                    className="rounded-md px-4 py-2 text-blue-600 hover:text-blue-800 transition duration-300"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('login')}
                                    className="rounded-md px-6 py-3 bg-blue-600 text-white hover:bg-blue-700 transition duration-300 shadow-lg"
                                >
                                    Access System
                                </Link>
                            </div>
                        </header>

                        {/* Hero Section */}
                        <main className="mt-16">
                            <div className="text-center">
                                <div className="flex justify-center mb-8">
                                    <div className="bg-blue-600 p-4 rounded-full">
                                        <Home className="w-16 h-16 text-white" />
                                    </div>
                                </div>
                                
                                <h1 className="text-5xl font-bold text-gray-900 mb-6">
                                    Parish Management System
                                </h1>
                                
                                <p className="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                                    A comprehensive solution for managing your parish community, 
                                    members, families, sacraments, and financial records all in one secure place.
                                    Please log in to access your dashboard.
                                </p>

                                <div className="flex justify-center space-x-4 mb-16">
                                    <Link
                                        href={route('login')}
                                        className="bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 transition duration-300 shadow-lg"
                                    >
                                        Access Dashboard
                                    </Link>
                                    <Link
                                        href="#features"
                                        className="bg-white text-blue-600 border-2 border-blue-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-50 transition duration-300"
                                    >
                                        Learn More
                                    </Link>
                                </div>
                            </div>
                        </main>

                        {/* Features Section */}
                        <section id="features" className="py-16">
                            <div className="text-center mb-12">
                                <h2 className="text-3xl font-bold text-gray-900 mb-4">
                                    Powerful Features for Parish Management
                                </h2>
                                <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                                    Everything you need to efficiently manage your parish community 
                                    and maintain accurate records.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                {features.map((feature, index) => (
                                    <FeatureCard
                                        key={index}
                                        title={feature.title}
                                        description={feature.description}
                                        icon={feature.icon}
                                        color={feature.color}
                                    />
                                ))}
                            </div>
                        </section>

                        {/* Benefits Section */}
                        <section className="py-16 bg-white rounded-lg shadow-lg mb-16">
                            <div className="px-8">
                                <div className="text-center mb-12">
                                    <h2 className="text-3xl font-bold text-gray-900 mb-4">
                                        Why Choose Our Parish Management System?
                                    </h2>
                                    <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                                        Built specifically for parish communities with features that matter most.
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {benefits.map((benefit, index) => (
                                        <div key={index} className="flex items-start space-x-3">
                                            <CheckCircle className="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" />
                                            <p className="text-gray-700">{benefit}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </section>

                        {/* Security Section */}
                        <section className="py-16">
                            <div className="bg-blue-50 rounded-lg p-8 text-center">
                                <Shield className="w-12 h-12 text-blue-600 mx-auto mb-4" />
                                <h3 className="text-2xl font-bold text-gray-900 mb-4">
                                    Secure & Reliable
                                </h3>
                                <p className="text-gray-600 max-w-2xl mx-auto">
                                    Built with security in mind. Your parish data is protected with 
                                    industry-standard encryption and regular backups. Role-based access 
                                    ensures only authorized personnel can access sensitive information.
                                </p>
                            </div>
                        </section>

                        {/* CTA Section */}
                        
                        {/* Footer */}
                        <footer className="bg-gradient-to-r from-blue-600 to-purple-600 p-8 text-white">
                             
                            <p className="mt-2 text-center">
                                © 2024 Parish Management System. All rights reserved.
                            </p>    
                 
                        </footer>
                    </div>
                </div>
            </div>
        </>
    );
}
