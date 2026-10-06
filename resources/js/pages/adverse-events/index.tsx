import { Head, Link } from '@inertiajs/react';
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
import { formatDate } from '@/lib/format';
import { adverseEventTypeLabels } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/adverse-events';
import { show as showAiSystem } from '@/routes/ai-systems';
import type { AdverseEvent, Paginated } from '@/types/models';

type Props = {
    adverseEvents: Paginated<AdverseEvent>;
};

export default function AdverseEventsIndex({ adverseEvents }: Props) {
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

                {adverseEvents.data.length === 0 ? (
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
                        <TableHead>Tipo</TableHead>
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
                                <Badge variant="outline">
                                    {adverseEventTypeLabels[event.event_type]}
                                </Badge>
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
