import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { EnumOption, Link, Mitigation, Owner, Risk } from '@/types/models';

type Props = {
    link: Link;
    risks: Risk[];
    mitigations: Mitigation[];
    owners: Owner[];
    lifecyclePhases: EnumOption[];
    statuses: EnumOption[];
    costLevels: EnumOption[];
};

export default function LinksEdit(props: Props) {
    return (
        <>
            <Head title="Edit link" />
            <ScaffoldPlaceholder
                title="Edit link"
                todo="TODO: build the edit form. Put it to links.update."
                data={props}
            />
        </>
    );
}

LinksEdit.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
