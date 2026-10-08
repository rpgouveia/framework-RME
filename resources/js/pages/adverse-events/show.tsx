import { Head, Link } from '@inertiajs/react';
import { EntryAuthor } from '@/components/entry-author';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { StatusTransition } from '@/components/status-transition';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    adverseEventNatureBadgeClasses,
    adverseEventNatureLabels,
    linkLabel,
    termLabel,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { index, show } from '@/routes/adverse-events';
import { show as showAiSystem } from '@/routes/ai-systems';
import { show as showLink } from '@/routes/links';
import { create as createEvidence } from '@/routes/links/evidence';
import { create as createRisk } from '@/routes/risks';
import type { AdverseEvent, StatusHistory } from '@/types/models';

type Props = {
    adverseEvent: AdverseEvent;
    /** Days from the occurrence to the detection; null when not recorded. */
    detectionDelayDays: number | null;
    /** The event's subdomains with no risk registered for the system. */
    unmappedSubdomainCodes: string[];
};

export default function AdverseEventsShow({
    adverseEvent,
    detectionDelayDays,
    unmappedSubdomainCodes,
}: Props) {
    const reversals = adverseEvent.reversals ?? [];
    // The other entries that name the event, recorded by hand.
    const changes = (adverseEvent.status_histories ?? []).filter(
        (change) => change.origin !== 'adverse_event',
    );
    const interceptor = adverseEvent.intercepting_link;

    return (
        <>
            <Head title={eventTitle(adverseEvent)} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-1">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {eventTitle(adverseEvent)}
                        </h1>
                        <Badge
                            className={
                                adverseEventNatureBadgeClasses[
                                    adverseEvent.nature
                                ]
                            }
                        >
                            {adverseEventNatureLabels[adverseEvent.nature]}
                        </Badge>
                    </div>
                    {adverseEvent.ai_system && (
                        <p className="text-muted-foreground text-sm">
                            <Link
                                href={showAiSystem(adverseEvent.ai_system.id)}
                                className="hover:underline"
                            >
                                {adverseEvent.ai_system.name}
                            </Link>
                            {adverseEvent.ai_system.application_domain &&
                                ` · ${adverseEvent.ai_system.application_domain}`}
                        </p>
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
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label="Natureza">
                                {adverseEventNatureLabels[adverseEvent.nature]}
                            </DetailItem>
                            <DetailItem label="Data de ocorrência">
                                {formatDate(adverseEvent.occurrence_date)}
                            </DetailItem>
                            <DetailItem label="Data de detecção">
                                {adverseEvent.detected_at ? (
                                    <span className="grid">
                                        <span>
                                            {formatDate(
                                                adverseEvent.detected_at,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground text-xs font-normal">
                                            {detectionDelayDays === 0
                                                ? 'Detectado no mesmo dia'
                                                : detectionDelayDays === 1
                                                  ? 'Detectado 1 dia depois'
                                                  : `Detectado ${detectionDelayDays} dias depois`}
                                        </span>
                                    </span>
                                ) : (
                                    <span className="text-muted-foreground font-normal">
                                        Não informada
                                    </span>
                                )}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Subdomínios de risco materializados">
                                <ul className="grid gap-3">
                                    {adverseEvent.risk_subdomains?.map(
                                        (subdomain) => (
                                            <li
                                                key={subdomain.code}
                                                className="grid gap-1"
                                            >
                                                <RiskSubdomain
                                                    subdomain={subdomain}
                                                />
                                                {unmappedSubdomainCodes.includes(
                                                    subdomain.code,
                                                ) && (
                                                    // A risk not yet mapped
                                                    // (0019, item 4).
                                                    <span className="flex flex-wrap items-center gap-2 text-sm font-normal text-amber-700 dark:text-amber-300">
                                                        Sem risco cadastrado
                                                        neste sistema.
                                                        <Link
                                                            href={createRisk({
                                                                query: {
                                                                    ai_system:
                                                                        adverseEvent.ai_system_id,
                                                                    subdomain:
                                                                        subdomain.code,
                                                                },
                                                            })}
                                                            className="font-medium underline underline-offset-4"
                                                        >
                                                            Cadastrar risco
                                                        </Link>
                                                    </span>
                                                )}
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

                {interceptor && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Vínculo que interceptou</CardTitle>
                            <CardDescription>
                                A mitigação deste vínculo barrou a ocorrência,
                                por isso ele não foi revertido. Registre uma
                                evidência nele para comprovar o funcionamento.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap items-center justify-between gap-4">
                            <Link
                                href={showLink(interceptor.id)}
                                className="font-medium hover:underline"
                            >
                                {linkLabel(interceptor)}
                            </Link>
                            <Button size="sm" asChild>
                                <Link href={createEvidence(interceptor.id)}>
                                    Registrar evidência
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Vínculos revertidos pelo evento</CardTitle>
                        <CardDescription>
                            Ao registrar o evento, os vínculos verificados do
                            sistema cujo risco está em algum dos subdomínios
                            voltaram a declarados, aguardando reavaliação.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {reversals.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Nenhum vínculo verificado foi revertido por este
                                evento.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {reversals.map((reversal) => (
                                    <li
                                        key={reversal.id}
                                        className="flex flex-wrap items-center justify-between gap-4 py-2"
                                    >
                                        {reversal.link && (
                                            <Link
                                                href={showLink(
                                                    reversal.link_id,
                                                )}
                                                className="font-medium hover:underline"
                                            >
                                                {linkLabel(reversal.link)}
                                            </Link>
                                        )}
                                        <StatusTransition entry={reversal} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {changes.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Outras mudanças que citam o evento
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChangesTable changes={changes} />
                        </CardContent>
                    </Card>
                )}
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
                            <StatusTransition entry={change} />
                        </TableCell>
                        <TableCell>
                            <EntryAuthor entry={change} />
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
