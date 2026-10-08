export type Translations = Record<string, string>;

export type TranslationReplacements = Record<string, string | number>;

export type TranslateFn = (
    key: string,
    replacements?: TranslationReplacements,
) => string;

/**
 * Look a line up in the shared translations, falling back to the key.
 *
 * The keys are the English strings, so a locale without a JSON file — or a
 * line the locale has not translated yet — renders the English source.
 * Placeholders follow Laravel's `:name` convention.
 */
export function translate(
    translations: Translations,
    key: string,
    replacements: TranslationReplacements = {},
): string {
    return Object.entries(replacements).reduce(
        (line, [placeholder, value]) =>
            line.replaceAll(`:${placeholder}`, String(value)),
        translations[key] ?? key,
    );
}
