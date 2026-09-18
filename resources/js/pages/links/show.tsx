import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/links';
import type { Link } from '@/types/models';

type Props = {
    link: Link;
};

export default function LinksShow(props: Props) {
    return (
        <>
            <Head title="Link" />
            <ScaffoldPlaceholder
                title="Link"
                todo="TODO: show the link with its evidence and status history."
                data={props}
            />
        </>
    );
}

LinksShow.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
