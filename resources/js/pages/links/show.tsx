import { Head, Link } from '@inertiajs/react';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { LinkVerificationCard } from '@/components/link-verification-card';
import type { VerificationProps } from '@/components/link-verification-card';
import { ObservedCost } from '@/components/observed-cost';
import { ReviewDate } from '@/components/review-date';
import { DetailItem } from '@/components/detail-item';
import { UnacceptableTierAlert } from '@/components/unacceptable-tier-alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import {
    changeOriginLabels,
    costLevelLabels,
    lifecyclePhaseLabels,
    linkLabel,
    reassessmentOutcomeLabels,
} from '@/lib/labels';
import { show as showAiSystem } from '@/routes/ai-systems';
import { edit, index, show } from '@/routes/links';
import {
    create as createEvidence,
    index as evidenceIndex,
} from '@/routes/links/evidence';
import {
    create as createStatusChange,
    index as statusHistoriesIndex,
} from '@/routes/links/status-histories';
import { show as showMitigation } from '@/routes/mitigations';
import { show as showReassessment } from '@/routes/reassessments';
import { show as showRisk } from '@/routes/risks';
import { create as createReassessment } from '@/routes/links/reassessments';
import type { Link as RiskLink } from '@/types/models';

type Props = {
    link: RiskLink;
    verification: VerificationProps;
    /** A reversal awaits its reassessment (0020). */
    awaitingReassessment: boolean;
};

