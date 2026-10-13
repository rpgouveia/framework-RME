import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { StatusTransition } from '@/components/status-transition';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel, subdomainCodes } from '@/lib/names';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create, index } from '@/routes/links/status-histories';
import { show as showReassessment } from '@/routes/reassessments';
import { show } from '@/routes/status-histories';
import type {
    Paginated,
    Link as RiskLink,
    Reassessment,
    StatusHistory,
} from '@/types/models';

/** An entry of the timeline: a status or verification change, or a reassessment. */
type TimelineEntry =
    | { type: 'change'; entry: StatusHistory }
    | { type: 'reassessment'; entry: Reassessment };

type Props = {
    link: RiskLink;
    entries: Paginated<TimelineEntry>;
};

export default function StatusHistoriesIndex({ link, entries }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Status history')} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={t('Status history')}
                        description={t(
                            ':link. Each change is recorded and can be neither edited nor deleted.',
                            { link: linkLabel(link, t) },
                        )}
                    />
                    <Button asChild>
                        <Link href={create(link.id)}>{t('Record change')}</Link>
                    </Button>
                </div>

                <ol className="relative max-w-3xl border-l pl-6">
                    {entries.data.map((item) =>
                        item.type === 'reassessment' ? (
                            <ReassessmentEntry
                                key={`reassessment-${item.entry.id}`}
                                reassessment={item.entry}
                            />
                        ) : (
                            <ChangeEntry
                                key={`change-${item.entry.id}`}
                                history={item.entry}
                            />
                        ),
                    )}
                </ol>

                {entries.last_page > 1 && (
                    <PaginationLinks links={entries.links} />
                )}
            </div>
        </>
    );
}

/** A reassessment, as an entry of its own in the timeline (0020). */
function ReassessmentEntry({ reassessment }: { reassessment: Reassessment }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <li className="relative pb-8 last:pb-0">
            <span
                aria-hidden
                className="bg-primary absolute top-1.5 left-[-1.95rem] size-3 rounded-full"
            />
            <div className="grid gap-2">
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <Link
                        href={showReassessment(reassessment.id)}
                        className="text-sm font-medium hover:underline"
                    >
                        {formatDate(reassessment.reassessment_date)}
                    </Link>
                    <span className="flex items-center gap-2 text-sm">
                        <span className="text-muted-foreground">
                            {t('Reassessment:')}
                        </span>
                        <Badge variant="outline">
                            {
                                enumLabels.ReassessmentOutcome[
                                    reassessment.outcome
                                ]
                            }
                        </Badge>
                    </span>
                </div>
                {reassessment.owner && (
                    <p className="text-muted-foreground text-sm">
                        {t('Reassessed by :role (:area)', {
                            role: reassessment.owner.organizational_role,
                            area: reassessment.owner.area,
                        })}
                    </p>
                )}
                <p className="max-w-prose text-sm">
                    {reassessment.justification}
                </p>
            </div>
        </li>
    );
}

function ChangeEntry({ history }: { history: StatusHistory }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <li className="relative pb-8 last:pb-0">
            <span
                aria-hidden
                className="bg-background text-muted-foreground absolute top-1.5 left-[-1.95rem] size-3 rounded-full border-2 border-current"
            />
            <div className="grid gap-2">
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <Link
                        href={show(history.id)}
                        className="text-sm font-medium hover:underline"
                    >
                        {formatDate(history.change_date)}
                    </Link>
                    <StatusTransition entry={history} />
                </div>
                {history.origin !== 'manual' ? (
                    <p className="text-muted-foreground text-sm">
                        {t('Recorded by the system (origin: :origin)', {
                            origin: enumLabels.ChangeOrigin[history.origin],
                        })}
                    </p>
                ) : (
                    history.owner && (
                        <p className="text-muted-foreground text-sm">
                            {t('Recorded by :role (:area)', {
                                role: history.owner.organizational_role,
                                area: history.owner.area,
                            })}
                        </p>
                    )
                )}
                {history.trigger_reason && (
                    <p className="max-w-prose text-sm">
                        {history.trigger_reason}
                    </p>
                )}
                {history.adverse_event && (
                    <p className="text-sm">
                        <span className="text-muted-foreground">
                            {t('Adverse event:')}{' '}
                        </span>
                        {t(':subdomains on :date', {
                            subdomains: subdomainCodes(history.adverse_event),
                            date: formatDate(
                                history.adverse_event.occurrence_date,
                            ),
                        })}
                    </p>
                )}
            </div>
        </li>
    );
}

StatusHistoriesIndex.layout = ({
    link,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            { title: linkLabel(link, t), href: showLink(link.id) },
            { title: t('History'), href: index(link.id) },
        ],
    };
};
