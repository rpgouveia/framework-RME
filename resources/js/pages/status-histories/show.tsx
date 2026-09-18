import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { StatusHistory } from '@/types/models';

type Props = {
    statusHistory: StatusHistory;
};

export default function StatusHistoriesShow(props: Props) {
    return (
        <>
            <Head title="Status change" />
            <ScaffoldPlaceholder
                title="Status change"
                todo="TODO: show the status change and who recorded it."
                data={props}
            />
        </>
    );
}

StatusHistoriesShow.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
