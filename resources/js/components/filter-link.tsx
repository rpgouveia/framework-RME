import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

/**
 * One option of a server side list filter: a link that keeps the page state
 * and marks itself as current when active. A subtle one is for a second,
 * narrower filter level.
 */
export function FilterLink({
    href,
    active,
    subtle = false,
    children,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
    active: boolean;
    subtle?: boolean;
    children: ReactNode;
}) {
    return (
        <Button
            size="sm"
            variant={
                active
                    ? subtle
                        ? 'secondary'
                        : 'default'
                    : subtle
                      ? 'ghost'
                      : 'outline'
            }
            asChild
        >
            <Link
                href={href}
                preserveState
                preserveScroll
                aria-current={active ? 'page' : undefined}
            >
                {children}
            </Link>
        </Button>
    );
}
