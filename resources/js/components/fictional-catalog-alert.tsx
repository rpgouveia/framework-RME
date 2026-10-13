import { FlaskConicalIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useTranslations } from '@/hooks/use-translations';

/**
 * Says the catalogue loaded into the database is demonstration data, as its
 * data file declares, so no one mistakes it for the curated C2 catalogue.
 */
export function FictionalCatalogAlert() {
    const { t } = useTranslations();

    return (
        <Alert>
            <FlaskConicalIcon />
            <AlertTitle>{t('Demonstration catalogue')}</AlertTitle>
            <AlertDescription>
                {t(
                    "The current catalogue holds fictional demonstration data. The names, identifiers and estimates do not come from the Saeri et al. database and will be replaced by the group's curated catalogue.",
                )}
            </AlertDescription>
        </Alert>
    );
}
