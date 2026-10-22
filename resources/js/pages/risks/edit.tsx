import { Form, Head } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import Heading from '@/components/heading';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { index, show } from '@/routes/risks';
import type { AiSystem, EnumOption, Risk, RiskDomain } from '@/types/models';
import { RiskForm } from './form';

type Props = {
    risk: Risk;
    aiSystems: Pick<AiSystem, 'id' | 'name'>[];
    riskDomains: RiskDomain[];
    lifecyclePhases: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function RisksEdit({
    risk,
    aiSystems,
    riskDomains,
    lifecyclePhases,
    uncertaintyLevels,
}: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Edit risk')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading title={t('Edit risk')} description={risk.name} />
                <Form
                    {...RiskController.update.form(risk.id)}
                    options={{ preserveScroll: true }}
                    className="grid max-w-3xl gap-8"
                >
                    {({ processing, errors }) => (
                        <RiskForm
                            aiSystems={aiSystems}
                            riskDomains={riskDomains}
                            lifecyclePhases={lifecyclePhases}
                            uncertaintyLevels={uncertaintyLevels}
                            errors={errors}
                            processing={processing}
                            submitLabel={t('Save')}
                            cancelHref={show(risk.id)}
                            risk={risk}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

RisksEdit.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Risks'), href: index() }],
    };
};
