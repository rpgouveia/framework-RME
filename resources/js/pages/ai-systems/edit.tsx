import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/ai-systems';
import type { AiSystem, EnumOption } from '@/types/models';

type Props = {
    aiSystem: AiSystem;
    sourceTypes: EnumOption[];
    categories: EnumOption[];
};

export default function AiSystemsEdit(props: Props) {
    return (
        <>
            <Head title="Edit AI system" />
            <ScaffoldPlaceholder
                title="Edit AI system"
                todo="TODO: build the edit form. Put it to ai-systems.update."
                data={props}
            />
        </>
    );
}

AiSystemsEdit.layout = {
    breadcrumbs: [{ title: 'AI systems', href: index() }],
};
