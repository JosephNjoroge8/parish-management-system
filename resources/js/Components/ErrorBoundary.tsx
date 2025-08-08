import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertTriangle } from 'lucide-react';

interface Props {
    children: ReactNode;
    fallback?: ReactNode;
}

interface State {
    hasError: boolean;
    error?: Error;
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
    }

    public render() {
        if (this.state.hasError) {
            return this.props.fallback || (
                <div className="bg-red-50 border border-red-200 rounded-lg p-6 m-4">
                    <div className="flex items-center">
                        <AlertTriangle className="h-6 w-6 text-red-600 mr-3" />
                        <div>
                            <h3 className="text-lg font-medium text-red-800">
                                Something went wrong
                            </h3>
                            <p className="text-red-600 mt-1">
                                Please refresh the page or contact support if the problem persists.
                            </p>
                            <button
                                onClick={() => window.location.reload()}
                                className="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors"
                            >
                                Refresh Page
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;