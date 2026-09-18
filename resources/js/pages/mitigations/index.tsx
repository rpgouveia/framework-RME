import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/mitigations';
import type { Mitigation, Paginated } from '@/types/models';

type Props = {
    mitigations: Paginated<Mitigation>;
};

export default function MitigationsIndex(props: Props) {
    return (
        <>
            <Head title="Mitigations" />
            <ScaffoldPlaceholder
                title="Mitigations"
                todo="TODO: list the mitigation catalogue."
                data={props}
            />
        </>
    );
}

MitigationsIndex.layout = {
    breadcrumbs: [{ title: 'Mitigations', href: index() }],
};
