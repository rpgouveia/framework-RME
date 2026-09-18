import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/risks';
import type { Paginated, Risk } from '@/types/models';

type Props = {
    risks: Paginated<Risk>;
};

export default function RisksIndex(props: Props) {
    return (
        <>
            <Head title="Risks" />
            <ScaffoldPlaceholder
                title="Risks"
                todo="TODO: list the risks with their system and category."
                data={props}
            />
        </>
    );
}

RisksIndex.layout = {
    breadcrumbs: [{ title: 'Risks', href: index() }],
};
