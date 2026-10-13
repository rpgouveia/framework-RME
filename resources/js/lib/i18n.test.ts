import { describe, expect, test } from 'vite-plus/test';
import { isValidElement } from 'react';
import {
    interpolateNodes,
    translate,
    translateChoice,
    translator,
} from '@/lib/i18n';

const lines = {
    'Link verified.': 'Vínculo verificado.',
    'Waiting since :date': 'Aguardando desde :date',
    ':count link|:count links': ':count vínculo|:count vínculos',
    '{0} today|{1} 1 day|[2,*] :count days':
        '{0} hoje|{1} 1 dia|[2,*] :count dias',
    '[*,2] few|[3,9] some|[10,*] many':
        '[*,2] poucos|[3,9] alguns|[10,*] muitos',
};

describe('translate', () => {
    test('looks the line up by its english key', () => {
        expect(translate(lines, 'Link verified.')).toBe('Vínculo verificado.');
    });

    test('falls back to the key', () => {
        expect(translate(lines, 'Not translated')).toBe('Not translated');
    });

    test('replaces the placeholders', () => {
        expect(
            translate(lines, 'Waiting since :date', { date: '12/10/2026' }),
        ).toBe('Aguardando desde 12/10/2026');
    });
});

describe('translateChoice', () => {
    // The expectations match Laravel's MessageSelector for pt_BR.
    test('takes the singular for 1 and the plural otherwise, as Laravel for pt_BR', () => {
        const key = ':count link|:count links';

        expect(translateChoice(lines, key, 0)).toBe('0 vínculos');
        expect(translateChoice(lines, key, 1)).toBe('1 vínculo');
        expect(translateChoice(lines, key, 2)).toBe('2 vínculos');
    });

    test('takes an exact count or an open range first', () => {
        const key = '{0} today|{1} 1 day|[2,*] :count days';

        expect(translateChoice(lines, key, 0)).toBe('hoje');
        expect(translateChoice(lines, key, 1)).toBe('1 dia');
        expect(translateChoice(lines, key, 2)).toBe('2 dias');
        expect(translateChoice(lines, key, 31)).toBe('31 dias');
    });

    test('takes a closed range and a range open below', () => {
        const key = '[*,2] few|[3,9] some|[10,*] many';

        expect(translateChoice(lines, key, 2)).toBe('poucos');
        expect(translateChoice(lines, key, 3)).toBe('alguns');
        expect(translateChoice(lines, key, 9)).toBe('alguns');
        expect(translateChoice(lines, key, 10)).toBe('muitos');
    });

    test('a single form serves every count', () => {
        expect(translateChoice(lines, 'Link verified.', 5)).toBe(
            'Vínculo verificado.',
        );
    });

    test('lets a replacement name the count otherwise', () => {
        expect(
            translateChoice(lines, ':count link|:count links', 3, {
                count: 'três',
            }),
        ).toBe('três vínculos');
    });

    test('falls back to the key with the same rules', () => {
        expect(translateChoice({}, 'one day|:count days', 4)).toBe('4 days');
    });
});

describe('translator', () => {
    test('translates with the lines of the page props', () => {
        const t = translator(lines);

        expect(t('Link verified.')).toBe('Vínculo verificado.');
    });
});

describe('interpolateNodes', () => {
    test('puts the nodes in the place of their placeholders', () => {
        const parts = interpolateNodes('Mudança do sistema: :change.', {
            change: 'LINK',
        });

        expect(parts).toHaveLength(3);
        expect(parts[0]).toBe('Mudança do sistema: ');
        expect(isValidElement(parts[1])).toBe(true);
        expect(parts[2]).toBe('.');
    });

    test('leaves other placeholders and colons as text', () => {
        const parts = interpolateNodes(':system: planejar às 10:30 :other', {
            system: 'LINK',
        });

        expect(parts.filter((part) => typeof part === 'string')).toEqual([
            ': planejar às 10:30 :other',
        ]);
    });

    test('tells apart a name that starts another one', () => {
        const parts = interpolateNodes(':risk e :risks', {
            risk: 'A',
            risks: 'B',
        });

        expect(
            parts.map((part) =>
                isValidElement<{ children: string }>(part)
                    ? part.props.children
                    : part,
            ),
        ).toEqual(['A', ' e ', 'B']);
    });
});
