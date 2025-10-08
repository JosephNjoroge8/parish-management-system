import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';

declare global {
    interface Window {
        axios: AxiosInstance;
        Ziggy: any; // Ziggy configuration object
    }

    /* eslint-disable no-var */
    var route: typeof ziggyRoute;

    // JSX namespace for React
    namespace JSX {
        interface IntrinsicElements {
            [elemName: string]: any;
        }
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}

// Lodash types
declare module 'lodash' {
    export function debounce<T extends (...args: any[]) => any>(
        func: T,
        wait?: number,
        options?: any
    ): T;
}
