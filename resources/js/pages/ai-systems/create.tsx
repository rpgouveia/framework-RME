import { Form, Head } from '@inertiajs/react';
import AiSystemController from '@/actions/App/Http/Controllers/AiSystemController';
import Heading from '@/components/heading';
import { index } from '@/routes/ai-systems';
import type { EnumOption } from '@/types/models';
import { AiSystemForm } from './form';

type Props = {
    sourceTypes: EnumOption[];
    categories: EnumOption[];
};

export default function AiSystemsCreate({ sourceTypes, categories }: Props) {
    return (
        <>
            <Head title="Cadastrar sistema de IA" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Cadastrar sistema de IA"
                    description="Adicione um sistema de IA ao portfólio e classifique-o conforme o EU AI Act."
                />
                <Form
                    {...AiSystemController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <AiSystemForm
                            sourceTypes={sourceTypes}
                            categories={categories}
                            errors={errors}
                            processing={processing}
                            submitLabel="Cadastrar"
                            cancelHref={index()}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

AiSystemsCreate.layout = {
    breadcrumbs: [{ title: 'Sistemas de IA', href: index() }],
};
