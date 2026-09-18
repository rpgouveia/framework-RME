import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { Link, Paginated, StatusHistory } from '@/types/models';

type Props = {
    link: Link;
    statusHistories: Paginated<StatusHistory>;
};

export default function StatusHistoriesIndex(props: Props) {
    return (
        <>
            <Head title="Status history" />
            <ScaffoldPlaceholder
                title="Status history"
                todo="TODO: list the status changes recorded for this link."
                data={props}
            />
        </>
    );
}

StatusHistoriesIndex.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
