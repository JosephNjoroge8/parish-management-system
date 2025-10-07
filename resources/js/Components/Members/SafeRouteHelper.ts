/**
 * Safe route helper for production environments
 * Handles cases where route() function might not be available
 */

import { ROUTE_MAP, type RouteKey } from './constants';

export const safeRoute = (name: RouteKey, params?: any): string => {
    try {
        // Check if route function is available globally
        if (typeof (window as any).route === 'function') {
            return (window as any).route(name, params);
        }
        
        // Fallback route generation using constants
        let url: string = ROUTE_MAP[name] || '/members';
        
        // Replace parameters in URL
        if (params) {
            if (typeof params === 'number' || typeof params === 'string') {
                url = url.replace(':id', String(params));
            } else if (typeof params === 'object' && params !== null) {
                Object.keys(params).forEach(key => {
                    url = url.replace(`:${key}`, String(params[key]));
                });
                
                // Add query parameters if they don't replace URL params
                const remainingParams = Object.entries(params).filter(([key]) => 
                    !url.includes(`:${key}`)
                );
                
                if (remainingParams.length > 0) {
                    const queryString = buildQueryString(Object.fromEntries(remainingParams));
                    url += queryString;
                }
            }
        }
        
        return url;
    } catch (error) {
        console.warn('Route generation failed, using fallback:', error);
        return '/members'; // Safe fallback
    }
};

export const buildQueryString = (params: Record<string, any>): string => {
    try {
        const searchParams = new URLSearchParams();
        
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                searchParams.append(key, String(value));
            }
        });
        
        const queryString = searchParams.toString();
        return queryString ? `?${queryString}` : '';
    } catch (error) {
        console.warn('Query string building failed:', error);
        return '';
    }
};

/**
 * Safe route helper specifically for member actions
 */
export const memberRoute = (action: 'show' | 'edit' | 'destroy', memberId: number): string => {
    switch (action) {
        case 'show':
            return safeRoute('members.show', memberId);
        case 'edit':
            return safeRoute('members.edit', memberId);
        case 'destroy':
            return safeRoute('members.destroy', memberId);
        default:
            return safeRoute('members.show', memberId);
    }
};

/**
 * Check if a route exists and is accessible
 */
export const isRouteAvailable = (name: RouteKey): boolean => {
    try {
        if (typeof (window as any).route === 'function') {
            const testRoute = (window as any).route(name);
            return typeof testRoute === 'string' && testRoute.length > 0;
        }
        return name in ROUTE_MAP;
    } catch {
        return name in ROUTE_MAP;
    }
};