import { Badge } from '@/components/ui/badge';
import { linkStatusBadgeClasses, linkStatusLabels } from '@/lib/labels';
import type { LinkStatus } from '@/types/models';

/** The move an entry records; the opening entry has no previous status. */
export function StatusTransition({
    from,
    to,
}: {
    from: LinkStatus | null;
    to: LinkStatus;
}) {
    return (
        <span className="flex flex-wrap items-center gap-2 text-sm">
            {from === null ? (
                <span className="text-muted-foreground">Abertura</span>
            ) : (
                <Badge className={linkStatusBadgeClasses[from]}>
                    {linkStatusLabels[from]}
                </Badge>
            )}
            <span aria-hidden className="text-muted-foreground">
                →
            </span>
            <Badge className={linkStatusBadgeClasses[to]}>
                {linkStatusLabels[to]}
            </Badge>
        </span>
    );
}
