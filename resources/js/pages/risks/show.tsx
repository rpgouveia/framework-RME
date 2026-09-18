import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/risks';
import type { Risk } from '@/types/models';

type Props = {
    risk: Risk;
};

export default function RisksShow(props: Props) {
    return (
        <>
            <Head title="Risk" />
            <ScaffoldPlaceholder
                title="Risk"
                todo="TODO: show the risk, its system and the links that mitigate it."
                data={props}
            />
        </>
    );
}

RisksShow.layout = {
    breadcrumbs: [{ title: 'Risks', href: index() }],
};
