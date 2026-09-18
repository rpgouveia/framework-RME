import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/risks';
import type { AiSystem, EnumOption } from '@/types/models';

type Props = {
    aiSystems: AiSystem[];
    categories: EnumOption[];
    lifecyclePhases: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function RisksCreate(props: Props) {
    return (
        <>
            <Head title="Register risk" />
            <ScaffoldPlaceholder
                title="Register risk"
                todo="TODO: build the risk form. Post it to risks.store."
                data={props}
            />
        </>
    );
}

RisksCreate.layout = {
    breadcrumbs: [{ title: 'Risks', href: index() }],
};
