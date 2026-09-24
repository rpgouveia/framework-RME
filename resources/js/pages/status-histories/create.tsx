import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { AdverseEvent, EnumOption, Link, Owner } from '@/types/models';

type Props = {
    link: Link;
    owners: Owner[];
    statuses: EnumOption[];
    adverseEvents: AdverseEvent[];
};

export default function StatusHistoriesCreate(props: Props) {
    return (
        <>
            <Head title="Record status change" />
            <ScaffoldPlaceholder
                title="Record status change"
                todo="TODO: build the status change form. Post it to links.status-histories.store."
                data={props}
            />
        </>
    );
}

StatusHistoriesCreate.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
