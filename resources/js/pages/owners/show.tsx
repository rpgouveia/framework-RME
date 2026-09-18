import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/owners';
import type { Owner } from '@/types/models';

type Props = {
    owner: Owner;
};

export default function OwnersShow(props: Props) {
    return (
        <>
            <Head title="Owner" />
            <ScaffoldPlaceholder
                title="Owner"
                todo="TODO: show the owner and the links they are accountable for."
                data={props}
            />
        </>
    );
}

OwnersShow.layout = {
    breadcrumbs: [{ title: 'Owners', href: index() }],
};
