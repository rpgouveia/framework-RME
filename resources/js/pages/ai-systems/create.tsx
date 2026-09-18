import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/ai-systems';
import type { EnumOption } from '@/types/models';

type Props = {
    sourceTypes: EnumOption[];
    categories: EnumOption[];
};

export default function AiSystemsCreate(props: Props) {
    return (
        <>
            <Head title="Register AI system" />
            <ScaffoldPlaceholder
                title="Register AI system"
                todo="TODO: build the registration form. Post it to ai-systems.store."
                data={props}
            />
        </>
    );
}

AiSystemsCreate.layout = {
    breadcrumbs: [{ title: 'AI systems', href: index() }],
};
