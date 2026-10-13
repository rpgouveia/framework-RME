import { Form, Head } from '@inertiajs/react';
import AiSystemController from '@/actions/App/Http/Controllers/AiSystemController';
import Heading from '@/components/heading';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { index } from '@/routes/ai-systems';
import type { AiSystem, EnumOption } from '@/types/models';
import { AiSystemForm } from './form';

type Props = {
    aiSystem: AiSystem;
    sourceTypes: EnumOption[];
    categories: EnumOption[];
    /** Verified links a move into the unacceptable tier would revert. */
    verifiedLinksCount: number;
};

export default function AiSystemsEdit({
    aiSystem,
    sourceTypes,
    categories,
    verifiedLinksCount,
}: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Edit AI system')} />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title={t('Edit AI system')}
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
                            submitLabel={t('Save')}
                            cancelHref={index()}
                            aiSystem={aiSystem}
                            verifiedLinksCount={verifiedLinksCount}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

AiSystemsEdit.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('AI systems'), href: index() }],
    };
};
