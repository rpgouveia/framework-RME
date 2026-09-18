import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { EnumOption, Link } from '@/types/models';

type Props = {
    link: Link;
    types: EnumOption[];
};

export default function EvidenceCreate(props: Props) {
    return (
        <>
            <Head title="Register evidence" />
            <ScaffoldPlaceholder
                title="Register evidence"
                todo="TODO: build the evidence form. Post it to links.evidence.store."
                data={props}
            />
        </>
    );
}

EvidenceCreate.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
