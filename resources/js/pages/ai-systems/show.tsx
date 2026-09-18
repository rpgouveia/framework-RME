import { Head } from '@inertiajs/react';
import { ScaffoldPlaceholder } from '@/components/scaffold-placeholder';
import { index } from '@/routes/ai-systems';
import type { AiSystem } from '@/types/models';

type Props = {
    aiSystem: AiSystem;
};

export default function AiSystemsShow(props: Props) {
    return (
        <>
            <Head title="AI system" />
            <ScaffoldPlaceholder
                title="AI system"
                todo="TODO: show the system details and its risks."
                data={props}
            />
        </>
    );
}

AiSystemsShow.layout = {
    breadcrumbs: [{ title: 'AI systems', href: index() }],
};
