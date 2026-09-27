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
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="font-medium">{children}</dd>
        </div>
    );
}
