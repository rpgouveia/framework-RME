import { CircleXIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useTranslations } from '@/hooks/use-translations';

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
        <Alert id={id} variant="destructive" className={className}>
            <CircleXIcon aria-hidden />
            <AlertTitle>
                {t('System in the unacceptable tier of the EU AI Act')}
            </AlertTitle>
            <AlertDescription>
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
