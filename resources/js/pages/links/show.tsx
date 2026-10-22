import { Head, Link } from '@inertiajs/react';
import { CostHelp } from '@/components/cost-help';
import { DangerZone } from '@/components/danger-zone';
import { LinkVerificationCard } from '@/components/link-verification-card';
import type { VerificationProps } from '@/components/link-verification-card';
import { ObservedCost } from '@/components/observed-cost';
import { DetailItem } from '@/components/detail-item';
import { UnacceptableTierAlert } from '@/components/unacceptable-tier-alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { categoryBadgeClasses } from '@/lib/labels';
import { interpolateNodes, translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel } from '@/lib/names';
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
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const reassessments = link.reassessments ?? [];
    const evidenceCount = link.evidence_count ?? 0;
    const historyCount = link.status_histories_count ?? 0;

    return (
        <>
            <Head title={linkLabel(link, t)} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-2">
                        <h1 className="flex flex-wrap items-baseline gap-x-2 text-[28px] leading-9 font-normal">
                            <Link
                                href={showRisk(link.risk_id)}
                                className="hover:underline"
                            >
                                {link.risk?.name}
                            </Link>
                            <span
                                className="text-muted-foreground font-normal"
                                aria-label={t('mitigated by')}
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
                        {link.risk?.ai_system && (
                            <div className="flex flex-wrap items-center gap-2">
                                <Link
                                    href={showAiSystem(link.risk.ai_system.id)}
                                    className="text-link hover:underline"
                                >
                                    {link.risk.ai_system.name}
                                </Link>
                                <Badge
                                    className={
                                        categoryBadgeClasses[
                                            link.risk.ai_system.category
                                        ]
                                    }
                                >
                                    {t('Tier :tier', {
                                        tier: enumLabels.AiSystemCategory[
                                            link.risk.ai_system.category
                                        ],
                                    })}
                                </Badge>
                            </div>
                        )}
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={edit(link.id)}>{t('Edit')}</Link>
                    </Button>
                </header>

                {link.risk?.ai_system?.category === 'unacceptable' && (
                    <UnacceptableTierAlert />
                )}

                <LinkVerificationCard
                    link={link}
                    verification={verification}
                    awaitingReassessment={awaitingReassessment}
                />

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Details')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <DetailItem label={t('Owner')}>
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
                            <DetailItem label={t('Lifecycle phase')}>
                                {
                                    enumLabels.LifecyclePhase[
                                        link.lifecycle_phase
                                    ]
                                }
                            </DetailItem>
                            <DetailItem
                                label={t('Estimated cost')}
                                help={<CostHelp />}
                            >
                                {enumLabels.CostLevel[link.estimated_cost]}
                            </DetailItem>
                            <DetailItem label={t('Observed cost')}>
                                <ObservedCost
                                    evidence={link.observed_cost_evidence}
                                />
                            </DetailItem>
                            <DetailItem label={t('Creation date')}>
                                {formatDate(link.creation_date)}
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                {(link.replaces || link.replaced_by) && (
                    <RelatedLinks link={link} />
                )}

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle>{t('Reassessments')}</CardTitle>
                        {awaitingReassessment && (
                            <Button size="sm" asChild>
                                <Link href={createReassessment(link.id)}>
                                    {t('Reassess')}
                                </Link>
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        {reassessments.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {awaitingReassessment
                                    ? t(
                                          'The last reversal awaits reassessment.',
                                      )
                                    : t(
                                          'This link has not been reassessed yet.',
                                      )}
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
                                            {t(':outcome on :date', {
                                                outcome:
                                                    enumLabels
                                                        .ReassessmentOutcome[
                                                        reassessment.outcome
                                                    ],
                                                date: formatDate(
                                                    reassessment.reassessment_date,
                                                ),
                                            })}
                                        </Link>
                                        <span className="text-muted-foreground text-sm">
                                            {reassessment.reversal &&
                                                t('Reversal by :origin', {
                                                    origin: enumLabels.ChangeOrigin[
                                                        reassessment.reversal
                                                            .origin
                                                    ].toLowerCase(),
                                                })}{' '}
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
                        title={t('Evidence')}
                        count={evidenceCount}
                        description={t(
                            'Documents and records that prove the mitigation is applied.',
                        )}
                        href={evidenceIndex(link.id)}
                        action={t('See evidence')}
                        secondary={{
                            href: createEvidence(link.id),
                            label: t('Register evidence'),
                        }}
                    />
                    <CountCard
                        title={t('Status history')}
                        count={historyCount}
                        description={t(
                            'Each change of status and of verification of the link, with who recorded it and when.',
                        )}
                        href={statusHistoriesIndex(link.id)}
                        action={t('See history')}
                        secondary={{
                            href: createStatusChange(link.id),
                            label: t('Record change'),
                        }}
                    />
                </div>

                {link.status !== 'cancelled' && (
                    <DangerZone
                        title={t('Cancel this link')}
                        description={t(
                            'The link is not deleted: it leaves the periodic review and can only go back to Planned.',
                        )}
                    >
                        <Button variant="destructive" asChild>
                            <Link
                                href={createStatusChange(link.id, {
                                    query: { new_status: 'cancelled' },
                                })}
                            >
                                {t('Cancel link')}
                            </Link>
                        </Button>
                    </DangerZone>
                )}
            </div>
        </>
    );
}

/** The link this one replaced, or the one that replaced it (0020). */
function RelatedLinks({ link }: { link: RiskLink }) {
    const { t } = useTranslations();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Replacement')}</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2 text-sm">
                {link.replaces && (
                    <p>
                        {interpolateNodes(
                            t(
                                'This link replaces :link, cancelled in a reassessment.',
                            ),
                            {
                                link: (
                                    <Link
                                        href={show(link.replaces.id)}
                                        className="font-medium underline underline-offset-4"
                                    >
                                        {link.replaces.mitigation?.name}
                                    </Link>
                                ),
                            },
                        )}
                    </p>
                )}
                {link.replaced_by && (
                    <p>
                        {interpolateNodes(
                            t('This link was replaced by :link.'),
                            {
                                link: (
                                    <Link
                                        href={show(link.replaced_by.id)}
                                        className="font-medium underline underline-offset-4"
                                    >
                                        {link.replaced_by.mitigation?.name}
                                    </Link>
                                ),
                            },
                        )}
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

LinksShow.layout = ({ link, translations }: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: index() },
            { title: linkLabel(link, t), href: show(link.id) },
        ],
    };
};
