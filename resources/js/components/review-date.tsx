import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslations } from '@/hooks/use-translations';
import { dateInputValue, formatDate } from '@/lib/format';
import type { VerificationStatus } from '@/types/models';

/**
 * A link's next review date. There is none for a link of a system in the
 * unacceptable tier, which never operates, nor for a declared link: the
 * periodic review starts at its first verification (0018). The reason shows
 * on hover or keyboard focus. The date is the last valid day: on it, the link
 * is "due today", and it is reverted from the next day (0019, item 1).
 */
export function ReviewDate({
    date,
    verification,
    unacceptable = false,
}: {
    date: string | null;
    verification: VerificationStatus;
    /** Whether the link's system is in the unacceptable tier. */
    unacceptable?: boolean;
}) {
    const { t } = useTranslations();

    if (date !== null && dateInputValue(date) === dateInputValue()) {
        return (
            <span className="text-tag-yellow-foreground font-semibold">
                {t('Due today')}
            </span>
        );
    }

    if (date !== null) {
        return <>{formatDate(date)}</>;
    }

    const [label, reason] =
        unacceptable || verification === 'verified'
            ? [
                  t('Not applicable'),
                  t(
                      'The system is in the unacceptable tier of the EU AI Act and cannot operate, so the link has no periodic review.',
                  ),
              ]
            : [
                  t('Awaiting verification'),
                  t(
                      "The periodic review starts at the link's first verification, with the interval of the system's tier.",
                  ),
              ];

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className="text-muted-foreground cursor-help font-normal underline decoration-dotted underline-offset-4"
                >
                    {label}
                </span>
            </TooltipTrigger>
            <TooltipContent className="max-w-xs">{reason}</TooltipContent>
        </Tooltip>
    );
}
