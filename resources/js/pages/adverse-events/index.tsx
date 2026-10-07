import { Head, Link } from '@inertiajs/react';
import { FilterLink } from '@/components/filter-link';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { RiskSubdomainBadges } from '@/components/risk-subdomain-badges';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { termLabel } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/adverse-events';
import { show as showAiSystem } from '@/routes/ai-systems';
import type { AdverseEvent, Paginated, TaxonomyCategory } from '@/types/models';

type Props = {
    adverseEvents: Paginated<AdverseEvent>;
    /** The MIT risk domain the list is narrowed to, if any. */
    filters: { domain: string | null };
    riskDomains: TaxonomyCategory[];
};

/** The list URL for a domain, or for every event. */
function eventsIn(domain: string | null) {
    return index({ query: domain ? { domain } : {} });
}

export default function AdverseEventsIndex({
    adverseEvents,
    filters,
    riskDomains,
}: Props) {
    const domain = riskDomains.find((option) => option.code === filters.domain);

    return (
        <>
            <Head title="Eventos adversos" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Eventos adversos"
                        description="O que deu errado nos sistemas em operação, do mais recente ao mais antigo."
                    />
                    <Button asChild>
                        <Link href={create()}>Registrar evento adverso</Link>
                    </Button>
                </div>

                <nav
                    aria-label="Filtrar por domínio de risco"
                    className="flex flex-wrap gap-2"
                >
                    <FilterLink
                        href={eventsIn(null)}
                        active={filters.domain === null}
                    >
                        Todos
                    </FilterLink>
                    {riskDomains.map((option) => (
                        <FilterLink
                            key={option.code}
                            href={eventsIn(option.code)}
                            active={filters.domain === option.code}
                        >
                            {termLabel(option)}
                        </FilterLink>
                    ))}
                </nav>

                {adverseEvents.data.length === 0 ? (
                    domain ? (
                        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                            <p className="text-muted-foreground text-sm">
                                Nenhum evento adverso materializa riscos do
                                domínio {termLabel(domain)}.
                            </p>
                            <Button variant="outline" asChild>
                                <Link href={eventsIn(null)}>
                                    Ver todos os eventos
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                            <p className="text-muted-foreground text-sm">
                                Nenhum evento adverso foi registrado.
                            </p>
                            <Button asChild>
                                <Link href={create()}>
                                    Registrar evento adverso
                                </Link>
                            </Button>
                        </div>
                    )
                ) : (
                    <>
                        <EventsTable events={adverseEvents.data} />
                        {adverseEvents.last_page > 1 && (
                            <PaginationLinks links={adverseEvents.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EventsTable({ events }: { events: AdverseEvent[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Subdomínios de risco</TableHead>
                        <TableHead>Sistema</TableHead>
                        <TableHead>Ocorrência</TableHead>
                        <TableHead>Descrição</TableHead>
                        <TableHead className="text-right">
                            Mudanças disparadas
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {events.map((event) => (
                        <TableRow
                            key={event.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(event.id))}
                        >
                            <TableCell>
                                <RiskSubdomainBadges
                                    subdomains={event.risk_subdomains}
                                />
                            </TableCell>
                            <TableCell>
                                {event.ai_system && (
                                    <Link
                                        href={showAiSystem(event.ai_system.id)}
                                        className="hover:underline"
                                    >
                                        {event.ai_system.name}
                                    </Link>
                                )}
                            </TableCell>
                            <TableCell>
                                {formatDate(event.occurrence_date)}
                            </TableCell>
                            <TableCell>
                                <Link
                                    href={show(event.id)}
                                    className="block max-w-sm truncate hover:underline"
                                >
                                    {event.description}
                                </Link>
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {event.status_histories_count ?? 0}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

AdverseEventsIndex.layout = {
    breadcrumbs: [{ title: 'Eventos adversos', href: index() }],
};
