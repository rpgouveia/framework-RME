import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/owners';

export default function OwnersCreate() {
    return (
        <>
            <Head title="Register owner" />
            <ScaffoldPlaceholder
                title="Register owner"
                todo="TODO: build the owner form. Post it to owners.store."
                data={{}}
            />
        </>
    );
}

OwnersCreate.layout = {
    breadcrumbs: [{ title: 'Owners', href: index() }],
};
