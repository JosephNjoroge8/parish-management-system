import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertTriangle, RefreshCcw, Home } from 'lucide-react';

interface Props {
    children: ReactNode;
    fallback?: ReactNode;
}

interface State {
    hasError: boolean;
    error?: Error;
    errorInfo?: ErrorInfo;
}

class ErrorBoundary extends Component<Props, State> {
    public state: State = {
        hasError: false
    };

    public static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('Uncaught error:', error, errorInfo);
        
        this.setState({
            error,
            errorInfo
        });

        // Report to error tracking service in production
        if (process.env.NODE_ENV === 'production') {
            // Add your error reporting service here
            console.error('Production error:', {
                error: error.message,
                stack: error.stack,
                componentStack: errorInfo.componentStack
            });
        }
    }

    private handleRefresh = () => {
        window.location.reload();
    };

    private handleGoHome = () => {
        window.location.href = '/dashboard';
    };

    private handleRetry = () => {
        this.setState({ hasError: false, error: undefined, errorInfo: undefined });
    };

    public render() {
        if (this.state.hasError) {
            return this.props.fallback || (
                <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                    <div className="sm:mx-auto sm:w-full sm:max-w-md">
                        <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                            <div className="flex items-center justify-center">
                                <AlertTriangle className="h-12 w-12 text-red-600" />
                            </div>
                            
                            <div className="mt-6 text-center">
                                <h2 className="text-2xl font-extrabold text-gray-900">
                                    Something went wrong
                                </h2>
                                <p className="mt-2 text-sm text-gray-600">
                                    We encountered an unexpected error. Please try one of the options below.
                                </p>
                            </div>

                            {process.env.NODE_ENV === 'development' && this.state.error && (
                                <div className="mt-4">
                                    <details className="bg-red-50 border border-red-200 rounded p-3">
                                        <summary className="text-sm font-medium text-red-800 cursor-pointer">
                                            Error Details (Development Only)
                                        </summary>
                                        <div className="mt-2 text-xs text-red-700 font-mono">
                                            <div>
                                                <strong>Error:</strong> {this.state.error.message}
                                            </div>
                                            {this.state.error.stack && (
                                                <div className="mt-2">
                                                    <strong>Stack:</strong>
                                                    <pre className="whitespace-pre-wrap">
                                                        {this.state.error.stack}
                                                    </pre>
                                                </div>
                                            )}
                                        </div>
                                    </details>
                                </div>
                            )}

                            <div className="mt-6 space-y-3">
                                <button
                                    onClick={this.handleRetry}
                                    className="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                >
                                    <RefreshCcw className="h-4 w-4 mr-2" />
                                    Try Again
                                </button>

                                <button
                                    onClick={this.handleRefresh}
                                    className="w-full flex justify-center items-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                >
                                    <RefreshCcw className="h-4 w-4 mr-2" />
                                    Refresh Page
                                </button>

                                <button
                                    onClick={this.handleGoHome}
                                    className="w-full flex justify-center items-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                >
                                    <Home className="h-4 w-4 mr-2" />
                                    Go to Dashboard
                                </button>
                            </div>

                            <div className="mt-4 text-center">
                                <p className="text-xs text-gray-500">
                                    If this problem persists, please contact system administrator.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;