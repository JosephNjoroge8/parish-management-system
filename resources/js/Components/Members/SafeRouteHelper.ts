/**
 * Safe route helper for production environments
 * Handles cases where route() function might not be available
 */

export const safeRoute = (name: string, params?: any): string => {
    try {
        // Check if route function is available (should be in production)
        if (typeof (window as any).route === 'function') {
            return (window as any).route(name, params);
        }
        
        // Fallback route generation for production
        const routeMap: Record<string, string> = {
            'members.index': '/members',
            'members.create': '/members/create',
            'members.show': '/members/:id',
            'members.edit': '/members/:id/edit',
            'members.destroy': '/members/:id',
            'members.update-status': '/members/:id/status',
            'members.bulk-delete': '/members/bulk-delete',
            'members.export': '/members/export',
            'members.import': '/members/import',
        };
        
        let url = routeMap[name] || '/';
        
        // Replace parameters in URL
        if (params) {
            if (typeof params === 'number' || typeof params === 'string') {
                url = url.replace(':id', String(params));
            } else if (typeof params === 'object') {
                Object.keys(params).forEach(key => {
                    url = url.replace(`:${key}`, String(params[key]));
                });
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
            if (value && value !== '') {
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