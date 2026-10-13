import { Link } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { subdomainCodes } from '@/lib/names';
import { rowLink } from '@/lib/row-link';
import { show } from '@/routes/system-changes';
import type { SystemChange } from '@/types/models';

/** The changes of a system, also used on the system page. */
export function SystemChangesTable({ changes }: { changes: SystemChange[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Date')}</TableHead>
                        <TableHead>{t('Type')}</TableHead>
                        <TableHead>{t('Affected subdomains')}</TableHead>
                        <TableHead className="text-right">
                            {t('Reverted links')}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {changes.map((change) => (
                        <TableRow
                            key={change.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(change.id))}
                        >
                            <TableCell>
                                <Link
                                    href={show(change.id)}
                                    className="font-medium hover:underline"
                                >
                                    {formatDate(change.change_date)}
                                </Link>
                            </TableCell>
                            <TableCell>
                                {enumLabels.SystemChangeType[change.type]}
                            </TableCell>
                            <TableCell>
                                {subdomainCodes(change) || (
                                    <span className="text-muted-foreground">
                                        {t('All (none given)')}
                                    </span>
                                )}
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {change.reversals_count ?? 0}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
