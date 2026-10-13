import { Form, Head } from '@inertiajs/react';
import OwnerController from '@/actions/App/Http/Controllers/OwnerController';
import Heading from '@/components/heading';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { create, index } from '@/routes/owners';
import { OwnerForm } from './form';

export default function OwnersCreate() {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Register owner')} />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title={t('Register owner')}
                    description={t(
                        'Register an organizational role that can answer for links.',
                    )}
                />
                <Form
                    {...OwnerController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <OwnerForm
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

OwnersCreate.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Owners'), href: index() },
            { title: t('Register'), href: create() },
        ],
    };
};
