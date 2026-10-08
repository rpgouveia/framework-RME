import { Head, Link } from '@inertiajs/react';
import { DetailItem } from '@/components/detail-item';
import { EntryAuthor } from '@/components/entry-author';
import { Badge } from '@/components/ui/badge';
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
import {
    causeStatusLabels,
    changeOriginLabels,
    costLevelLabels,
    lifecyclePhaseLabels,
    linkLabel,
    linkStatusLabels,
    reassessmentFieldLabels,
    reassessmentOutcomeLabels,
    subdomainCodes,
    systemChangeTypeLabels,
} from '@/lib/labels';
import { show as showAdverseEvent } from '@/routes/adverse-events';
import { show as showSystemChange } from '@/routes/system-changes';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { index as reassessmentsIndex } from '@/routes/links/reassessments';
import { show } from '@/routes/reassessments';
import { show as showStatusHistory } from '@/routes/status-histories';
import type {
    CostLevel,
    LifecyclePhase,
    LinkStatus,
    Owner,
    Reassessment,
    ReassessmentChange,
} from '@/types/models';

type Props = {
    reassessment: Reassessment;
    /** The roles named in an owner change, keyed by id. */
    changedOwners: Record<
        string,
        Pick<Owner, 'id' | 'organizational_role' | 'area'>
    >;
};

