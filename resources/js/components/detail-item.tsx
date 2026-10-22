import type { ReactNode } from 'react';

/** A label and value pair, meant to sit inside a `<dl>`. */
export function DetailItem({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-1">
            <dt className="text-muted-foreground text-xs tracking-[0.32px]">
                {label}
            </dt>
            <dd>{children}</dd>
        </div>
    );
}
