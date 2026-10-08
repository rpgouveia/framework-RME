import { Head, Link } from '@inertiajs/react';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { ReviewDate } from '@/components/review-date';
import { FilterLink } from '@/components/filter-link';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { changeOriginLabels, costLevelLabels } from '@/lib/labels';
import { linksWith } from '@/lib/link-filters';
import type { VerificationFilter } from '@/lib/link-filters';
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/links';
import { show as showMitigation } from '@/routes/mitigations';
import { show as showRisk } from '@/routes/risks';
import type { ChangeOrigin, Link as RiskLink, Paginated } from '@/types/models';

type Props = {
    links: Paginated<RiskLink>;
    filters: {
        verification: VerificationFilter | null;
        /** Within the reassessment list, the origin of the last reversal. */
        origin: ChangeOrigin | null;
    };
};

/** The origins of a reversal, in the order the dashboard shows them. */
const reversalOrigins: ChangeOrigin[] = [
    'review_due',
    'adverse_event',
    'system_reclassification',
    'manual',
];

const verificationFilters: {
    value: VerificationFilter | null;
    label: string;
    empty: string;
}[] = [
    { value: null, label: 'Todos', empty: 'Nenhum vínculo foi criado ainda.' },
    {
        value: 'awaiting_first',
        label: 'Aguardando primeira verificação',
        empty: 'Nenhum vínculo aguarda a primeira verificação.',
    },
    {
        value: 'awaiting_reassessment',
        label: 'Aguardando reavaliação',
        empty: 'Nenhum vínculo aguarda reavaliação.',
    },
    {
        value: 'verified',
        label: 'Verificados',
        empty: 'Nenhum vínculo está verificado.',
    },
];

export default function LinksIndex({ links, filters }: Props) {
    const current =
        verificationFilters.find(
            (option) => option.value === filters.verification,
        ) ?? verificationFilters[0];

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

                <nav
                    aria-label="Filtrar por verificação"
                    className="flex flex-wrap gap-2"
                >
                    {verificationFilters.map((option) => (
                        <FilterLink
                            key={option.label}
                            href={linksWith(option.value)}
                            active={filters.verification === option.value}
                        >
                            {option.label}
                        </FilterLink>
                    ))}
                </nav>
                {filters.verification === 'awaiting_reassessment' && (
                    <nav
                        aria-label="Filtrar pela origem da última reversão"
                        className="-mt-2 flex flex-wrap gap-2"
                    >
                        <FilterLink
                            href={linksWith('awaiting_reassessment')}
                            active={filters.origin === null}
                            subtle
                        >
                            Todas as origens
                        </FilterLink>
                        {reversalOrigins.map((origin) => (
                            <FilterLink
                                key={origin}
                                href={linksWith(
                                    'awaiting_reassessment',
                                    origin,
                                )}
                                active={filters.origin === origin}
                                subtle
                            >
                                {changeOriginLabels[origin]}
                            </FilterLink>
                        ))}
                    </nav>
                )}
                {filters.verification !== null && (
                    <p className="text-muted-foreground -mt-2 text-sm">
                        {filters.verification === 'verified'
                            ? 'Vínculos verificados que não foram cancelados.'
                            : filters.verification === 'awaiting_reassessment'
                              ? 'Vínculos revertidos para declarados, ainda não cancelados. Os de sistemas reclassificados para a faixa inaceitável aparecem aqui, mas não podem ser verificados de novo.'
                              : 'Ficam de fora os vínculos cancelados e os de sistemas na faixa inaceitável, que não podem ser verificados.'}
                    </p>
                )}

                {links.data.length === 0 ? (
                    filters.verification ? (
                        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                            <p className="text-muted-foreground text-sm">
                                {current.empty}
                            </p>
                            <Button variant="outline" asChild>
                                <Link href={linksWith(null)}>
                                    Ver todos os vínculos
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <EmptyState />
                    )
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
                                <LinkStatusBadges link={link} />
                            </TableCell>
                            <TableCell>
                                <ReviewDate
                                    date={link.next_review_date}
                                    verification={link.verification_status}
                                    unacceptable={
                                        link.risk?.ai_system?.category ===
                                        'unacceptable'
                                    }
                                />
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
