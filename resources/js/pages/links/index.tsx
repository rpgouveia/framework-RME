import { Head, Link } from '@inertiajs/react';
import { ReviewDate } from '@/components/review-date';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    costLevelLabels,
    linkStatusBadgeClasses,
    linkStatusLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/links';
import { show as showMitigation } from '@/routes/mitigations';
import { show as showRisk } from '@/routes/risks';
import type { Link as RiskLink, Paginated } from '@/types/models';

type Props = {
    links: Paginated<RiskLink>;
};

export default function LinksIndex({ links }: Props) {
    return (
        <>
            <Head title="Vínculos" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Vínculos"
                        description="Mitigações aplicadas a cada risco, ordenadas pela próxima revisão."
                    />
                    <Button asChild>
                        <Link href={create()}>Criar vínculo</Link>
                    </Button>
                </div>

                {links.data.length === 0 ? (
                    <EmptyState />
                ) : (
                    <>
                        <LinksTable links={links.data} />
                        {links.last_page > 1 && (
                            <PaginationLinks links={links.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">
                Nenhum vínculo foi criado ainda.
            </p>
            <Button asChild>
                <Link href={create()}>Criar vínculo</Link>
            </Button>
        </div>
    );
}

function LinksTable({ links }: { links: RiskLink[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Risco</TableHead>
                        <TableHead>Mitigação</TableHead>
                        <TableHead>Responsável</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Próxima revisão</TableHead>
                        <TableHead>Custo estimado</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {links.map((link) => (
                        <TableRow
                            key={link.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(link.id))}
                        >
                            <TableCell>
                                <div className="grid max-w-xs">
                                    <Link
                                        href={showRisk(link.risk_id)}
                                        className="font-medium hover:underline"
                                    >
                                        {link.risk?.name}
                                    </Link>
                                    <span className="text-muted-foreground truncate text-xs">
                                        {link.risk?.ai_system?.name}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Link
                                    href={showMitigation(link.mitigation_id)}
                                    className="hover:underline"
                                >
                                    {link.mitigation?.name}
                                </Link>
                            </TableCell>
                            <TableCell>
                                {link.owner && (
                                    <div className="grid">
                                        <span>
                                            {link.owner.organizational_role}
                                        </span>
                                        <span className="text-muted-foreground text-xs">
                                            {link.owner.area}
                                        </span>
                                    </div>
                                )}
                            </TableCell>
                            <TableCell>
                                <Badge
                                    className={
                                        linkStatusBadgeClasses[link.status]
                                    }
                                >
                                    {linkStatusLabels[link.status]}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <ReviewDate date={link.next_review_date} />
                            </TableCell>
                            <TableCell>
                                {costLevelLabels[link.estimated_cost]}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

LinksIndex.layout = {
    breadcrumbs: [{ title: 'Vínculos', href: index() }],
};
