import { Head, Link } from '@inertiajs/react';
import { EntryAuthor } from '@/components/entry-author';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { StatusTransition } from '@/components/status-transition';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { evidenceTypeLabels, linkLabel } from '@/lib/labels';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { index as historyIndex } from '@/routes/links/status-histories';
import { show as showEvidence } from '@/routes/evidence';
import { show } from '@/routes/status-histories';
import type { Evidence, Link as RiskLink, StatusHistory } from '@/types/models';

type Props = {
    statusHistory: StatusHistory & { link: RiskLink };
    /** For a verification: the evidence it rested on. */
    supportingEvidence: Evidence[];
};

export default function StatusHistoriesShow({
    statusHistory,
    supportingEvidence,
}: Props) {
    const event = statusHistory.adverse_event;

    // Each entry changes one dimension (0013).
    const title =
        statusHistory.previous_verification === 'verified' &&
        statusHistory.new_verification === 'verified'
            ? 'Renovação da verificação'
            : statusHistory.new_verification !== null
              ? 'Mudança de verificação'
              : 'Mudança de status';

    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-2">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <StatusTransition entry={statusHistory} />
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
                                <EntryAuthor entry={statusHistory} />
                            </DetailItem>
                        </dl>
                        <dl>
                            {statusHistory.new_verification === 'verified' ? (
                                // A verification has no reason: it rests on
                                // the evidence that counted then (0013).
                                <DetailItem label="Evidências que embasaram a verificação">
                                    <SupportingEvidence
                                        evidence={supportingEvidence}
                                    />
                                </DetailItem>
                            ) : (
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
                            )}
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

function SupportingEvidence({ evidence }: { evidence: Evidence[] }) {
    if (evidence.length === 0) {
        return (
            <span className="text-muted-foreground font-normal">
                Nenhuma evidência encontrada
            </span>
        );
    }

    return (
        <ul className="grid gap-2 font-normal">
            {evidence.map((item) => (
                <li key={item.id} className="grid">
                    <Link
                        href={showEvidence(item.id)}
                        className="font-medium hover:underline"
                    >
                        {evidenceTypeLabels[item.type]} de{' '}
                        {formatDate(item.registration_date)}
                    </Link>
                    <span className="text-muted-foreground text-sm">
                        {item.description}
                    </span>
                </li>
            ))}
        </ul>
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
