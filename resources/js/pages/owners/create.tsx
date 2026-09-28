import { Form, Head } from '@inertiajs/react';
import OwnerController from '@/actions/App/Http/Controllers/OwnerController';
import Heading from '@/components/heading';
import { create, index } from '@/routes/owners';
import { OwnerForm } from './form';

export default function OwnersCreate() {
    return (
        <>
            <Head title="Cadastrar responsável" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Cadastrar responsável"
                    description="Registre um papel organizacional que possa responder por vínculos."
                />
                <Form
                    {...OwnerController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <OwnerForm
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

OwnersCreate.layout = {
    breadcrumbs: [
        { title: 'Responsáveis', href: index() },
        { title: 'Cadastrar', href: create() },
    ],
};
