import { Form, Head } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import Heading from '@/components/heading';
import { index, show } from '@/routes/risks';
import type { AiSystem, EnumOption, Risk } from '@/types/models';
import { RiskForm } from './form';

type Props = {
    risk: Risk;
    aiSystems: Pick<AiSystem, 'id' | 'name'>[];
    categories: EnumOption[];
    lifecyclePhases: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function RisksEdit({
    risk,
    aiSystems,
    categories,
    lifecyclePhases,
    uncertaintyLevels,
}: Props) {
    return (
        <>
            <Head title="Editar risco" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading title="Editar risco" description={risk.description} />
                <Form
                    {...RiskController.update.form(risk.id)}
                    options={{ preserveScroll: true }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <RiskForm
                            aiSystems={aiSystems}
                            categories={categories}
                            lifecyclePhases={lifecyclePhases}
                            uncertaintyLevels={uncertaintyLevels}
                            errors={errors}
                            processing={processing}
                            submitLabel="Salvar"
                            cancelHref={show(risk.id)}
                            risk={risk}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

RisksEdit.layout = {
    breadcrumbs: [{ title: 'Riscos', href: index() }],
};
