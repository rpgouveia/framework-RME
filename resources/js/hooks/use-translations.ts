import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import type {
    TranslateChoiceFn,
    TranslateFn,
    TranslationReplacements,
} from '@/lib/i18n';
import { translate, translateChoice } from '@/lib/i18n';

/**
 * Get the translator for the locale the server is rendering in: `t` for a
 * line, `tc` for a line with a form per count.
 *
 * The lines arrive as a shared `translations` prop, resolved once per visit
 * and remembered by the client.
 */
export function useTranslations(): { t: TranslateFn; tc: TranslateChoiceFn } {
    const { translations } = usePage().props;

    const t = useCallback(
        (key: string, replacements?: TranslationReplacements): string =>
            translate(translations, key, replacements),
        [translations],
    );

    const tc = useCallback(
        (
            key: string,
            count: number,
            replacements?: TranslationReplacements,
        ): string => translateChoice(translations, key, count, replacements),
        [translations],
    );

    return { t, tc };
}
