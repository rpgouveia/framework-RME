import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { EnumOption, Mitigation, Owner, Risk } from '@/types/models';

type Props = {
    risks: Risk[];
    mitigations: Mitigation[];
    owners: Owner[];
    lifecyclePhases: EnumOption[];
    statuses: EnumOption[];
    costLevels: EnumOption[];
};

export default function LinksCreate(props: Props) {
    return (
        <>
            <Head title="Create link" />
            <ScaffoldPlaceholder
                title="Create link"
                todo="TODO: build the link form. Post it to links.store."
                data={props}
            />
        </>
    );
}

LinksCreate.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
