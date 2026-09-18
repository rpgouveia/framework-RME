import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/owners';
import type { Owner, Paginated } from '@/types/models';

type Props = {
    owners: Paginated<Owner>;
};

export default function OwnersIndex(props: Props) {
    return (
        <>
            <Head title="Owners" />
            <ScaffoldPlaceholder
                title="Owners"
                todo="TODO: list the organizational roles accountable for mitigations."
                data={props}
            />
        </>
    );
}

OwnersIndex.layout = {
    breadcrumbs: [{ title: 'Owners', href: index() }],
};
