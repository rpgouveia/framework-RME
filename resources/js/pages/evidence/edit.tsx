import { Form, Head } from '@inertiajs/react';
import EvidenceController from '@/actions/App/Http/Controllers/EvidenceController';
import Heading from '@/components/heading';
import { formatDate } from '@/lib/format';
import { evidenceTypeLabels, linkLabel } from '@/lib/labels';
import { edit, show } from '@/routes/evidence';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { index as evidenceIndex } from '@/routes/links/evidence';
import type { EnumOption, Evidence, Link as RiskLink } from '@/types/models';
import { EvidenceForm } from './form';

type Props = {
    evidence: Evidence & { link: RiskLink };
    types: EnumOption[];
};

export default function EvidenceEdit({ evidence, types }: Props) {
    return (
        <>
            <Head title="Editar evidência" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Editar evidência"
                    description={`Registrada em ${formatDate(evidence.registration_date)}. A data não muda na edição.`}
                />
                <Form
                    {...EvidenceController.update.form(evidence.id)}
                    options={{ preserveScroll: true }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <EvidenceForm
                            types={types}
                            errors={errors}
                            processing={processing}
                            submitLabel="Salvar"
                            cancelHref={show(evidence.id)}
                            evidence={evidence}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

EvidenceEdit.layout = ({ evidence }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(evidence.link), href: showLink(evidence.link_id) },
        { title: 'Evidências', href: evidenceIndex(evidence.link_id) },
        { title: evidenceTypeLabels[evidence.type], href: show(evidence.id) },
        { title: 'Editar', href: edit(evidence.id) },
    ],
});
