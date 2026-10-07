import { FlaskConicalIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

/**
 * Says the catalogue loaded into the database is demonstration data, as its
 * data file declares, so no one mistakes it for the curated C2 catalogue.
 */
export function FictionalCatalogAlert() {
    return (
        <Alert>
            <FlaskConicalIcon />
            <AlertTitle>Catálogo de demonstração</AlertTitle>
            <AlertDescription>
                O catálogo atual contém dados fictícios de demonstração. Os
                nomes, identificadores e estimativas não vêm da base de Saeri et
                al. e serão substituídos pelo catálogo curado do grupo.
            </AlertDescription>
        </Alert>
    );
}
