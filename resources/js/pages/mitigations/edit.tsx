import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/mitigations';
import type { EnumOption, Mitigation } from '@/types/models';

type Props = {
    mitigation: Mitigation;
    saeriCategories: EnumOption[];
};

export default function MitigationsEdit(props: Props) {
    return (
        <>
            <Head title="Edit mitigation" />
            <ScaffoldPlaceholder
                title="Edit mitigation"
                todo="TODO: build the edit form. Put it to mitigations.update."
                data={props}
            />
        </>
    );
}

MitigationsEdit.layout = {
    breadcrumbs: [{ title: 'Mitigations', href: index() }],
};
