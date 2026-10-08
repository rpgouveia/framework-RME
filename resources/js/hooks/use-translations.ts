import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import type { TranslateFn, TranslationReplacements } from '@/lib/i18n';
import { translate } from '@/lib/i18n';

/**
 * Get the translator for the locale the server is rendering in.
 *
 * The lines arrive as a shared `translations` prop, resolved once per visit
 * and remembered by the client.
 */
export function useTranslations(): { t: TranslateFn } {
    const { translations } = usePage().props;

    const t = useCallback(
        (key: string, replacements?: TranslationReplacements): string =>
            translate(translations, key, replacements),
        [translations],
    );

    return { t };
}
