import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/risks';
import type { AiSystem, EnumOption, Risk } from '@/types/models';

type Props = {
    risk: Risk;
    aiSystems: AiSystem[];
    categories: EnumOption[];
    lifecyclePhases: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function RisksEdit(props: Props) {
    return (
        <>
            <Head title="Edit risk" />
            <ScaffoldPlaceholder
                title="Edit risk"
                todo="TODO: build the edit form. Put it to risks.update."
                data={props}
            />
        </>
    );
}

RisksEdit.layout = {
    breadcrumbs: [{ title: 'Risks', href: index() }],
};
