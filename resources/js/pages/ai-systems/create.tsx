import { Form, Head } from '@inertiajs/react';
import AiSystemController from '@/actions/App/Http/Controllers/AiSystemController';
import Heading from '@/components/heading';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { index } from '@/routes/ai-systems';
import type { EnumOption } from '@/types/models';
import { AiSystemForm } from './form';

type Props = {
    sourceTypes: EnumOption[];
    categories: EnumOption[];
};

export default function AiSystemsCreate({ sourceTypes, categories }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Register AI system')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Register AI system')}
                    description={t(
                        'Add an AI system to the portfolio and classify it under the EU AI Act.',
                    )}
                />
                <Form
                    {...AiSystemController.store.form()}
                    className="grid max-w-3xl gap-10"
                >
                    {({ processing, errors }) => (
                        <AiSystemForm
                            sourceTypes={sourceTypes}
                            categories={categories}
                            errors={errors}
                            processing={processing}
                            submitLabel={t('Register')}
                            cancelHref={index()}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

AiSystemsCreate.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('AI systems'), href: index() }],
    };
};
