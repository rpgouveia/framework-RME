import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { Evidence } from '@/types/models';

type Props = {
    evidence: Evidence;
};

export default function EvidenceShow(props: Props) {
    return (
        <>
            <Head title="Evidence" />
            <ScaffoldPlaceholder
                title="Evidence"
                todo="TODO: show the evidence and the link it belongs to."
                data={props}
            />
        </>
    );
}

EvidenceShow.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
