import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            /**
             * Flash yang dipublikasikan server. Daftar key didefinisikan
             * eksplisit di `HandleInertiaRequests`.
             */
            flash: {
                'booking.customer_saved'?: unknown;
            };
            [key: string]: unknown;
        };
    }
}
