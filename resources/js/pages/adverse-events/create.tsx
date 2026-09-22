import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/adverse-events';
import type { AiSystem, EnumOption } from '@/types/models';

type Props = {
    aiSystems: AiSystem[];
    eventTypes: EnumOption[];
};

export default function AdverseEventsCreate(props: Props) {
    return (
        <>
            <Head title="Record adverse event" />
            <ScaffoldPlaceholder
                title="Record adverse event"
                todo="TODO: build the adverse event form. Post it to adverse-events.store."
                data={props}
            />
        </>
    );
}

AdverseEventsCreate.layout = {
    breadcrumbs: [{ title: 'Adverse events', href: index() }],
};
