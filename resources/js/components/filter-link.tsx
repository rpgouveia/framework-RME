import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

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
        <Link
            href={href}
            preserveState
            preserveScroll
            aria-current={active ? 'page' : undefined}
            className={cn(
                'focus-visible:outline-ring inline-flex h-8 items-center gap-2 border px-3 text-sm tracking-[0.16px] whitespace-nowrap transition-colors outline-none focus-visible:outline-2 focus-visible:-outline-offset-2',
                active
                    ? 'border-foreground bg-foreground text-background'
                    : 'border-border-strong text-foreground hover:bg-accent',
                subtle && !active && 'border-border',
            )}
        >
            {children}
        </Link>
    );
}
