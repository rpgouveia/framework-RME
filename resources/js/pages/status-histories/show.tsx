import { Head, Link } from '@inertiajs/react';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { StatusTransition } from '@/components/status-transition';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { linkLabel } from '@/lib/labels';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { index as historyIndex } from '@/routes/links/status-histories';
import { show } from '@/routes/status-histories';
import type { Link as RiskLink, StatusHistory } from '@/types/models';

type Props = {
    statusHistory: StatusHistory & { link: RiskLink };
};

export default function StatusHistoriesShow({ statusHistory }: Props) {
    const event = statusHistory.adverse_event;

    return (
        <>
            <Head title="Mudança de status" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-2">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Mudança de status
                    </h1>
                    <StatusTransition
                        from={statusHistory.previous_status}
                        to={statusHistory.new_status}
                    />
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label="Vínculo">
                                <Link
                                    href={showLink(statusHistory.link_id)}
                                    className="hover:underline"
                                >
                                    {linkLabel(statusHistory.link)}
                                </Link>
                            </DetailItem>
                            <DetailItem label="Data da mudança">
                                {formatDate(statusHistory.change_date)}
                            </DetailItem>
                            <DetailItem label="Registrado por">
                                {statusHistory.owner && (
                                    <span className="grid">
                                        <span>
                                            {
                                                statusHistory.owner
                                                    .organizational_role
                                            }
                                        </span>
                                        <span className="text-muted-foreground text-sm font-normal">
                                            {statusHistory.owner.area}
                                        </span>
                                    </span>
                                )}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Motivo">
                                {statusHistory.trigger_reason ? (
                                    <p className="max-w-prose font-normal whitespace-pre-line">
                                        {statusHistory.trigger_reason}
                                    </p>
                                ) : (
                                    <span className="text-muted-foreground font-normal">
                                        Não informado
                                    </span>
                                )}
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                {event && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Evento adverso relacionado</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-6">
                            <dl className="grid gap-4 sm:grid-cols-3">
                                <DetailItem label="Subdomínios de risco">
                                    <ul className="grid gap-2">
                                        {event.risk_subdomains?.map(
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
                                <DetailItem label="Ocorrência">
                                    {formatDate(event.occurrence_date)}
                                </DetailItem>
                                <DetailItem label="Sistema de IA">
                                    {event.ai_system?.name}
                                </DetailItem>
                            </dl>
                            <dl>
                                <DetailItem label="Descrição">
                                    <p className="max-w-prose font-normal whitespace-pre-line">
                                        {event.description}
                                    </p>
                                </DetailItem>
                            </dl>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

StatusHistoriesShow.layout = ({ statusHistory }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        {
            title: linkLabel(statusHistory.link),
            href: showLink(statusHistory.link_id),
        },
        { title: 'Histórico', href: historyIndex(statusHistory.link_id) },
        {
            title: formatDate(statusHistory.change_date),
            href: show(statusHistory.id),
        },
    ],
});
