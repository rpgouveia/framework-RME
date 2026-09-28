import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { StatusTransition } from '@/components/status-transition';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { adverseEventTypeLabels, linkLabel } from '@/lib/labels';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create, index } from '@/routes/links/status-histories';
import { show } from '@/routes/status-histories';
import type {
    Paginated,
    Link as RiskLink,
    StatusHistory,
} from '@/types/models';

type Props = {
    link: RiskLink;
    statusHistories: Paginated<StatusHistory>;
};

export default function StatusHistoriesIndex({ link, statusHistories }: Props) {
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
                    {statusHistories.data.map((history) => (
                        <li
                            key={history.id}
                            className="relative pb-8 last:pb-0"
                        >
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
                                    <StatusTransition
                                        from={history.previous_status}
                                        to={history.new_status}
                                    />
                                </div>
                                {history.owner && (
                                    <p className="text-muted-foreground text-sm">
                                        Registrado por{' '}
                                        {history.owner.organizational_role} (
                                        {history.owner.area})
                                    </p>
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
                                        {
                                            adverseEventTypeLabels[
                                                history.adverse_event.event_type
                                            ]
                                        }{' '}
                                        em{' '}
                                        {formatDate(
                                            history.adverse_event
                                                .occurrence_date,
                                        )}
                                    </p>
                                )}
                            </div>
                        </li>
                    ))}
                </ol>

                {statusHistories.last_page > 1 && (
                    <PaginationLinks links={statusHistories.links} />
                )}
            </div>
        </>
    );
}

StatusHistoriesIndex.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(link), href: showLink(link.id) },
        { title: 'Histórico', href: index(link.id) },
    ],
});
