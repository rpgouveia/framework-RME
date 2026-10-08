import { changeOriginLabels } from '@/lib/labels';
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
    if (entry.origin !== 'manual') {
        return (
            <span className="grid">
                <span>Registrado pelo sistema</span>
                <span className="text-muted-foreground text-xs font-normal">
                    Origem: {changeOriginLabels[entry.origin]}
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
