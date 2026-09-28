import { Form, Head } from '@inertiajs/react';
import EvidenceController from '@/actions/App/Http/Controllers/EvidenceController';
import Heading from '@/components/heading';
import { linkLabel } from '@/lib/labels';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create, index } from '@/routes/links/evidence';
import type { EnumOption, Link as RiskLink } from '@/types/models';
import { EvidenceForm } from './form';

type Props = {
    link: RiskLink;
    types: EnumOption[];
};

export default function EvidenceCreate({ link, types }: Props) {
    return (
        <>
            <Head title="Registrar evidência" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Registrar evidência"
                    description={`${linkLabel(link)}. A data de registro é definida automaticamente.`}
                />
                <Form
                    {...EvidenceController.store.form(link.id)}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <EvidenceForm
                            types={types}
                            errors={errors}
                            processing={processing}
                            submitLabel="Registrar"
                            cancelHref={index(link.id)}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

EvidenceCreate.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(link), href: showLink(link.id) },
        { title: 'Evidências', href: index(link.id) },
        { title: 'Registrar', href: create(link.id) },
    ],
});
