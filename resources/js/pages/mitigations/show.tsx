import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/mitigations';
import type { Mitigation } from '@/types/models';

type Props = {
    mitigation: Mitigation;
};

export default function MitigationsShow(props: Props) {
    return (
        <>
            <Head title="Mitigation" />
            <ScaffoldPlaceholder
                title="Mitigation"
                todo="TODO: show the mitigation and every risk it is applied to."
                data={props}
            />
        </>
    );
}

MitigationsShow.layout = {
    breadcrumbs: [{ title: 'Mitigations', href: index() }],
};
