import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { Link, Paginated } from '@/types/models';

type Props = {
    links: Paginated<Link>;
};

export default function LinksIndex(props: Props) {
    return (
        <>
            <Head title="Links" />
            <ScaffoldPlaceholder
                title="Links"
                todo="TODO: list the risk/mitigation links, ordered by next review date."
                data={props}
            />
        </>
    );
}

LinksIndex.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