export default function LinksShow({
    link,
    verification,
    awaitingReassessment,
}: Props) {
    const reassessments = link.reassessments ?? [];
    const evidenceCount = link.evidence_count ?? 0;
    const historyCount = link.status_histories_count ?? 0;

    return (
        <>
            <Head title={linkLabel(link)} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-2">
                        <h1 className="flex flex-wrap items-baseline gap-x-2 text-xl font-semibold tracking-tight">
                            <Link
                                href={showRisk(link.risk_id)}
                                className="hover:underline"
                            >
                                {link.risk?.name}
                            </Link>
                            <span
                                className="text-muted-foreground font-normal"
                                aria-label="mitigado por"
                            >
                                →
                            </span>
                            <Link
                                href={showMitigation(link.mitigation_id)}
                                className="hover:underline"
                            >
                                {link.mitigation?.name}
                            </Link>
                        </h1>
                        <div className="flex flex-wrap items-center gap-2 text-sm">
                            <LinkStatusBadges link={link} />
                            {link.risk?.ai_system && (
                                <Link
                                    href={showAiSystem(link.risk.ai_system.id)}
                                    className="text-muted-foreground hover:underline"
                                >
                                    {link.risk.ai_system.name}
                                </Link>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(link.id)}>Editar</Link>
                        </Button>
                        {awaitingReassessment && (
                            <Button asChild>
                                <Link href={createReassessment(link.id)}>
                                    Reavaliar
                                </Link>
                            </Button>
                        )}
                        {/* Links are permanent: they are closed by cancelling
                            them through the status history, and reopened the
                            same way, since the pair cannot be linked twice. */}
                        {link.status === 'cancelled' ? (
                            <Button asChild>
                                <Link
                                    href={createStatusChange(link.id, {
                                        query: { new_status: 'planned' },
                                    })}
                                >
                                    Reativar vínculo
                                </Link>
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                className="text-destructive"
                                asChild
                            >
                                <Link
                                    href={createStatusChange(link.id, {
                                        query: { new_status: 'cancelled' },
                                    })}
                                >
                                    Cancelar vínculo
                                </Link>
                            </Button>
                        )}
                    </div>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <DetailItem label="Responsável">
                                {link.owner && (
                                    <span className="grid">
                                        <span>
                                            {link.owner.organizational_role}
                                        </span>
                                        <span className="text-muted-foreground text-sm font-normal">
                                            {link.owner.area}
                                        </span>
                                    </span>
                                )}
                            </DetailItem>
                            <DetailItem label="Fase do ciclo de vida">
                                {lifecyclePhaseLabels[link.lifecycle_phase]}
                            </DetailItem>
                            <DetailItem label="Custo estimado">
                                {costLevelLabels[link.estimated_cost]}
                            </DetailItem>
                            <DetailItem label="Custo observado">
                                <ObservedCost
                                    evidence={link.observed_cost_evidence}
                                />
                            </DetailItem>
                            <DetailItem label="Data de criação">
                                {formatDate(link.creation_date)}
                            </DetailItem>
                            <DetailItem label="Próxima revisão">
                                <ReviewDate
                                    date={link.next_review_date}
                                    verification={link.verification_status}
                                    unacceptable={
                                        link.risk?.ai_system?.category ===
                                        'unacceptable'
                                    }
                                />
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                {link.risk?.ai_system?.category === 'unacceptable' && (
                    <UnacceptableTierAlert />
                )}

                <LinkVerificationCard link={link} verification={verification} />

                {(link.replaces || link.replaced_by) && (
                    <RelatedLinks link={link} />
                )}

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle>Reavaliações</CardTitle>
                        {awaitingReassessment && (
                            <Button size="sm" asChild>
                                <Link href={createReassessment(link.id)}>
                                    Reavaliar
                                </Link>
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        {reassessments.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {awaitingReassessment
                                    ? 'A última reversão aguarda reavaliação.'
                                    : 'Este vínculo ainda não foi reavaliado.'}
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {reassessments.map((reassessment) => (
                                    <li
                                        key={reassessment.id}
                                        className="flex flex-wrap items-center justify-between gap-4 py-2"
                                    >
                                        <Link
                                            href={showReassessment(
                                                reassessment.id,
                                            )}
                                            className="font-medium hover:underline"
                                        >
                                            {
                                                reassessmentOutcomeLabels[
                                                    reassessment.outcome
                                                ]
                                            }{' '}
                                            em{' '}
                                            {formatDate(
                                                reassessment.reassessment_date,
                                            )}
                                        </Link>
                                        <span className="text-muted-foreground text-sm">
                                            {reassessment.reversal &&
                                                `Reversão por ${changeOriginLabels[reassessment.reversal.origin].toLowerCase()}`}{' '}
                                            ·{' '}
                                            {
                                                reassessment.owner
                                                    ?.organizational_role
                                            }
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-6 md:grid-cols-2">
                    <CountCard
                        title="Evidências"
                        count={evidenceCount}
                        description="Documentos e registros que comprovam a aplicação da mitigação."
                        href={evidenceIndex(link.id)}
                        action="Ver evidências"
                        secondary={{
                            href: createEvidence(link.id),
                            label: 'Registrar evidência',
                        }}
                    />
                    <CountCard
                        title="Histórico de status"
                        count={historyCount}
                        description="Cada mudança de status e de verificação do vínculo, com quem a registrou e quando."
                        href={statusHistoriesIndex(link.id)}
                        action="Ver histórico"
                        secondary={{
                            href: createStatusChange(link.id),
                            label: 'Registrar mudança',
                        }}
                    />
                </div>
            </div>
        </>
    );
}

/** The link this one replaced, or the one that replaced it (0020). */
function RelatedLinks({ link }: { link: RiskLink }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Substituição</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2 text-sm">
                {link.replaces && (
                    <p>
                        Este vínculo substitui{' '}
                        <Link
                            href={show(link.replaces.id)}
                            className="font-medium underline underline-offset-4"
                        >
                            {link.replaces.mitigation?.name}
                        </Link>
                        , cancelado numa reavaliação.
                    </p>
                )}
                {link.replaced_by && (
                    <p>
                        Este vínculo foi substituído por{' '}
                        <Link
                            href={show(link.replaced_by.id)}
                            className="font-medium underline underline-offset-4"
                        >
                            {link.replaced_by.mitigation?.name}
                        </Link>
                        .
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function CountCard({
    title,
    count,
    description,
    href,
    action,
    secondary,
}: {
    title: string;
    count: number;
    description: string;
    href: ReturnType<typeof evidenceIndex>;
    action: string;
    secondary?: { href: ReturnType<typeof evidenceIndex>; label: string };
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="flex items-center justify-between gap-4">
                <span className="text-3xl font-semibold tabular-nums">
                    {count}
                </span>
                <div className="flex flex-wrap justify-end gap-2">
                    {secondary && (
                        <Button variant="ghost" asChild>
                            <Link href={secondary.href}>{secondary.label}</Link>
                        </Button>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={href}>{action}</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

LinksShow.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: index() },
        { title: linkLabel(link), href: show(link.id) },
    ],
});
