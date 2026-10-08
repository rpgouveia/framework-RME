import { daysSince } from '@/lib/labels';

/**
 * How long a link has waited for its reassessment, since its reversal. There
 * is no deadline yet (0020, item 9): the lists show the wait and start with
 * the longest.
 */
export function WaitingDays({ since }: { since: string }) {
    const days = daysSince(since);

    return (
        <span className="tabular-nums">
            {days === 0 ? 'hoje' : days === 1 ? '1 dia' : `${days} dias`}
        </span>
    );
}
