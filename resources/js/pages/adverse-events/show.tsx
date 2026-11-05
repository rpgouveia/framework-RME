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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { TranslateFn, WithTranslations } from '@/lib/i18n';
import { adverseEventNatureBadgeClasses } from '@/lib/labels';
import { linkLabel, termLabel } from '@/lib/names';
import { rowLink } from '@/lib/row-link';
import { index, show } from '@/routes/adverse-events';
import { show as showAiSystem } from '@/routes/ai-systems';
import { show as showLink } from '@/routes/links';
import { create as createEvidence } from '@/routes/links/evidence';
import { create as createReassessment } from '@/routes/links/reassessments';
import { show as showReassessment } from '@/routes/reassessments';
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
    const enumLabels = useEnumLabels();
    const { t, tc } = useTranslations();
    const reversals = adverseEvent.reversals ?? [];
    const reassessed = reversals.filter(
        (reversal) => reversal.reassessment,
    ).length;
    // The other entries that name the event, recorded by hand.
    const changes = (adverseEvent.status_histories ?? []).filter(
        (change) => change.origin !== 'adverse_event',
    );
    const interceptor = adverseEvent.intercepting_link;

    return (
        <>
            <Head title={eventTitle(adverseEvent, t)} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="grid gap-1">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-[28px] leading-9 font-normal">
                            {eventTitle(adverseEvent, t)}
                        </h1>
                        <Badge
                            className={
                                adverseEventNatureBadgeClasses[
                                    adverseEvent.nature
                                ]
                            }
                        >
                            {enumLabels.AdverseEventNature[adverseEvent.nature]}
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
                    {t(
                        'Adverse events can be neither edited nor deleted: they record what happened and explain the status changes they trigger.',
                    )}
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Details')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label={t('Nature')}>
                                {
                                    enumLabels.AdverseEventNature[
                                        adverseEvent.nature
                                    ]
                                }
                            </DetailItem>
                            <DetailItem label={t('Occurrence date')}>
                                {formatDate(adverseEvent.occurrence_date)}
                            </DetailItem>
                            <DetailItem label={t('Detection date')}>
                                {adverseEvent.detected_at ? (
                                    <span className="grid">
                                        <span>
                                            {formatDate(
                                                adverseEvent.detected_at,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground text-xs font-normal">
                                            {tc(
                                                '{0} Detected on the same day|{1} Detected 1 day later|[2,*] Detected :count days later',
                                                detectionDelayDays ?? 0,
                                            )}
                                        </span>
                                    </span>
                                ) : (
                                    <span className="text-muted-foreground font-normal">
                                        {t('Not recorded')}
                                    </span>
                                )}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem
                                label={t('Risk subdomains materialized')}
                            >
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
                                                        {t(
                                                            'No risk registered in this system.',
                                                        )}
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
                                                            {t('Register risk')}
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
                            <DetailItem label={t('Description')}>
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
                            <CardTitle>{t('Intercepting link')}</CardTitle>
                            <CardDescription>
                                {t(
                                    "This link's mitigation stopped the occurrence, so it was not reverted. Register evidence on it to prove it works.",
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap items-center justify-between gap-4">
                            <Link
                                href={showLink(interceptor.id)}
                                className="font-medium hover:underline"
                            >
                                {linkLabel(interceptor, t)}
                            </Link>
                            <Button size="sm" asChild>
                                <Link href={createEvidence(interceptor.id)}>
                                    {t('Register evidence')}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {t('Links reverted by the event')}
                        </CardTitle>
                        <CardDescription>
                            {t(
                                "On recording the event, the system's verified links whose risk is in one of the subdomains went back to declared. Each one is reassessed separately.",
                            )}
                        </CardDescription>
                        {reversals.length > 0 && (
                            // How far the reassessments went (0020, item 7).
                            <p className="text-sm font-medium">
                                {tc(
                                    ':reassessed of :count reassessed',
                                    reversals.length,
                                    { reassessed },
                                )}
                            </p>
                        )}
                    </CardHeader>
                    <CardContent>
                        {reversals.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t(
                                    'No verified link was reverted by this event.',
                                )}
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
                                                {linkLabel(reversal.link, t)}
                                            </Link>
                                        )}
                                        {reversal.reassessment ? (
                                            <Link
                                                href={showReassessment(
                                                    reversal.reassessment.id,
                                                )}
                                                className="flex items-center gap-2 text-sm hover:underline"
                                            >
                                                <Badge variant="outline">
                                                    {
                                                        enumLabels
                                                            .ReassessmentOutcome[
                                                            reversal
                                                                .reassessment
                                                                .outcome
                                                        ]
                                                    }
                                                </Badge>
                                                {t('on :date', {
                                                    date: formatDate(
                                                        reversal.reassessment
                                                            .reassessment_date,
                                                    ),
                                                })}
                                            </Link>
                                        ) : (
                                            <span className="flex items-center gap-3">
                                                <span className="text-tag-yellow-foreground text-sm">
                                                    {t('Awaiting reassessment')}
                                                </span>
                                                <Button size="sm" asChild>
                                                    <Link
                                                        href={createReassessment(
                                                            reversal.link_id,
                                                        )}
                                                    >
                                                        {t('Reassess')}
                                                    </Link>
                                                </Button>
                                            </span>
                                        )}
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
                                {t('Other changes that cite the event')}
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
    const { t } = useTranslations();

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>{t('Link')}</TableHead>
                    <TableHead>{t('Transition')}</TableHead>
                    <TableHead>{t('Owner')}</TableHead>
                    <TableHead>{t('Date')}</TableHead>
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
                                    {linkLabel(change.link, t)}
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
function eventTitle(event: AdverseEvent, t: TranslateFn): string {
    const subdomains = event.risk_subdomains ?? [];

    return t('Adverse event: :event', {
        event:
            subdomains.length === 1
                ? termLabel(subdomains[0])
                : subdomains.map((term) => term.code).join(', '),
    });
}

AdverseEventsShow.layout = ({
    adverseEvent,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Adverse events'), href: index() },
            {
                title: eventTitle(adverseEvent, t),
                href: show(adverseEvent.id),
            },
        ],
    };
};
