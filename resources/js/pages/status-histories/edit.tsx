import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type {
    AdverseEvent,
    EnumOption,
    Owner,
    StatusHistory,
} from '@/types/models';

type Props = {
    statusHistory: StatusHistory;
    owners: Owner[];
    statuses: EnumOption[];
    adverseEvents: AdverseEvent[];
};

export default function StatusHistoriesEdit(props: Props) {
    return (
        <>
            <Head title="Edit status change" />
            <ScaffoldPlaceholder
                title="Edit status change"
                todo="TODO: build the edit form. Put it to status-histories.update."
                data={props}
            />
        </>
    );
}

StatusHistoriesEdit.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
