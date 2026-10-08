import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { StatusTransition } from '@/components/status-transition';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import {
    changeOriginLabels,
    linkLabel,
    reassessmentOutcomeLabels,
    subdomainCodes,
} from '@/lib/labels';
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
    return (
        <>
            <Head title="Histórico de status" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Histórico de status"
                        description={`${linkLabel(link)}. Cada mudança fica registrada e não pode ser editada nem excluída.`}
                    />
                    <Button asChild>
                        <Link href={create(link.id)}>Registrar mudança</Link>
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
                            Reavaliação:
                        </span>
                        <Badge variant="outline">
                            {reassessmentOutcomeLabels[reassessment.outcome]}
                        </Badge>
                    </span>
                </div>
                {reassessment.owner && (
                    <p className="text-muted-foreground text-sm">
                        Reavaliado por {reassessment.owner.organizational_role}{' '}
                        ({reassessment.owner.area})
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
                        Registrado pelo sistema (origem:{' '}
                        {changeOriginLabels[history.origin]})
                    </p>
                ) : (
                    history.owner && (
                        <p className="text-muted-foreground text-sm">
                            Registrado por {history.owner.organizational_role} (
                            {history.owner.area})
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
                            Evento adverso:{' '}
                        </span>
                        {subdomainCodes(history.adverse_event)} em{' '}
                        {formatDate(history.adverse_event.occurrence_date)}
                    </p>
                )}
            </div>
        </li>
    );
}

StatusHistoriesIndex.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(link), href: showLink(link.id) },
        { title: 'Histórico', href: index(link.id) },
    ],
});
