import { Head, Link } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { WaitingDays } from '@/components/waiting-days';
import Heading from '@/components/heading';
import { AiSystemName } from '@/components/ai-system-name';
import { ReviewDate } from '@/components/review-date';
import { FilterLink } from '@/components/filter-link';
import { VerificationBadge } from '@/components/link-status-badges';
import { RiskSubdomainBadges } from '@/components/risk-subdomain-badges';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { dateInputValue, formatDate } from '@/lib/format';
import { interpolateNodes, translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { categoryBadgeClasses, linkStatusBadgeClasses } from '@/lib/labels';
import { linkLabel } from '@/lib/names';
import { linksWith } from '@/lib/link-filters';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    index as adverseEventsIndex,
    show as showAdverseEvent,
} from '@/routes/adverse-events';
import {
    create as createAiSystem,
    show as showAiSystem,
} from '@/routes/ai-systems';
import { create as createLink, show as showLink } from '@/routes/links';
import { create as createEvidence } from '@/routes/links/evidence';
import { create as createReassessment } from '@/routes/links/reassessments';
import { show as showSystemChange } from '@/routes/system-changes';
import {
    create as createRisk,
    index as risksIndex,
    show as showRisk,
} from '@/routes/risks';
import type {
    AdverseEvent,
    AiSystem,
    ChangeOrigin,
    Link as RiskLink,
    LinkStatus,
    Risk,
} from '@/types/models';

type Pending<T> = { count: number; items: T[] };

type SystemSummary = Pick<
    AiSystem,
    'id' | 'name' | 'application_domain' | 'category'
> & {
    risks_count: number;
    unlinked_risks_count: number;
    links_count: number;
    awaiting_reassessment_count: number;
    recent_events_count: number;
};

/** An event subdomain with no risk registered for the system (0019). */
type UnmappedRisk = {
    ai_system: { id: number; name: string };
    subdomain: { code: string; name: string; domain: string | null };
    /** Where the case came from: adverse events, system changes, or both. */
    sources: ('adverse_event' | 'system_change')[];
    events_count: number;
    latest_event: { id: number; occurrence_date: string } | null;
    changes_count: number;
    latest_change: { id: number; change_date: string } | null;
};

type Props = {
    totals: {
        aiSystems: number;
        risks: number;
        links: number;
        mitigations: number;
        owners: number;
    };
    unlinkedRisks: Pending<Risk>;
    linksByStatus: { status: LinkStatus; count: number }[];
    linksWithoutEvidence: Pending<RiskLink>;
    recentEvents: Pending<AdverseEvent> & { days: number };
    reviews: {
        upcomingDays: number;
        upcomingCount: number;
        upcoming: RiskLink[];
    };
    systems: SystemSummary[];
    /** The verification pending lists of Tela 3 (0018). */
    verification: {
        awaitingVerification: number;
        awaitingReassessment: number;
        /** Split by the origin of the last reversal (0019). */
        awaitingReassessmentByOrigin: { origin: ChangeOrigin; count: number }[];
        verified: number;
        /** The links waiting longest for their reassessment (0020). */
        longestAwaiting: RiskLink[];
    };
    /** Risks not yet mapped: event subdomains with no risk (0019). */
    unmappedRisks: UnmappedRisk[];
    /** Systems that may not operate, left out of the review indicators. */
    unacceptableSystems: Pick<AiSystem, 'id' | 'name'>[];
};

/** Tones for the review labels of the reassessment screen (Tela 5). */
const reviewBadge = {
    dueToday: 'border-transparent bg-tag-yellow text-tag-yellow-foreground',
    onTrack: 'border-transparent bg-tag-green text-tag-green-foreground',
};

/** A pending count that is not zero reads in the warning colour. */
const pendingTone = 'text-tag-yellow-foreground font-semibold';

