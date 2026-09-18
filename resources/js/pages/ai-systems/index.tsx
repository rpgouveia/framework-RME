import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/ai-systems';
import type { AiSystem, Paginated } from '@/types/models';

type Props = {
    aiSystems: Paginated<AiSystem>;
};

export default function AiSystemsIndex(props: Props) {
    return (
        <>
            <Head title="AI systems" />
            <ScaffoldPlaceholder
                title="AI systems"
                todo="TODO: list the registered AI systems in a table, with a link to each one."
                data={props}
            />
        </>
    );
}

AiSystemsIndex.layout = {
    breadcrumbs: [{ title: 'AI systems', href: index() }],
};
