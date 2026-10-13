import { createElement, Fragment } from 'react';
import type { ReactNode } from 'react';

export type Translations = Record<string, string>;

export type TranslationReplacements = Record<string, string | number>;

export type TranslateFn = (
    key: string,
    replacements?: TranslationReplacements,
) => string;

export type TranslateChoiceFn = (
    key: string,
    count: number,
    replacements?: TranslationReplacements,
) => string;

/** The page props that carry the shared translation lines. */
export type WithTranslations = { translations: Translations };

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
    return replace(translations[key] ?? key, replacements);
}

/**
 * Look a line up and pick the form for `count`, as Laravel's `trans_choice`.
 *
 * Forms are separated by `|`. A form may start with an exact count, `{0}`, or
 * a range, `[2,*]`; otherwise the first form is the singular, for a count of
 * 1, and the second the plural, which is Laravel's rule for pt_BR, the locale
 * of the interface (0022), as for English. `:count` is replaced by the count.
 */
export function translateChoice(
    translations: Translations,
    key: string,
    count: number,
    replacements: TranslationReplacements = {},
): string {
    return replace(choose(translations[key] ?? key, count), {
        count,
        ...replacements,
    });
}

/**
 * The translator for code outside a component that receives the page props,
 * such as a page's layout callback.
 */
export function translator(translations: Translations): TranslateFn {
    return (key, replacements) => translate(translations, key, replacements);
}

/**
 * Put elements in the place of `:name` placeholders of a translated line, for
 * a sentence with a link or emphasis in the middle. Only the placeholders
 * named in `nodes` are replaced; the rest of the line stays text.
 */
export function interpolateNodes(
    line: string,
    nodes: Record<string, ReactNode>,
): ReactNode[] {
    const names = Object.keys(nodes);

    if (names.length === 0) {
        return [line];
    }

    return line
        .split(new RegExp(`:(${names.join('|')})(?![A-Za-z0-9_])`))
        .map((part, index) =>
            index % 2 === 0
                ? part
                : createElement(Fragment, { key: index }, nodes[part]),
        )
        .filter((part) => part !== '');
}

function replace(line: string, replacements: TranslationReplacements): string {
    return Object.entries(replacements).reduce(
        (result, [placeholder, value]) =>
            result.replaceAll(`:${placeholder}`, String(value)),
        line,
    );
}

const condition = /^[{[]([^[\]{}]*)[}\]]/s;

/** Laravel's MessageSelector::choose. */
function choose(line: string, count: number): string {
    const segments = line.split('|');

    for (const segment of segments) {
        const match = segment.match(/^[{[]([^[\]{}]*)[}\]](.*)/s);

        if (match && matches(match[1], count)) {
            return match[2].trim();
        }
    }

    const forms = segments.map((segment) => segment.replace(condition, ''));
    const index = count === 1 ? 0 : 1;

    return forms.length === 1 || forms[index] === undefined
        ? forms[0]
        : forms[index];
}

function matches(interval: string, count: number): boolean {
    if (!interval.includes(',')) {
        return Number(interval) === count;
    }

    const [from, to] = interval.split(',', 2);

    if (to === '*') {
        return count >= Number(from);
    }

    if (from === '*') {
        return count <= Number(to);
    }

    return count >= Number(from) && count <= Number(to);
}
