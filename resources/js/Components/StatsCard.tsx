import React from 'react';
import { LucideIcon } from 'lucide-react';

interface StatsCardProps {
    title: string;
    value: number | string | undefined | null;
    icon: LucideIcon;
    trend?: {
        value: number;
        isPositive: boolean;
    };
    formatAsCurrency?: boolean;
    className?: string;
    subtitle?: string;
}

const StatsCard: React.FC<StatsCardProps> = ({
    title,
    value,
    icon: Icon,
    trend,
    formatAsCurrency = false,
    className = "",
    subtitle
}) => {
    const safeValue = value ?? 0;
    const numericValue = typeof safeValue === 'string' ? parseFloat(safeValue) || 0 : safeValue;
    
    const formatValue = (val: number): string => {
        try {
            if (formatAsCurrency) {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                }).format(val);
            }
            
            return val.toLocaleString();
        } catch (error) {
            return formatAsCurrency ? `$${val.toFixed(2)}` : val.toString();
        }
    };

    const displayValue = formatValue(numericValue);

    return (
        <div className={`bg-white rounded-xl shadow-sm p-6 border transition-all duration-200 hover:shadow-md ${className}`}>
            <div className="flex items-center justify-between">
                <div className="flex-1">
                    <p className="text-sm font-medium text-gray-600 mb-1">
                        {title}
                    </p>
                    <p className="text-3xl font-bold text-gray-900 mb-1">
                        {displayValue}
                    </p>
                    {subtitle && (
                        <p className="text-xs text-gray-500">
                            {subtitle}
                        </p>
                    )}
                    {trend && (
                        <div className={`flex items-center mt-2 text-sm ${trend.isPositive ? 'text-emerald-600' : 'text-red-600'}`}>
                            <span className="mr-1">
                                {trend.isPositive ? '↗' : '↘'}
                            </span>
                            <span className="font-medium">
                                {Math.abs(trend.value)}%
                            </span>
                            <span className="text-gray-500 ml-1">
                                vs last month
                            </span>
                        </div>
                    )}
                </div>
                <div className="flex-shrink-0">
                    <div className="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg flex items-center justify-center">
                        <Icon className="h-6 w-6 text-white" />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default StatsCard;