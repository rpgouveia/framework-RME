import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import type { StatusHistory } from '@/types/models';

/**
 * Who recorded an entry: the owner of a manual one (role, with the area
 * below), or the system for an automatic one (review due, adverse event,
 * reclassification), which has no author (0018).
 */
export function EntryAuthor({
    entry,
}: {
    entry: Pick<StatusHistory, 'origin' | 'owner'>;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    if (entry.origin !== 'manual') {
        return (
            <span className="grid">
                <span>{t('Recorded by the system')}</span>
                <span className="text-muted-foreground text-xs font-normal">
                    {t('Origin: :origin', {
                        origin: enumLabels.ChangeOrigin[entry.origin],
                    })}
                </span>
            </span>
        );
    }

    if (!entry.owner) {
        return null;
    }

    return (
        <span className="grid">
            <span>{entry.owner.organizational_role}</span>
            <span className="text-muted-foreground text-xs font-normal">
                {entry.owner.area}
            </span>
        </span>
    );
}
