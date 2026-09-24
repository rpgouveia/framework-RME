import { Form, Head } from '@inertiajs/react';
import AiSystemController from '@/actions/App/Http/Controllers/AiSystemController';
import Heading from '@/components/heading';
import { index, show } from '@/routes/ai-systems';
import type { AiSystem, EnumOption } from '@/types/models';
import { AiSystemForm } from './form';

type Props = {
    aiSystem: AiSystem;
    sourceTypes: EnumOption[];
    categories: EnumOption[];
};

export default function AiSystemsEdit({
    aiSystem,
    sourceTypes,
    categories,
}: Props) {
    return (
        <>
            <Head title="Editar sistema de IA" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Editar sistema de IA"
                    description={aiSystem.name}
                />
                <Form
                    {...AiSystemController.update.form(aiSystem.id)}
                    options={{ preserveScroll: true }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <AiSystemForm
                            sourceTypes={sourceTypes}
                            categories={categories}
                            errors={errors}
                            processing={processing}
                            submitLabel="Salvar"
                            cancelHref={show(aiSystem.id)}
                            aiSystem={aiSystem}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

AiSystemsEdit.layout = {
    breadcrumbs: [{ title: 'Sistemas de IA', href: index() }],
};
