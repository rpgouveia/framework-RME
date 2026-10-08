import { VerificationBadge } from '@/components/link-status-badges';
import { Badge } from '@/components/ui/badge';
import { linkStatusBadgeClasses, linkStatusLabels } from '@/lib/labels';
import type { StatusHistory } from '@/types/models';

type Entry = Pick<
    StatusHistory,
    | 'previous_status'
    | 'new_status'
    | 'previous_verification'
    | 'new_verification'
>;

/**
 * The move an entry records. Each entry changes one dimension (0013): the
 * progress, where the opening entry has no previous status, or the
 * verification, labelled as such.
 */
export function StatusTransition({ entry }: { entry: Entry }) {
    if (
        entry.previous_verification === 'verified' &&
        entry.new_verification === 'verified'
    ) {
        // Renewed before it fell due (0019, item 2).
        return (
            <span className="flex flex-wrap items-center gap-2 text-sm">
                <span className="text-muted-foreground">
                    Renovação da verificação:
                </span>
                <VerificationBadge verification="verified" />
            </span>
        );
    }

    if (entry.new_verification !== null) {
        return (
            <span className="flex flex-wrap items-center gap-2 text-sm">
                <span className="text-muted-foreground">Verificação:</span>
                {entry.previous_verification !== null && (
                    <VerificationBadge
                        verification={entry.previous_verification}
                    />
                )}
                <span aria-hidden className="text-muted-foreground">
                    →
                </span>
                <VerificationBadge verification={entry.new_verification} />
            </span>
        );
    }

    return (
        <span className="flex flex-wrap items-center gap-2 text-sm">
            {entry.previous_status === null ? (
                <span className="text-muted-foreground">Abertura</span>
            ) : (
                <Badge
                    className={linkStatusBadgeClasses[entry.previous_status]}
                >
                    {linkStatusLabels[entry.previous_status]}
                </Badge>
            )}
            <span aria-hidden className="text-muted-foreground">
                →
            </span>
            {entry.new_status !== null && (
                <Badge className={linkStatusBadgeClasses[entry.new_status]}>
                    {linkStatusLabels[entry.new_status]}
                </Badge>
            )}
        </span>
    );
}
