import type { ReactNode } from 'react';

/**
 * The bottom area of a page for the action that ends or removes the record,
 * kept apart from the everyday actions. When the action is unavailable, the
 * description says why.
 */
export function DangerZone({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="flex flex-wrap items-center justify-between gap-4 border-t pt-6">
            <div className="grid gap-1">
                <h2 className="text-base font-semibold">{title}</h2>
                <p className="text-muted-foreground max-w-2xl">{description}</p>
            </div>
            {children}
        </section>
    );
}
