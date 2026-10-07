import { termLabel } from '@/lib/labels';
import type { TaxonomyTerm } from '@/types/models';

/**
 * A risk's MIT subdomain, with its domain below it in a smaller line.
 */
export function RiskSubdomain({ subdomain }: { subdomain?: TaxonomyTerm }) {
    if (!subdomain) {
        return null;
    }

    return (
        <div className="grid gap-0.5">
            <span>{termLabel(subdomain)}</span>
            {subdomain.parent && (
                <span className="text-muted-foreground text-xs font-normal">
                    {termLabel(subdomain.parent)}
                </span>
            )}
        </div>
    );
}
