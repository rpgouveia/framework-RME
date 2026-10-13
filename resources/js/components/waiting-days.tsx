import { useTranslations } from '@/hooks/use-translations';
import { daysSince } from '@/lib/format';

/**
 * How long a link has waited for its reassessment, since its reversal. There
 * is no deadline yet (0020, item 9): the lists show the wait and start with
 * the longest.
 */
export function WaitingDays({ since }: { since: string }) {
    const { tc } = useTranslations();
    const days = daysSince(since);

    return (
        <span className="tabular-nums">
            {tc('{0} today|{1} 1 day|[2,*] :count days', days)}
        </span>
    );
}
