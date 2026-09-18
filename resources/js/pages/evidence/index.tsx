import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { Evidence, Link, Paginated } from '@/types/models';

type Props = {
    link: Link;
    evidence: Paginated<Evidence>;
};

export default function EvidenceIndex(props: Props) {
    return (
        <>
            <Head title="Evidence" />
            <ScaffoldPlaceholder
                title="Evidence"
                todo="TODO: list the evidence collected for this link."
                data={props}
            />
        </>
    );
}

EvidenceIndex.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
