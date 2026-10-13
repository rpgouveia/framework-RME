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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { interpolateNodes, translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel, subdomainCodes } from '@/lib/names';
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
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const link = reassessment.link;
    /** The link fields an adjustment may change (0020). */
    const fieldLabels: Record<ReassessmentChange['field'], string> = {
        owner_id: t('Owner'),
        estimated_cost: t('Estimated cost'),
        lifecycle_phase: t('Lifecycle phase'),
        status: t('Progress status'),
    };
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
                return enumLabels.CostLevel[value as CostLevel];
            case 'lifecycle_phase':
                return enumLabels.LifecyclePhase[value as LifecyclePhase];
            case 'status':
                return enumLabels.LinkStatus[value as LinkStatus];
        }
    }

    return (
        <>
            <Head title={t('Reassessment')} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-2">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {t('Reassessment')}
                        </h1>
                        <Badge variant="outline">
                            {
                                enumLabels.ReassessmentOutcome[
                                    reassessment.outcome
                                ]
                            }
                        </Badge>
                    </div>
                    {link && (
                        <Link
                            href={showLink(link.id)}
                            className="text-muted-foreground text-sm hover:underline"
                        >
                            {linkLabel(link, t)}
                        </Link>
                    )}
                </header>

                <p className="text-muted-foreground -mt-2 text-sm">
                    {t(
                        'Reassessments can be neither edited nor deleted: they record what was concluded, by whom and why.',
                    )}
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Decision')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label={t('Outcome')}>
                                {
                                    enumLabels.ReassessmentOutcome[
                                        reassessment.outcome
                                    ]
                                }
                            </DetailItem>
                            <DetailItem label={t('Date')}>
                                {formatDate(reassessment.reassessment_date)}
                            </DetailItem>
                            <DetailItem label={t('Who reassessed')}>
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
                            <DetailItem label={t('Justification')}>
                                <p className="font-normal whitespace-pre-line">
                                    {reassessment.justification}
                                </p>
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Cause analysis')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label={t('Situation')}>
                                {
                                    enumLabels.CauseStatus[
                                        reassessment.cause_status
                                    ]
                                }
                            </DetailItem>
                            {reassessment.cause_status === 'identified' && (
                                <>
                                    <DetailItem label={t('Phase of origin')}>
                                        {reassessment.cause_phase &&
                                            enumLabels.LifecyclePhase[
                                                reassessment.cause_phase
                                            ]}
                                    </DetailItem>
                                    <DetailItem label={t('Cause found')}>
                                        <p className="font-normal whitespace-pre-line">
                                            {reassessment.cause}
                                        </p>
                                    </DetailItem>
                                </>
                            )}
                            {reassessment.cause_status === 'not_applicable' && (
                                <p className="text-muted-foreground text-sm sm:col-span-2">
                                    {t(
                                        "A reversal by review due or by the system's reclassification has no cause to find.",
                                    )}
                                </p>
                            )}
                        </dl>
                    </CardContent>
                </Card>

                {reversal && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Reassessed reversal')}</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <dl className="grid gap-4 sm:grid-cols-3">
                                <DetailItem label={t('Origin')}>
                                    {enumLabels.ChangeOrigin[reversal.origin]}
                                </DetailItem>
                                <DetailItem label={t('Date')}>
                                    <Link
                                        href={showStatusHistory(reversal.id)}
                                        className="hover:underline"
                                    >
                                        {formatDate(reversal.change_date)}
                                    </Link>
                                </DetailItem>
                                <DetailItem label={t('Reversal recorded by')}>
                                    <EntryAuthor entry={reversal} />
                                </DetailItem>
                            </dl>
                            {reversal.trigger_reason && (
                                <dl>
                                    <DetailItem label={t('Reason')}>
                                        <p className="font-normal">
                                            {reversal.trigger_reason}
                                        </p>
                                    </DetailItem>
                                </dl>
                            )}
                            {reversal.system_change && (
                                <dl>
                                    <DetailItem label={t('System change')}>
                                        <Link
                                            href={showSystemChange(
                                                reversal.system_change.id,
                                            )}
                                            className="hover:underline"
                                        >
                                            {t(':type of :date', {
                                                type: enumLabels
                                                    .SystemChangeType[
                                                    reversal.system_change.type
                                                ],
                                                date: formatDate(
                                                    reversal.system_change
                                                        .change_date,
                                                ),
                                            })}
                                        </Link>
                                    </DetailItem>
                                </dl>
                            )}
                            {reversal.adverse_event && (
                                <dl>
                                    <DetailItem label={t('Adverse event')}>
                                        <Link
                                            href={showAdverseEvent(
                                                reversal.adverse_event.id,
                                            )}
                                            className="hover:underline"
                                        >
                                            {t(':subdomains of :date', {
                                                subdomains: subdomainCodes(
                                                    reversal.adverse_event,
                                                ),
                                                date: formatDate(
                                                    reversal.adverse_event
                                                        .occurrence_date,
                                                ),
                                            })}
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
                            <CardTitle>{t('Changes applied')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {changes.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    {t('No field of the link was changed.')}
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('Field')}</TableHead>
                                            <TableHead>{t('Before')}</TableHead>
                                            <TableHead>{t('After')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {changes.map((change) => (
                                            <TableRow key={change.field}>
                                                <TableCell>
                                                    {fieldLabels[change.field]}
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
                        <CardTitle>
                            {t('Verification and replacement')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 text-sm">
                        {reassessment.verification ? (
                            <p>
                                {interpolateNodes(
                                    t(
                                        'The link was verified in the same act, :verification.',
                                    ),
                                    {
                                        verification: (
                                            <Link
                                                href={showStatusHistory(
                                                    reassessment.verification
                                                        .id,
                                                )}
                                                className="font-medium underline underline-offset-4"
                                            >
                                                {t('on :date', {
                                                    date: formatDate(
                                                        reassessment
                                                            .verification
                                                            .change_date,
                                                    ),
                                                })}
                                            </Link>
                                        ),
                                    },
                                )}
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                {reassessment.outcome === 'adjust'
                                    ? t(
                                          'No verification in the same act: the link went on to await verification.',
                                      )
                                    : t('No verification in the same act.')}
                            </p>
                        )}
                        {reassessment.outcome === 'replace' &&
                            (link?.replaced_by ? (
                                <p>
                                    {interpolateNodes(t('Replaced by :link.'), {
                                        link: (
                                            <Link
                                                href={showLink(
                                                    link.replaced_by.id,
                                                )}
                                                className="font-medium underline underline-offset-4"
                                            >
                                                {
                                                    link.replaced_by.mitigation
                                                        ?.name
                                                }
                                            </Link>
                                        ),
                                    })}
                                </p>
                            ) : (
                                <p className="text-muted-foreground">
                                    {t(
                                        'The replacing link has not been created yet.',
                                    )}
                                </p>
                            ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ReassessmentsShow.layout = ({
    reassessment,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            ...(reassessment.link
                ? [
                      {
                          title: linkLabel(reassessment.link, t),
                          href: showLink(reassessment.link.id),
                      },
                      {
                          title: t('Reassessments'),
                          href: reassessmentsIndex(reassessment.link.id),
                      },
                  ]
                : []),
            { title: t('Reassessment'), href: show(reassessment.id) },
        ],
    };
};
