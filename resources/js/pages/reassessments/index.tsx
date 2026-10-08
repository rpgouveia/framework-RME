import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import {
    causeStatusLabels,
    changeOriginLabels,
    linkLabel,
    reassessmentOutcomeLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { index } from '@/routes/links/reassessments';
import { show } from '@/routes/reassessments';
import type { Link as RiskLink, Paginated, Reassessment } from '@/types/models';

type Props = {
    link: RiskLink;
    reassessments: Paginated<Reassessment>;
};

export default function ReassessmentsIndex({ link, reassessments }: Props) {
    return (
        <>
            <Head title="Reavaliações" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Reavaliações"
                    description={`${linkLabel(link)}. Cada reavaliação conclui uma reversão e não pode ser editada nem excluída.`}
                />

                {reassessments.data.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        Este vínculo ainda não foi reavaliado.
                    </p>
                ) : (
                    <>
                        <div className="rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Data</TableHead>
                                        <TableHead>Desfecho</TableHead>
                                        <TableHead>Reversão</TableHead>
                                        <TableHead>Causa</TableHead>
                                        <TableHead>Quem reavaliou</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reassessments.data.map((reassessment) => (
                                        <TableRow
                                            key={reassessment.id}
                                            className="cursor-pointer"
                                            onClick={rowLink(
                                                show(reassessment.id),
                                            )}
                                        >
                                            <TableCell>
                                                <Link
                                                    href={show(reassessment.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {formatDate(
                                                        reassessment.reassessment_date,
                                                    )}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {
                                                        reassessmentOutcomeLabels[
                                                            reassessment.outcome
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {reassessment.reversal &&
                                                    changeOriginLabels[
                                                        reassessment.reversal
                                                            .origin
                                                    ]}
                                            </TableCell>
                                            <TableCell>
                                                {
                                                    causeStatusLabels[
                                                        reassessment
                                                            .cause_status
                                                    ]
                                                }
                                            </TableCell>
                                            <TableCell>
                                                {
                                                    reassessment.owner
                                                        ?.organizational_role
                                                }
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        {reassessments.last_page > 1 && (
                            <PaginationLinks links={reassessments.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

ReassessmentsIndex.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(link), href: showLink(link.id) },
        { title: 'Reavaliações', href: index(link.id) },
    ],
});
