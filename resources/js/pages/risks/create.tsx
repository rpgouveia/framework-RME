import { Form, Head, Link, usePage } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
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
    const { t } = useTranslations();
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
            <Head title={t('Register risk')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Register risk')}
                    description={t(
                        'Register a risk identified in an AI system.',
                    )}
                />
                {aiSystems.length === 0 ? (
                    <div className="flex max-w-xl flex-col items-start gap-4 rounded-xl border border-dashed p-6">
                        <p className="text-muted-foreground text-sm">
                            {t(
                                'Every risk belongs to an AI system, and no system has been registered yet. Register a system before registering its risks.',
                            )}
                        </p>
                        <Button asChild>
                            <Link href={createAiSystem()}>
                                {t('Register AI system')}
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
                                submitLabel={t('Register')}
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

RisksCreate.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Risks'), href: index() }],
    };
};
