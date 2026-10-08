import { Link } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { subdomainCodes, systemChangeTypeLabels } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { show } from '@/routes/system-changes';
import type { SystemChange } from '@/types/models';

/** The changes of a system, also used on the system page. */
export function SystemChangesTable({ changes }: { changes: SystemChange[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Data</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Subdomínios afetados</TableHead>
                        <TableHead className="text-right">
                            Vínculos revertidos
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
                                {systemChangeTypeLabels[change.type]}
                            </TableCell>
                            <TableCell>
                                {subdomainCodes(change) || (
                                    <span className="text-muted-foreground">
                                        Todos (nenhum informado)
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
