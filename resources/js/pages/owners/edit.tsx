import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/owners';
import type { Owner } from '@/types/models';

type Props = {
    owner: Owner;
};

export default function OwnersEdit(props: Props) {
    return (
        <>
            <Head title="Edit owner" />
            <ScaffoldPlaceholder
                title="Edit owner"
                todo="TODO: build the edit form. Put it to owners.update."
                data={props}
            />
        </>
    );
}

OwnersEdit.layout = {
    breadcrumbs: [{ title: 'Owners', href: index() }],
};
