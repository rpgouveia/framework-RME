import { Link } from '@inertiajs/react';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { show as showEvidence } from '@/routes/evidence';
import type { Evidence } from '@/types/models';

/**
 * A link's observed cost: the one reported by its latest evidence that
 * reported a cost (RF07, 0018), with a link to that evidence.
 */
export function ObservedCost({ evidence }: { evidence?: Evidence | null }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    if (!evidence?.observed_cost) {
        return (
            <span className="text-muted-foreground font-normal">
                {t('Not reported')}
            </span>
        );
    }

    return (
        <span className="grid">
            <span>{enumLabels.CostLevel[evidence.observed_cost]}</span>
            <Link
                href={showEvidence(evidence.id)}
                className="text-muted-foreground text-xs font-normal hover:underline"
            >
                {t('Reported in the evidence of :date', {
                    date: formatDate(evidence.registration_date),
                })}
            </Link>
        </span>
    );
}
