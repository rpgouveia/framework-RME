import type { ReactNode } from 'react';

/**
 * A label and value pair, meant to sit inside a `<dl>`. The help, usually a
 * Toggletip, sits beside the label.
 */
export function DetailItem({
    label,
    help,
    children,
}: {
    label: string;
    help?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="grid content-start gap-1">
            <dt className="text-muted-foreground flex items-center gap-1 text-xs tracking-[0.32px]">
                {label}
                {help}
            </dt>
            <dd>{children}</dd>
        </div>
    );
}
