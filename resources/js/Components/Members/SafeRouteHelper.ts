/**
 * Safe route helper for production environments
 * Handles cases where route() function might not be available
 */

import { ROUTE_MAP, type RouteKey } from './constants';

// Cache Ziggy availability to avoid repeated checks
let ziggyAvailable: boolean | null = null;
let lastZiggyCheck = 0;
const ZIGGY_CHECK_INTERVAL = 5000; // Check every 5 seconds

/**
 * Check if Ziggy route helper is available and working
 */
const isZiggyAvailable = (): boolean => {
    // Use cached result if recent
    const now = Date.now();
    if (ziggyAvailable !== null && (now - lastZiggyCheck) < ZIGGY_CHECK_INTERVAL) {
        return ziggyAvailable;
    }

    try {
        if (typeof window === 'undefined') {
            ziggyAvailable = false;
            lastZiggyCheck = now;
            return false;
        }
        
        // Check for global route function (provided by Laravel @routes)
        const routeFunction = (window as any).route;
        if (typeof routeFunction !== 'function') {
            ziggyAvailable = false;
            lastZiggyCheck = now;
            return false;
        }
        
        // Test with a simple route to ensure it works
        try {
            // Test with a route we know exists
            const testResult = routeFunction('members.index');
            ziggyAvailable = typeof testResult === 'string' && testResult.length > 0;
            lastZiggyCheck = now;
            return ziggyAvailable;
        } catch {
            ziggyAvailable = false;
            lastZiggyCheck = now;
            return false;
        }
    } catch {
        ziggyAvailable = false;
        lastZiggyCheck = now;
        return false;
    }
};

/**
 * Generate a URL for a route with parameters
 * Tries Ziggy first, falls back to manual URL construction
 */
export const safeRoute = (name: RouteKey, params?: any): string => {
    try {
        // First try the global route helper (Ziggy)
        if (isZiggyAvailable()) {
            try {
                const result = (window as any).route(name, params);
                if (typeof result === 'string' && result.length > 0) {
                    return result;
                }
            } catch (error) {
                console.warn(`Ziggy route helper failed for ${name}:`, error);
            }
        }
        
        // Fallback: manual URL construction
        let url: string = ROUTE_MAP[name] || '/members';
        
        // Handle parameter substitution
        if (params) {
            if (typeof params === 'number' || typeof params === 'string') {
                // Single ID parameter
                url = url.replace(':id', String(params));
            } else if (typeof params === 'object' && params !== null && !Array.isArray(params)) {
                // Object with multiple parameters
                const paramEntries = Object.entries(params);
                
                // Replace URL parameters first
                paramEntries.forEach(([key, value]) => {
                    const placeholder = `:${key}`;
                    if (url.includes(placeholder) && value !== null && value !== undefined) {
                        url = url.replace(placeholder, String(value));
                    }
                });
                
                // Build query string for remaining parameters
                const queryParams = paramEntries.filter(([key, value]) => {
                    // Only include params that aren't already in the URL and have valid values
                    return !ROUTE_MAP[name]?.includes(`:${key}`) && 
                           value !== null && 
                           value !== undefined && 
                           value !== '';
                });
                
                if (queryParams.length > 0) {
                    const queryString = buildQueryString(Object.fromEntries(queryParams));
                    url += queryString;
                }
            }
        }
        
        return url;
    } catch (error) {
        console.error(`Route generation failed for ${name}:`, error);
        return '/members'; // Ultimate fallback
    }
};

/**
 * Build a query string from parameters
 */
export const buildQueryString = (params: Record<string, any>): string => {
    try {
        const filteredParams = Object.entries(params).filter(([_, value]) => {
            return value !== null && value !== undefined && value !== '';
        });
        
        if (filteredParams.length === 0) {
            return '';
        }
        
        const searchParams = new URLSearchParams();
        filteredParams.forEach(([key, value]) => {
            searchParams.append(key, String(value));
        });
        
        return `?${searchParams.toString()}`;
    } catch (error) {
        console.warn('Query string building failed:', error);
        return '';
    }
};

/**
 * Safe route helper specifically for member actions
 */
export const memberRoute = (action: 'show' | 'edit' | 'destroy', memberId: number): string => {
    const routeMap: Record<string, RouteKey> = {
        'show': 'members.show',
        'edit': 'members.edit',
        'destroy': 'members.destroy'
    };
    
    const routeName = routeMap[action] || 'members.show';
    return safeRoute(routeName, memberId);
};

/**
 * Check if a route exists and is accessible
 */
export const isRouteAvailable = (name: RouteKey): boolean => {
    try {
        if (isZiggyAvailable()) {
            try {
                const testRoute = (window as any).route(name);
                return typeof testRoute === 'string' && testRoute.length > 0;
            } catch {
                // Fall through to ROUTE_MAP check
            }
        }
        return name in ROUTE_MAP;
    } catch {
        return name in ROUTE_MAP;
    }
};

/**
 * Generate pagination URL with filters
 */
export const buildPaginationUrl = (filters: Record<string, any>, page: number): string => {
    const params = { ...filters, page };
    return safeRoute('members.index', params);
};

/**
 * Generate export URL with current filters
 */
export const buildExportUrl = (filters: Record<string, any>, format: string = 'excel'): string => {
    // Don't double-encode the format in both params and query string
    const exportParams = { ...filters, format };
    return safeRoute('members.export', exportParams);
};

/**
 * Debug helper to diagnose route issues (development only)
 */
export const debugRouteHelper = (): void => {
    if (process.env.NODE_ENV === 'development') {
        console.group('🔍 Route Helper Debug Information');
        console.log('Ziggy Available:', isZiggyAvailable());
        console.log('Window Route Function:', typeof (window as any)?.route);
        console.log('Window Ziggy Object:', typeof (window as any)?.Ziggy);
        console.log('Available Routes in ROUTE_MAP:', Object.keys(ROUTE_MAP));
        
        // Check if Ziggy routes are available
        if (typeof (window as any)?.Ziggy?.routes === 'object') {
            const ziggyRoutes = Object.keys((window as any).Ziggy.routes);
            const memberRoutes = ziggyRoutes.filter(route => route.startsWith('members.'));
            console.log('Available Ziggy Member Routes:', memberRoutes);
        }
        
        // Test a few key routes
        const testRoutes: RouteKey[] = ['members.index', 'members.show', 'members.create'];
        testRoutes.forEach(routeName => {
            try {
                const url = safeRoute(routeName, routeName.includes('show') ? 1 : undefined);
                console.log(`✅ ${routeName}:`, url);
            } catch (error) {
                console.error(`❌ ${routeName}:`, error);
            }
        });
        console.groupEnd();
    }
};

/**
 * Initialize route helper and perform diagnostics
 */
export const initializeRouteHelper = (): boolean => {
    const available = isZiggyAvailable();
    
    if (process.env.NODE_ENV === 'development') {
        if (!available) {
            console.warn('⚠️ Ziggy route helper is not available. Using fallback route generation.');
            console.log('💡 This is normal in SSR or if Ziggy is not properly loaded.');
        } else {
            console.log('✅ Ziggy route helper is available and working.');
        }
    }
    
    return available;
};