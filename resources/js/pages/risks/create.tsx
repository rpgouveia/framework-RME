import { Form, Head, Link, usePage } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create as createAiSystem } from '@/routes/ai-systems';
import { index } from '@/routes/risks';
import type { AiSystem, EnumOption, RiskDomain } from '@/types/models';
import { RiskForm } from './form';

type Props = {
    aiSystems: Pick<AiSystem, 'id' | 'name'>[];
    riskDomains: RiskDomain[];
    lifecyclePhases: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function RisksCreate({
    aiSystems,
    riskDomains,
    lifecyclePhases,
    uncertaintyLevels,
}: Props) {
    const { url } = usePage();

    // The system's detail page links here with `?ai_system=<id>`; ignore an
    // id that is not among the options.
    const query = new URLSearchParams(url.split('?')[1]);
    const requestedId = Number(query.get('ai_system'));
    // A risk not yet mapped links here with `&subdomain=<code>` (0019).
    const defaultSubdomainCode = query.get('subdomain') ?? undefined;
    const defaultAiSystemId = aiSystems.some((s) => s.id === requestedId)
        ? requestedId
        : undefined;

    return (
        <>
            <Head title="Cadastrar risco" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Cadastrar risco"
                    description="Registre um risco identificado em um sistema de IA."
                />
                {aiSystems.length === 0 ? (
                    <div className="flex max-w-xl flex-col items-start gap-4 rounded-xl border border-dashed p-6">
                        <p className="text-muted-foreground text-sm">
                            Todo risco pertence a um sistema de IA, e nenhum
                            sistema foi cadastrado ainda. Cadastre um sistema
                            antes de registrar seus riscos.
                        </p>
                        <Button asChild>
                            <Link href={createAiSystem()}>
                                Cadastrar sistema de IA
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <Form
                        {...RiskController.store.form()}
                        className="max-w-xl space-y-6"
                    >
                        {({ processing, errors }) => (
                            <RiskForm
                                aiSystems={aiSystems}
                                riskDomains={riskDomains}
                                lifecyclePhases={lifecyclePhases}
                                uncertaintyLevels={uncertaintyLevels}
                                errors={errors}
                                processing={processing}
                                submitLabel="Cadastrar"
                                cancelHref={index()}
                                defaultAiSystemId={defaultAiSystemId}
                                defaultSubdomainCode={defaultSubdomainCode}
                            />
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

RisksCreate.layout = {
    breadcrumbs: [{ title: 'Riscos', href: index() }],
};
