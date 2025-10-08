import axios from 'axios';

// Set up axios globally
window.axios = axios;

// Set default headers
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add request interceptor for CSRF token
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Add response interceptor for better error handling
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        // Handle common HTTP errors
        if (error.response?.status === 419) {
            // CSRF token mismatch - reload page
            console.warn('CSRF token mismatch, reloading page...');
            window.location.reload();
        } else if (error.response?.status === 500) {
            console.error('Server error:', error.response.data);
        }
        
        return Promise.reject(error);
    }
);

// Global error handler for unhandled promise rejections
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    event.preventDefault();
});

// Global error handler for JavaScript errors
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error || event.message);
});

export {};
