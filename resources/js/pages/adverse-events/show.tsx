import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/adverse-events';
import type { AdverseEvent } from '@/types/models';

type Props = {
    adverseEvent: AdverseEvent;
};

export default function AdverseEventsShow(props: Props) {
    return (
        <>
            <Head title="Adverse event" />
            <ScaffoldPlaceholder
                title="Adverse event"
                todo="TODO: show the event, its system and the status changes it triggered."
                data={props}
            />
        </>
    );
}

AdverseEventsShow.layout = {
    breadcrumbs: [{ title: 'Adverse events', href: index() }],
};
