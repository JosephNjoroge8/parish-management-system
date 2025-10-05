import React from 'react';
import { ChevronDown } from 'lucide-react';

export interface FormFieldProps {
    id: string;
    label: string;
    type?: 'text' | 'email' | 'tel' | 'date' | 'textarea' | 'select' | 'checkbox' | 'number';
    required?: boolean;
    placeholder?: string;
    className?: string;
    rows?: number;
    value: string | number | boolean;
    onChange: (value: string | number | boolean) => void;
    maxLength?: number;
    min?: string | number;
    max?: string | number;
    options?: Array<{ value: string | number; label: string }>;
    hasError?: boolean;
    errorMessage?: string;
    disabled?: boolean;
    helpText?: string;
}

/**
 * Unified Form Field Component - Replaces multiple duplicate form input components
 * Used across Members, Families, Sacraments, Tithes, etc.
 */
export default function FormField({
    id,
    label,
    type = 'text',
    required = false,
    placeholder = '',
    className = '',
    rows = 3,
    value,
    onChange,
    maxLength,
    min,
    max,
    options = [],
    hasError = false,
    errorMessage = '',
    disabled = false,
    helpText = '',
}: FormFieldProps) {
    const baseInputClassName = `w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors disabled:bg-gray-50 disabled:cursor-not-allowed ${
        hasError ? 'border-red-500 focus:ring-red-200' : 'border-gray-300'
    } ${className}`;

    const renderInput = () => {
        switch (type) {
            case 'textarea':
                return (
                    <textarea
                        id={id}
                        name={id}
                        value={value as string}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder={placeholder}
                        rows={rows}
                        maxLength={maxLength}
                        disabled={disabled}
                        className={baseInputClassName}
                    />
                );

            case 'select':
                return (
                    <div className="relative">
                        <select
                            id={id}
                            name={id}
                            value={value as string}
                            onChange={(e) => onChange(e.target.value)}
                            disabled={disabled}
                            className={`${baseInputClassName} pr-10 appearance-none cursor-pointer`}
                        >
                            {placeholder && (
                                <option value="" disabled>
                                    {placeholder}
                                </option>
                            )}
                            {options.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <ChevronDown className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                    </div>
                );

            case 'checkbox':
                return (
                    <div className="flex items-center">
                        <input
                            id={id}
                            name={id}
                            type="checkbox"
                            checked={value as boolean}
                            onChange={(e) => onChange(e.target.checked)}
                            disabled={disabled}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:cursor-not-allowed"
                        />
                        <label htmlFor={id} className="ml-2 block text-sm text-gray-900">
                            {label} {required && <span className="text-red-500">*</span>}
                        </label>
                    </div>
                );

            case 'number':
                return (
                    <input
                        id={id}
                        name={id}
                        type="number"
                        value={value as number}
                        onChange={(e) => onChange(parseFloat(e.target.value) || 0)}
                        placeholder={placeholder}
                        min={min}
                        max={max}
                        disabled={disabled}
                        className={baseInputClassName}
                    />
                );

            default:
                return (
                    <input
                        id={id}
                        name={id}
                        type={type}
                        value={value as string}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder={placeholder}
                        maxLength={maxLength}
                        min={min as string}
                        max={max as string}
                        disabled={disabled}
                        className={baseInputClassName}
                    />
                );
        }
    };

    if (type === 'checkbox') {
        return (
            <div className="space-y-1">
                {renderInput()}
                {hasError && errorMessage && (
                    <p className="text-sm text-red-600">{errorMessage}</p>
                )}
                {helpText && !hasError && (
                    <p className="text-sm text-gray-500">{helpText}</p>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-1">
            <label htmlFor={id} className="block text-sm font-medium text-gray-700">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            {renderInput()}
            {hasError && errorMessage && (
                <p className="text-sm text-red-600">{errorMessage}</p>
            )}
            {helpText && !hasError && (
                <p className="text-sm text-gray-500">{helpText}</p>
            )}
        </div>
    );
}