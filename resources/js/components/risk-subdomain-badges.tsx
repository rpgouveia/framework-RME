import { Badge } from '@/components/ui/badge';
import { termLabel } from '@/lib/labels';

/**
 * The MIT risk subdomains of an adverse event as compact badges: code and
 * name, the name cut short when long (the full label shows on hover).
 */
export function RiskSubdomainBadges({
    subdomains,
}: {
    subdomains?: { code: string; name: string }[];
}) {
    return (
        <div className="flex flex-wrap gap-1">
            {subdomains?.map((subdomain) => (
                <Badge
                    key={subdomain.code}
                    variant="outline"
                    title={termLabel(subdomain)}
                    className="max-w-56"
                >
                    <span className="truncate">{termLabel(subdomain)}</span>
                </Badge>
            ))}
        </div>
    );
}