export default function ReassessmentsShow({
    reassessment,
    changedOwners,
}: Props) {
    const link = reassessment.link;
    const reversal = reassessment.reversal;
    const changes = reassessment.changes ?? [];

    // A value of a change, as the screens name it.
    function valueLabel(change: ReassessmentChange, value: string | null) {
        if (value === null) {
            return '—';
        }

        switch (change.field) {
            case 'owner_id':
                return changedOwners[value]?.organizational_role ?? value;
            case 'estimated_cost':
                return costLevelLabels[value as CostLevel];
            case 'lifecycle_phase':
                return lifecyclePhaseLabels[value as LifecyclePhase];
            case 'status':
                return linkStatusLabels[value as LinkStatus];
        }
    }

    return (
        <>
            <Head title="Reavaliação" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-2">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Reavaliação
                        </h1>
                        <Badge variant="outline">
                            {reassessmentOutcomeLabels[reassessment.outcome]}
                        </Badge>
                    </div>
                    {link && (
                        <Link
                            href={showLink(link.id)}
                            className="text-muted-foreground text-sm hover:underline"
                        >
                            {linkLabel(link)}
                        </Link>
                    )}
                </header>

                <p className="text-muted-foreground -mt-2 text-sm">
                    Reavaliações não podem ser editadas nem excluídas: elas
                    registram o que foi concluído, por quem e por quê.
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>Decisão</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label="Desfecho">
                                {
                                    reassessmentOutcomeLabels[
                                        reassessment.outcome
                                    ]
                                }
                            </DetailItem>
                            <DetailItem label="Data">
                                {formatDate(reassessment.reassessment_date)}
                            </DetailItem>
                            <DetailItem label="Quem reavaliou">
                                {reassessment.owner && (
                                    <span className="grid">
                                        <span>
                                            {
                                                reassessment.owner
                                                    .organizational_role
                                            }
                                        </span>
                                        <span className="text-muted-foreground text-xs font-normal">
                                            {reassessment.owner.area}
                                        </span>
                                    </span>
                                )}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Justificativa">
                                <p className="font-normal whitespace-pre-line">
                                    {reassessment.justification}
                                </p>
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Análise de causa</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label="Situação">
                                {causeStatusLabels[reassessment.cause_status]}
                            </DetailItem>
                            {reassessment.cause_status === 'identified' && (
                                <>
                                    <DetailItem label="Fase de origem">
                                        {reassessment.cause_phase &&
                                            lifecyclePhaseLabels[
                                                reassessment.cause_phase
                                            ]}
                                    </DetailItem>
                                    <DetailItem label="Causa apurada">
                                        <p className="font-normal whitespace-pre-line">
                                            {reassessment.cause}
                                        </p>
                                    </DetailItem>
                                </>
                            )}
                            {reassessment.cause_status === 'not_applicable' && (
                                <p className="text-muted-foreground text-sm sm:col-span-2">
                                    Uma reversão por vencimento ou por
                                    reclassificação do sistema não tem causa a
                                    apurar.
                                </p>
                            )}
                        </dl>
                    </CardContent>
                </Card>

                {reversal && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Reversão reavaliada</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <dl className="grid gap-4 sm:grid-cols-3">
                                <DetailItem label="Origem">
                                    {changeOriginLabels[reversal.origin]}
                                </DetailItem>
                                <DetailItem label="Data">
                                    <Link
                                        href={showStatusHistory(reversal.id)}
                                        className="hover:underline"
                                    >
                                        {formatDate(reversal.change_date)}
                                    </Link>
                                </DetailItem>
                                <DetailItem label="Registrada por">
                                    <EntryAuthor entry={reversal} />
                                </DetailItem>
                            </dl>
                            {reversal.trigger_reason && (
                                <dl>
                                    <DetailItem label="Motivo">
                                        <p className="font-normal">
                                            {reversal.trigger_reason}
                                        </p>
                                    </DetailItem>
                                </dl>
                            )}
                            {reversal.system_change && (
                                <dl>
                                    <DetailItem label="Mudança do sistema">
                                        <Link
                                            href={showSystemChange(
                                                reversal.system_change.id,
                                            )}
                                            className="hover:underline"
                                        >
                                            {
                                                systemChangeTypeLabels[
                                                    reversal.system_change.type
                                                ]
                                            }{' '}
                                            de{' '}
                                            {formatDate(
                                                reversal.system_change
                                                    .change_date,
                                            )}
                                        </Link>
                                    </DetailItem>
                                </dl>
                            )}
                            {reversal.adverse_event && (
                                <dl>
                                    <DetailItem label="Evento adverso">
                                        <Link
                                            href={showAdverseEvent(
                                                reversal.adverse_event.id,
                                            )}
                                            className="hover:underline"
                                        >
                                            {subdomainCodes(
                                                reversal.adverse_event,
                                            )}{' '}
                                            de{' '}
                                            {formatDate(
                                                reversal.adverse_event
                                                    .occurrence_date,
                                            )}
                                        </Link>
                                    </DetailItem>
                                </dl>
                            )}
                        </CardContent>
                    </Card>
                )}

                {reassessment.outcome === 'adjust' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Mudanças aplicadas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {changes.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Nenhum campo do vínculo foi alterado.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Campo</TableHead>
                                            <TableHead>Antes</TableHead>
                                            <TableHead>Depois</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {changes.map((change) => (
                                            <TableRow key={change.field}>
                                                <TableCell>
                                                    {
                                                        reassessmentFieldLabels[
                                                            change.field
                                                        ]
                                                    }
                                                </TableCell>
                                                <TableCell>
                                                    {valueLabel(
                                                        change,
                                                        change.before,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {valueLabel(
                                                        change,
                                                        change.after,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Verificação e substituição</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 text-sm">
                        {reassessment.verification ? (
                            <p>
                                O vínculo foi verificado no mesmo ato,{' '}
                                <Link
                                    href={showStatusHistory(
                                        reassessment.verification.id,
                                    )}
                                    className="font-medium underline underline-offset-4"
                                >
                                    em{' '}
                                    {formatDate(
                                        reassessment.verification.change_date,
                                    )}
                                </Link>
                                .
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                {reassessment.outcome === 'adjust'
                                    ? 'Sem verificação no mesmo ato: o vínculo passou a aguardar verificação.'
                                    : 'Sem verificação no mesmo ato.'}
                            </p>
                        )}
                        {reassessment.outcome === 'replace' &&
                            (link?.replaced_by ? (
                                <p>
                                    Substituído por{' '}
                                    <Link
                                        href={showLink(link.replaced_by.id)}
                                        className="font-medium underline underline-offset-4"
                                    >
                                        {link.replaced_by.mitigation?.name}
                                    </Link>
                                    .
                                </p>
                            ) : (
                                <p className="text-muted-foreground">
                                    O vínculo substituto ainda não foi criado.
                                </p>
                            ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ReassessmentsShow.layout = ({ reassessment }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        ...(reassessment.link
            ? [
                  {
                      title: linkLabel(reassessment.link),
                      href: showLink(reassessment.link.id),
                  },
                  {
                      title: 'Reavaliações',
                      href: reassessmentsIndex(reassessment.link.id),
                  },
              ]
            : []),
        { title: 'Reavaliação', href: show(reassessment.id) },
    ],
});