export default function Dashboard(props: Props) {
    const { t } = useTranslations();
    const { totals } = props;

    return (
        <>
            <Head title={t('Dashboard')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <div className="flex flex-wrap items-end justify-between gap-6">
                    <Heading
                        title={t('Dashboard')}
                        description={t(
                            'Where the traceability chain is still incomplete.',
                        )}
                    />
                    {totals.aiSystems > 0 && (
                        <dl className="flex flex-wrap gap-x-8 gap-y-2">
                            <Total
                                label={t('AI systems')}
                                value={totals.aiSystems}
                            />
                            <Total label={t('Risks')} value={totals.risks} />
                            <Total label={t('Links')} value={totals.links} />
                            <Total
                                label={t('Mitigations in the catalogue')}
                                value={totals.mitigations}
                            />
                            <Total
                                label={t('Active owners')}
                                value={totals.owners}
                            />
                        </dl>
                    )}
                </div>
                {totals.aiSystems === 0 ? <Welcome /> : <Overview {...props} />}
            </div>
        </>
    );
}

/** With nothing registered, zeroed cards say nothing: point to step one. */
function Welcome() {
    const { t } = useTranslations();

    return (
        <div className="flex max-w-2xl flex-col items-start gap-4 border border-dashed p-8">
            <h2 className="text-xl">{t('Start with the AI system')}</h2>
            <p className="text-muted-foreground">
                {t(
                    "The framework's cycle starts from a registered AI system: the risks, the mitigations applied, the evidence and the adverse events tie to it. Register the first system for the dashboard to start showing what is missing at each step.",
                )}
            </p>
            <Button asChild>
                <Link href={createAiSystem()}>{t('Register AI system')}</Link>
            </Button>
        </div>
    );
}

function Overview({
    unlinkedRisks,
    linksByStatus,
    linksWithoutEvidence,
    recentEvents,
    reviews,
    systems,
    unacceptableSystems,
    verification,
    unmappedRisks,
}: Props) {
    const { t } = useTranslations();

    return (
        <>
            {unacceptableSystems.length > 0 && (
                <UnacceptableSystemsAlert systems={unacceptableSystems} />
            )}

            <section aria-labelledby="needs-action" className="grid gap-4">
                <h2 id="needs-action" className="text-xl">
                    {t('What needs action')}
                </h2>
                <div className="grid gap-px border bg-[var(--border)] sm:grid-cols-2 lg:grid-cols-4">
                    <ActionTile
                        count={verification.awaitingReassessment}
                        title={t('Awaiting reassessment')}
                        sentence={t(
                            'Reverted to declared, waiting for a decision.',
                        )}
                        href={linksWith('awaiting_reassessment')}
                        action={t('See the queue')}
                    />
                    <ActionTile
                        count={verification.awaitingVerification}
                        title={t('Awaiting verification')}
                        sentence={t(
                            'Declared, waiting for the proof: never verified, or reassessed without verification.',
                        )}
                        href={linksWith('awaiting_verification')}
                        action={t('See in the list')}
                    />
                    <ActionTile
                        count={linksWithoutEvidence.count}
                        title={t('Links without evidence')}
                        sentence={t(
                            'Mitigations applied with nothing proving their execution.',
                        )}
                        href="#links-without-evidence"
                        action={t('See below')}
                    />
                    <ActionTile
                        count={unmappedRisks.length}
                        title={t('Risks not mapped')}
                        sentence={t(
                            'Subdomains cited by events or changes with no risk registered in the system.',
                        )}
                        href="#risks-not-mapped"
                        action={t('See below')}
                    />
                </div>
                <p className="text-muted-foreground flex flex-wrap gap-x-6 gap-y-1 text-sm">
                    <span>
                        {unlinkedRisks.count === 0
                            ? t('Every risk has at least one link.')
                            : interpolateNodes(
                                  t('Risks without a link: :count'),
                                  {
                                      count: (
                                          <a
                                              href="#risks-without-link"
                                              className={cn(
                                                  'hover:underline',
                                                  pendingTone,
                                              )}
                                          >
                                              {unlinkedRisks.count}
                                          </a>
                                      ),
                                  },
                              )}
                    </span>
                    <span>
                        {interpolateNodes(
                            t('Events in the last :days days: :count', {
                                days: recentEvents.days,
                            }),
                            {
                                count: (
                                    <Link
                                        href={adverseEventsIndex()}
                                        className="text-link hover:text-link-hover hover:underline"
                                    >
                                        {recentEvents.count}
                                    </Link>
                                ),
                            },
                        )}
                    </span>
                    <span>
                        {interpolateNodes(t('Verified links: :count'), {
                            count: (
                                <Link
                                    href={linksWith('verified')}
                                    className="text-link hover:text-link-hover hover:underline"
                                >
                                    {verification.verified}
                                </Link>
                            ),
                        })}
                    </span>
                </p>
            </section>

            <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <ReassessmentQueue counts={verification} />
                <div className="grid gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Upcoming reviews')}</CardTitle>
                            <CardDescription>
                                {t(
                                    'Due today or in the next :days days. The date is the last valid day: on the next day, the link goes back to declared.',
                                    { days: reviews.upcomingDays },
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {reviews.upcoming.length === 0 ? (
                                <Empty>
                                    {t(
                                        'No review due in the next :days days.',
                                        { days: reviews.upcomingDays },
                                    )}
                                </Empty>
                            ) : (
                                <ul className="divide-y">
                                    {reviews.upcoming.map((link) => (
                                        <ReviewItem key={link.id} link={link} />
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                    <UnmappedRisks risks={unmappedRisks} />
                </div>
            </div>

            <div className="grid items-start gap-6 lg:grid-cols-2">
                <Card id="links-without-evidence" className="scroll-mt-6">
                    <CardHeader>
                        <CardTitle>{t('Links without evidence')}</CardTitle>
                        <CardDescription>
                            {t('The oldest first.')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {linksWithoutEvidence.items.length === 0 ? (
                            <Empty>
                                {t(
                                    'Every link has at least one piece of evidence.',
                                )}
                            </Empty>
                        ) : (
                            <ul className="divide-y">
                                {linksWithoutEvidence.items.map((link) => (
                                    <li
                                        key={link.id}
                                        className="flex items-center justify-between gap-4 py-3"
                                    >
                                        <span className="grid gap-0.5">
                                            <Link
                                                href={showLink(link.id)}
                                                className="text-link hover:text-link-hover hover:underline"
                                            >
                                                {linkLabel(link, t)}
                                            </Link>
                                            <span className="text-muted-foreground text-xs tracking-[0.32px]">
                                                {t('Created on :date', {
                                                    date: formatDate(
                                                        link.creation_date,
                                                    ),
                                                })}
                                            </span>
                                        </span>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link
                                                href={createEvidence(link.id)}
                                            >
                                                {t('Register evidence')}
                                            </Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Recent events')}</CardTitle>
                        <CardDescription>
                            {t(
                                'Last :days days, from the most recent to the oldest.',
                                { days: recentEvents.days },
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {recentEvents.items.length === 0 ? (
                            <Empty>
                                {t('No adverse event in the last :days days.', {
                                    days: recentEvents.days,
                                })}
                            </Empty>
                        ) : (
                            <ul className="divide-y">
                                {recentEvents.items.map((event) => (
                                    <li
                                        key={event.id}
                                        className="flex items-center justify-between gap-4 py-3"
                                    >
                                        <span className="grid gap-1">
                                            <Link
                                                href={showAdverseEvent(
                                                    event.id,
                                                )}
                                                className="text-link hover:text-link-hover hover:underline"
                                            >
                                                {event.ai_system?.name}
                                            </Link>
                                            <RiskSubdomainBadges
                                                subdomains={
                                                    event.risk_subdomains
                                                }
                                            />
                                        </span>
                                        <span className="text-muted-foreground text-sm whitespace-nowrap">
                                            {formatDate(event.occurrence_date)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {unlinkedRisks.items.length > 0 && (
                    <Card id="risks-without-link" className="scroll-mt-6">
                        <CardHeader>
                            <CardTitle>{t('Risks without a link')}</CardTitle>
                            <CardDescription>
                                {t('The oldest first.')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul className="divide-y">
                                {unlinkedRisks.items.map((risk) => (
                                    <li
                                        key={risk.id}
                                        className="flex items-center justify-between gap-4 py-3"
                                    >
                                        <span className="grid gap-0.5">
                                            <Link
                                                href={showRisk(risk.id)}
                                                className="text-link hover:text-link-hover hover:underline"
                                            >
                                                {risk.name}
                                            </Link>
                                            <span className="text-muted-foreground text-xs tracking-[0.32px]">
                                                {risk.ai_system?.name}
                                            </span>
                                        </span>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link
                                                href={createLink({
                                                    query: { risk: risk.id },
                                                })}
                                            >
                                                {t('Create link')}
                                            </Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                            <Link
                                href={risksIndex()}
                                className="text-link hover:text-link-hover mt-3 inline-block text-sm hover:underline"
                            >
                                {t('See risks')}
                            </Link>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Links by progress')}</CardTitle>
                        <CardDescription>
                            {t(
                                'Progress of the mitigations applied; cancelled ones are left out.',
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <StatusBars rows={linksByStatus} />
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{t('By AI system')}</CardTitle>
                    <CardDescription>
                        {t('Pending counts show in yellow.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <SystemsTable systems={systems} />
                </CardContent>
            </Card>
        </>
    );
}

function Total({ label, value }: { label: string; value: number }) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-muted-foreground text-xs tracking-[0.32px]">
                {label}
            </dt>
            <dd className="text-xl tabular-nums">{value}</dd>
        </div>
    );
}

/** A pending count; it stands out when there is something to resolve. */
function ActionTile({
    count,
    title,
    sentence,
    href,
    action,
}: {
    count: number;
    title: string;
    sentence: string;
    href: NonNullable<InertiaLinkProps['href']>;
    action: string;
}) {
    const pending = count > 0;
    const body = (
        <>
            <span className="text-sm">{title}</span>
            <span
                className={cn(
                    'text-[42px] leading-[50px] font-light tabular-nums',
                    pending
                        ? 'text-tag-yellow-foreground'
                        : 'text-muted-foreground',
                )}
            >
                {count}
            </span>
            <span className="text-muted-foreground text-xs tracking-[0.32px]">
                {sentence}
            </span>
            <span className="text-link mt-auto pt-2 text-sm">{action} →</span>
        </>
    );
    const className =
        'bg-card hover:bg-accent focus-visible:outline-ring flex flex-col gap-1 p-4 outline-none focus-visible:outline-2 focus-visible:-outline-offset-2';

    // Anchors within the page are plain links; the rest go through Inertia.
    return typeof href === 'string' && href.startsWith('#') ? (
        <a href={href} className={className}>
            {body}
        </a>
    ) : (
        <Link href={href} className={className}>
            {body}
        </Link>
    );
}

/**
 * Systems in the unacceptable tier: prohibited practices that may not
 * operate. They stay out of the review indicators, so the dashboard says so.
 */
function UnacceptableSystemsAlert({
    systems,
}: {
    systems: Props['unacceptableSystems'];
}) {
    const { t, tc } = useTranslations();

    return (
        <Alert variant="destructive">
            <TriangleAlertIcon aria-hidden />
            <AlertTitle>
                {tc(
                    ':count system in the unacceptable tier of the EU AI Act|:count systems in the unacceptable tier of the EU AI Act',
                    systems.length,
                )}
            </AlertTitle>
            <AlertDescription>
                <p>
                    {t(
                        'Prohibited practices: these systems are not considered in operation and are left out of the review indicators. Their links serve to plan the discontinuation.',
                    )}
                </p>
                <ul className="flex flex-wrap gap-x-4 gap-y-1">
                    {systems.map((system) => (
                        <li key={system.id}>
                            <Link
                                href={showAiSystem(system.id)}
                                className="text-link hover:text-link-hover hover:underline"
                            >
                                {system.name}
                            </Link>
                        </li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}

/**
 * The reassessment queue (0020): how many links wait by the origin of their
 * last reversal (0019), and those waiting longest, each with its shortcut.
 */
function ReassessmentQueue({ counts }: { counts: Props['verification'] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <Card>
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-4">
                <div className="grid gap-1.5">
                    <CardTitle>{t('Reassessment queue')}</CardTitle>
                    <CardDescription>
                        {t(
                            'Those waiting longest first, by the origin of the last reversal.',
                        )}
                    </CardDescription>
                </div>
                <Link
                    href={linksWith('awaiting_reassessment')}
                    className="text-link hover:text-link-hover text-sm hover:underline"
                >
                    {t('See all :count', {
                        count: counts.awaitingReassessment,
                    })}
                </Link>
            </CardHeader>
            <CardContent className="grid gap-4">
                <nav
                    aria-label={t('Filter by the origin of the last reversal')}
                    className="flex flex-wrap gap-2"
                >
                    {counts.awaitingReassessmentByOrigin.map((row) => (
                        <FilterLink
                            key={row.origin}
                            href={linksWith(
                                'awaiting_reassessment',
                                row.origin,
                            )}
                            active={false}
                        >
                            {enumLabels.ChangeOrigin[row.origin]}
                            <span
                                className={cn(
                                    'tabular-nums',
                                    row.count > 0
                                        ? pendingTone
                                        : 'text-muted-foreground',
                                )}
                            >
                                {row.count}
                            </span>
                        </FilterLink>
                    ))}
                </nav>
                {counts.longestAwaiting.length === 0 ? (
                    <Empty>{t('No link awaits reassessment.')}</Empty>
                ) : (
                    <div className="overflow-x-auto border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('Link')}</TableHead>
                                    <TableHead>{t('Origin')}</TableHead>
                                    <TableHead>{t('Waiting for')}</TableHead>
                                    <TableHead>
                                        <span className="sr-only">
                                            {t('Action')}
                                        </span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {counts.longestAwaiting.map((link) => (
                                    <TableRow key={link.id}>
                                        <TableCell className="min-w-48 whitespace-normal">
                                            <Link
                                                href={showLink(link.id)}
                                                className="text-link hover:text-link-hover hover:underline"
                                            >
                                                {linkLabel(link, t)}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="whitespace-normal">
                                            {link.last_reversal &&
                                                enumLabels.ChangeOrigin[
                                                    link.last_reversal.origin
                                                ]}
                                        </TableCell>
                                        <TableCell>
                                            {link.last_reversal && (
                                                <WaitingDays
                                                    since={
                                                        link.last_reversal
                                                            .change_date
                                                    }
                                                />
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                asChild
                                            >
                                                <Link
                                                    href={createReassessment(
                                                        link.id,
                                                    )}
                                                >
                                                    {t('Reassess')}
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * Risks not yet mapped (0019, item 4): adverse events in subdomains where
 * the system has no risk registered, each with a shortcut to register it.
 * Worked out on every visit, so a case goes away once the risk exists.
 */
function UnmappedRisks({ risks }: { risks: UnmappedRisk[] }) {
    const { t, tc } = useTranslations();

    return (
        <Card id="risks-not-mapped" className="scroll-mt-6">
            <CardHeader>
                <CardTitle>{t('Risks not mapped')}</CardTitle>
                <CardDescription>
                    {t(
                        'Subdomains cited by adverse events or by system changes where the system has no registered risk: a risk not yet identified.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {risks.length === 0 ? (
                    <Empty>
                        {t(
                            'Every event is in a subdomain with a risk registered in the system.',
                        )}
                    </Empty>
                ) : (
                    <ul className="divide-y">
                        {risks.map((risk) => (
                            <li
                                key={`${risk.ai_system.id}-${risk.subdomain.code}`}
                                className="flex flex-wrap items-center justify-between gap-4 py-2"
                            >
                                <span className="grid gap-0.5">
                                    <span>
                                        {risk.subdomain.code}{' '}
                                        {risk.subdomain.name}
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        <Link
                                            href={showAiSystem(
                                                risk.ai_system.id,
                                            )}
                                            className="hover:underline"
                                        >
                                            {risk.ai_system.name}
                                        </Link>{' '}
                                        {risk.latest_event && (
                                            <>
                                                {' '}
                                                ·{' '}
                                                {interpolateNodes(
                                                    tc(
                                                        'Adverse event: :count event, the most recent on :date|Adverse event: :count events, the most recent on :date',
                                                        risk.events_count,
                                                    ),
                                                    {
                                                        date: (
                                                            <Link
                                                                href={showAdverseEvent(
                                                                    risk
                                                                        .latest_event
                                                                        .id,
                                                                )}
                                                                className="hover:underline"
                                                            >
                                                                {formatDate(
                                                                    risk
                                                                        .latest_event
                                                                        .occurrence_date,
                                                                )}
                                                            </Link>
                                                        ),
                                                    },
                                                )}
                                            </>
                                        )}
                                        {risk.latest_change && (
                                            <>
                                                {' '}
                                                ·{' '}
                                                {interpolateNodes(
                                                    tc(
                                                        'System change: :count change, the most recent on :date|System change: :count changes, the most recent on :date',
                                                        risk.changes_count,
                                                    ),
                                                    {
                                                        date: (
                                                            <Link
                                                                href={showSystemChange(
                                                                    risk
                                                                        .latest_change
                                                                        .id,
                                                                )}
                                                                className="hover:underline"
                                                            >
                                                                {formatDate(
                                                                    risk
                                                                        .latest_change
                                                                        .change_date,
                                                                )}
                                                            </Link>
                                                        ),
                                                    },
                                                )}
                                            </>
                                        )}
                                    </span>
                                </span>
                                <Button size="sm" variant="ghost" asChild>
                                    <Link
                                        href={createRisk({
                                            query: {
                                                ai_system: risk.ai_system.id,
                                                subdomain: risk.subdomain.code,
                                            },
                                        })}
                                    >
                                        {t('Register risk')}
                                    </Link>
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}

function ReviewItem({ link }: { link: RiskLink }) {
    const { t } = useTranslations();
    const dueToday =
        link.next_review_date !== null &&
        dateInputValue(link.next_review_date) === dateInputValue();

    return (
        <li className="flex items-center justify-between gap-4 py-3">
            <span className="grid">
                <Link
                    href={showLink(link.id)}
                    className="text-link hover:text-link-hover hover:underline"
                >
                    {linkLabel(link, t)}
                </Link>
                <span className="text-muted-foreground flex flex-wrap items-center gap-2 text-xs tracking-[0.32px]">
                    <span>
                        {interpolateNodes(t('Review on :date'), {
                            date: (
                                <ReviewDate
                                    date={link.next_review_date}
                                    verification={link.verification_status}
                                />
                            ),
                        })}
                    </span>
                    <VerificationBadge
                        verification={link.verification_status}
                    />
                </span>
            </span>
            <Badge
                className={
                    dueToday ? reviewBadge.dueToday : reviewBadge.onTrack
                }
            >
                {dueToday ? t('DUE TODAY') : t('ON TRACK')}
            </Badge>
        </li>
    );
}

/** Plain Tailwind bars: enough to compare a handful of statuses. */
function StatusBars({ rows }: { rows: Props['linksByStatus'] }) {
    const enumLabels = useEnumLabels();
    const max = Math.max(1, ...rows.map((row) => row.count));

    return (
        <ul className="grid gap-3">
            {rows.map((row) => (
                <li
                    key={row.status}
                    className="grid grid-cols-[9rem_1fr_2.5rem] items-center gap-3"
                >
                    <Badge
                        className={cn(
                            'justify-self-start',
                            linkStatusBadgeClasses[row.status],
                        )}
                    >
                        {enumLabels.LinkStatus[row.status]}
                    </Badge>
                    <div className="bg-muted h-2 overflow-hidden">
                        <div
                            className="bg-primary h-full"
                            style={{ width: `${(row.count / max) * 100}%` }}
                        />
                    </div>
                    <span className="text-right text-sm tabular-nums">
                        {row.count}
                    </span>
                </li>
            ))}
        </ul>
    );
}

function SystemsTable({ systems }: { systems: SystemSummary[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="overflow-x-auto border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('System')}</TableHead>
                        <TableHead className="text-right">
                            {t('Risks')}
                        </TableHead>
                        <TableHead className="text-right">
                            {t('Without a link')}
                        </TableHead>
                        <TableHead className="text-right">
                            {t('Links')}
                        </TableHead>
                        <TableHead className="text-right">
                            {t('Awaiting reassessment')}
                        </TableHead>
                        <TableHead className="text-right">
                            {t('Events (30 days)')}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {systems.map((system) => (
                        <TableRow key={system.id}>
                            <TableCell>
                                <AiSystemName
                                    domain={system.application_domain}
                                >
                                    <span className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={showAiSystem(system.id)}
                                            className="text-link hover:text-link-hover hover:underline"
                                        >
                                            {system.name}
                                        </Link>
                                        {system.category === 'unacceptable' && (
                                            <Badge
                                                className={
                                                    categoryBadgeClasses.unacceptable
                                                }
                                            >
                                                {
                                                    enumLabels.AiSystemCategory
                                                        .unacceptable
                                                }
                                            </Badge>
                                        )}
                                    </span>
                                </AiSystemName>
                            </TableCell>
                            <NumberCell value={system.risks_count} />
                            <NumberCell
                                value={system.unlinked_risks_count}
                                pending
                            />
                            <NumberCell value={system.links_count} />
                            <NumberCell
                                value={system.awaiting_reassessment_count}
                                pending
                            />
                            <NumberCell
                                value={system.recent_events_count}
                                pending
                            />
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function NumberCell({
    value,
    pending = false,
}: {
    value: number;
    /** A pending count is highlighted when it is not zero. */
    pending?: boolean;
}) {
    return (
        <TableCell
            className={cn(
                'text-right tabular-nums',
                pending && value > 0 && pendingTone,
            )}
        >
            {value}
        </TableCell>
    );
}

function Empty({ children }: { children: React.ReactNode }) {
    return (
        <p className="text-muted-foreground border border-dashed p-6 text-center text-sm">
            {children}
        </p>
    );
}

Dashboard.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Dashboard'), href: dashboard() }],
    };
};
