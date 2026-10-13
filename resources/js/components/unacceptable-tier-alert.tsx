import { TriangleAlertIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * Red tones measured for WCAG 2.1 AA: at least 9:1 in light mode and 13:1 in
 * dark mode. shadcn's destructive alert falls to 3.6:1 in dark mode.
 */
export const unacceptableTone =
    'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100';

/** Anchor of the alert on the system page, so other pages can point at it. */
export const UNACCEPTABLE_ALERT_ID = 'faixa-inaceitavel';

/**
 * The warning for a system in the unacceptable tier of the EU AI Act: it
 * may be registered and planned for, but never operates. The short version
 * is for forms, next to the choice that led to it.
 */
export function UnacceptableTierAlert({
    short = false,
    className,
    id,
}: {
    short?: boolean;
    className?: string;
    id?: string;
}) {
    const { t } = useTranslations();

    return (
        <Alert id={id} className={cn(unacceptableTone, className)}>
            <TriangleAlertIcon aria-hidden />
            <AlertTitle>
                {t('System in the unacceptable tier of the EU AI Act')}
            </AlertTitle>
            <AlertDescription className="text-red-900 dark:text-red-200">
                {short ? (
                    <p>
                        {t(
                            'The system cannot operate: this link will have no periodic review and serves to plan the discontinuation.',
                        )}
                    </p>
                ) : (
                    <p>
                        {t(
                            'The tier stands for practices the EU AI Act prohibits. The system is not considered in operation, and its links have no periodic review: they serve to plan the discontinuation.',
                        )}
                    </p>
                )}
            </AlertDescription>
        </Alert>
    );
}
