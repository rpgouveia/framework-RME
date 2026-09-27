import { router } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { MouseEvent } from 'react';

/**
 * Click handler that makes a whole table row open `href`. Clicks on inner
 * links and buttons navigate on their own, and so do clicks inside a dialog
 * opened from the row: React bubbles portal events up the component tree, but
 * the dialog is not inside the row in the DOM.
 */
export function rowLink(href: NonNullable<InertiaLinkProps['href']>) {
    return (event: MouseEvent<HTMLElement>) => {
        const target = event.target as HTMLElement;

        if (
            !event.currentTarget.contains(target) ||
            target.closest('a, button')
        ) {
            return;
        }

        router.visit(href);
    };
}
