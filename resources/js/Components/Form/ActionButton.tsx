import React from 'react';
import { Loader2 } from 'lucide-react';

export interface ActionButtonProps {
    variant?: 'primary' | 'secondary' | 'danger' | 'success' | 'warning';
    size?: 'sm' | 'md' | 'lg';
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
    icon?: React.ComponentType<{ className?: string }>;
    children: React.ReactNode;
    onClick?: () => void;
    className?: string;
}

/**
 * Unified Action Button Component - Replaces PrimaryButton, SecondaryButton, DangerButton
 * Provides consistent styling and behavior across the application
 */
export default function ActionButton({
    variant = 'primary',
    size = 'md',
    type = 'button',
    disabled = false,
    loading = false,
    icon: Icon,
    children,
    onClick,
    className = '',
}: ActionButtonProps) {
    const isDisabled = disabled || loading;

    // Base styles
    const baseStyles = 'inline-flex items-center justify-center rounded-lg font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    // Size variants
    const sizeStyles = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
    };

    // Color variants
    const variantStyles = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 border border-transparent',
        secondary: 'bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-500 border border-gray-300',
        danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 border border-transparent',
        success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 border border-transparent',
        warning: 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500 border border-transparent',
    };

    const buttonClassName = `${baseStyles} ${sizeStyles[size]} ${variantStyles[variant]} ${className}`;

    return (
        <button
            type={type}
            disabled={isDisabled}
            onClick={onClick}
            className={buttonClassName}
        >
            {loading ? (
                <Loader2 className="w-4 h-4 animate-spin mr-2" />
            ) : Icon ? (
                <Icon className="w-4 h-4 mr-2" />
            ) : null}
            {children}
        </button>
    );
}