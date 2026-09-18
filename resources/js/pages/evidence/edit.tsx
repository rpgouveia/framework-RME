import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { EnumOption, Evidence } from '@/types/models';

type Props = {
    evidence: Evidence;
    types: EnumOption[];
};

export default function EvidenceEdit(props: Props) {
    return (
        <>
            <Head title="Edit evidence" />
            <ScaffoldPlaceholder
                title="Edit evidence"
                todo="TODO: build the edit form. Put it to evidence.update."
                data={props}
            />
        </>
    );
}

EvidenceEdit.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
