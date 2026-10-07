import type { ReactNode } from 'react';

/**
 * A system's name with its application domain in a smaller line below it.
 * The domain is optional and descriptive: when empty, nothing is shown.
 */
export function AiSystemName({
    domain,
    children,
}: {
    domain: string | null | undefined;
    /** The name, often a link to the system. */
    children: ReactNode;
}) {
    return (
        <span className="grid gap-0.5">
            {children}
            {domain && (
                <span className="text-muted-foreground text-xs font-normal">
                    {domain}
                </span>
            )}
        </span>
    );
}
