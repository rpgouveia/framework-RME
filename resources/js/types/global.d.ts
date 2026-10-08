import type { Auth } from '@/types/auth';
import type { Translations } from '@/lib/i18n';

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
            translations: Translations;
            [key: string]: unknown;
        };
    }
}
