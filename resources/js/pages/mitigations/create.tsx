import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/mitigations';
import type { EnumOption } from '@/types/models';

type Props = {
    saeriCategories: EnumOption[];
    costLevels: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function MitigationsCreate(props: Props) {
    return (
        <>
            <Head title="Register mitigation" />
            <ScaffoldPlaceholder
                title="Register mitigation"
                todo="TODO: build the mitigation form. Post it to mitigations.store."
                data={props}
            />
        </>
    );
}

MitigationsCreate.layout = {
    breadcrumbs: [{ title: 'Mitigations', href: index() }],
};
