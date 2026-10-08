import { Link } from '@inertiajs/react';
import { formatDate } from '@/lib/format';
import { costLevelLabels } from '@/lib/labels';
import { show as showEvidence } from '@/routes/evidence';
import type { Evidence } from '@/types/models';

/**
 * A link's observed cost: the one reported by its latest evidence that
 * reported a cost (RF07, 0018), with a link to that evidence.
 */
export function ObservedCost({ evidence }: { evidence?: Evidence | null }) {
    if (!evidence?.observed_cost) {
        return (
            <span className="text-muted-foreground font-normal">
                Não informado
            </span>
        );
    }

    return (
        <span className="grid">
            <span>{costLevelLabels[evidence.observed_cost]}</span>
            <Link
                href={showEvidence(evidence.id)}
                className="text-muted-foreground text-xs font-normal hover:underline"
            >
                Informado na evidência de{' '}
                {formatDate(evidence.registration_date)}
            </Link>
        </span>
    );
}
