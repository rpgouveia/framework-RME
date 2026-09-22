import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/adverse-events';
import type { AdverseEvent, Paginated } from '@/types/models';

type Props = {
    adverseEvents: Paginated<AdverseEvent>;
};

export default function AdverseEventsIndex(props: Props) {
    return (
        <>
            <Head title="Adverse events" />
            <ScaffoldPlaceholder
                title="Adverse events"
                todo="TODO: list the events with their system, type and date."
                data={props}
            />
        </>
    );
}

AdverseEventsIndex.layout = {
    breadcrumbs: [{ title: 'Adverse events', href: index() }],
};
