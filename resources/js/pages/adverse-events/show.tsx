import { Head, Link } from '@inertiajs/react';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { StatusTransition } from '@/components/status-transition';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { linkLabel, termLabel } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { index, show } from '@/routes/adverse-events';
import { show as showAiSystem } from '@/routes/ai-systems';
import { show as showLink } from '@/routes/links';
import type { AdverseEvent, StatusHistory } from '@/types/models';

type Props = {
    adverseEvent: AdverseEvent;
};

export default function AdverseEventsShow({ adverseEvent }: Props) {
    const changes = adverseEvent.status_histories ?? [];

    return (
        <>
            <Head title={eventTitle(adverseEvent)} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-1">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {eventTitle(adverseEvent)}
                    </h1>
                    {adverseEvent.ai_system && (
                        <Link
                            href={showAiSystem(adverseEvent.ai_system.id)}
                            className="text-muted-foreground text-sm hover:underline"
                        >
                            {adverseEvent.ai_system.name}
                        </Link>
                    )}
                </header>

                <p className="text-muted-foreground -mt-2 text-sm">
                    Eventos adversos não podem ser editados nem excluídos: eles
                    registram o que aconteceu e explicam as mudanças de status
                    que disparam.
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl>
                            <DetailItem label="Data de ocorrência">
                                {formatDate(adverseEvent.occurrence_date)}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Subdomínios de risco materializados">
                                <ul className="grid gap-3">
                                    {adverseEvent.risk_subdomains?.map(
                                        (subdomain) => (
                                            <li key={subdomain.code}>
                                                <RiskSubdomain
                                                    subdomain={subdomain}
                                                />
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Descrição">
                                <p className="font-normal whitespace-pre-line">
                                    {adverseEvent.description}
                                </p>
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Mudanças de status disparadas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {changes.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Este evento ainda não disparou mudanças de
                                status.
                            </p>
                        ) : (
                            <ChangesTable changes={changes} />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function ChangesTable({ changes }: { changes: StatusHistory[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Vínculo</TableHead>
                    <TableHead>Transição</TableHead>
                    <TableHead>Responsável</TableHead>
                    <TableHead>Data</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {changes.map((change) => (
                    <TableRow
                        key={change.id}
                        className="cursor-pointer"
                        onClick={rowLink(showLink(change.link_id))}
                    >
                        <TableCell className="whitespace-normal">
                            {change.link && (
                                <Link
                                    href={showLink(change.link_id)}
                                    className="font-medium hover:underline"
                                >
                                    {linkLabel(change.link)}
                                </Link>
                            )}
                        </TableCell>
                        <TableCell>
                            <StatusTransition
                                from={change.previous_status}
                                to={change.new_status}
                            />
                        </TableCell>
                        <TableCell>
                            {change.owner && (
                                <span className="grid">
                                    <span>
                                        {change.owner.organizational_role}
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        {change.owner.area}
                                    </span>
                                </span>
                            )}
                        </TableCell>
                        <TableCell>{formatDate(change.change_date)}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

/** The event has no name of its own: it is told by its subdomains. */
function eventTitle(event: AdverseEvent): string {
    const subdomains = event.risk_subdomains ?? [];

    return subdomains.length === 1
        ? `Evento adverso: ${termLabel(subdomains[0])}`
        : `Evento adverso: ${subdomains.map((term) => term.code).join(', ')}`;
}

AdverseEventsShow.layout = ({ adverseEvent }: Props) => ({
    breadcrumbs: [
        { title: 'Eventos adversos', href: index() },
        {
            title: eventTitle(adverseEvent),
            href: show(adverseEvent.id),
        },
    ],
});
