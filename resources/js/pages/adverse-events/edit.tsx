import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/adverse-events';
import type { AdverseEvent, AiSystem, EnumOption } from '@/types/models';

type Props = {
    adverseEvent: AdverseEvent;
    aiSystems: AiSystem[];
    eventTypes: EnumOption[];
};

export default function AdverseEventsEdit(props: Props) {
    return (
        <>
            <Head title="Edit adverse event" />
            <ScaffoldPlaceholder
                title="Edit adverse event"
                todo="TODO: build the edit form. Put it to adverse-events.update."
                data={props}
            />
        </>
    );
}

AdverseEventsEdit.layout = {
    breadcrumbs: [{ title: 'Adverse events', href: index() }],
};
