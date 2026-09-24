import { Head, Link as InertiaLink } from '@inertiajs/react';
import { index } from '@/routes/links';
import type { Evidence, EvidenceType } from '@/types/models';
import '../../../css/evidence.css';

type Props = {
    evidence: Evidence;
};

const evidenceTypeLabels: Record<EvidenceType, string> = {
    document: 'Documento',
    report: 'Relatório',
    audit_log: 'Log de auditoria',
    test_result: 'Resultado de teste',
    certification: 'Certificação',
    meeting_minutes: 'Ata de reunião',
};

function formatDate(date: string | null | undefined) {
    if (!date) {
        return 'Data não informada';
    }

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return date;
    }

    return parsedDate.toLocaleDateString('pt-BR', {
        timeZone: 'UTC',
    });
}

export default function EvidenceShow({ evidence }: Props) {
    return (
        <>
            <Head title="Evidência" />

            <main className="evidence-page evidence-show">
                <header className="evidence-header">
                    <div>
                        <span className="evidence-eyebrow">
                            Evidência #{evidence.id}
                        </span>

                        <h1>Detalhes da evidência</h1>

                        <p>
                            Visualize as informações registradas para esta
                            evidência.
                        </p>
                    </div>

                    <InertiaLink
                        href={`/evidence/${evidence.id}/edit`}
                        className="evidence-button evidence-button-primary"
                    >
                        Editar evidência
                    </InertiaLink>
                </header>

                <section className="evidence-section">
                    <h2>Informações da evidência</h2>

                    <div className="evidence-show-grid">
                        <div className="evidence-show-item">
                            <span>Tipo</span>

                            <strong>
                                {evidenceTypeLabels[evidence.type] ??
                                    evidence.type}
                            </strong>
                        </div>

                        <div className="evidence-show-item">
                            <span>Data de registro</span>

                            <strong>
                                {formatDate(evidence.registration_date)}
                            </strong>
                        </div>

                        <div className="evidence-show-item">
                            <span>ID da evidência</span>

                            <strong>#{evidence.id}</strong>
                        </div>
                    </div>
                </section>

                <section className="evidence-section">
                    <h2>Descrição</h2>

                    <p className="evidence-show-description">
                        {evidence.description}
                    </p>
                </section>

                <section className="evidence-section">
                    <h2>Vínculo relacionado</h2>

                    <div className="evidence-show-grid">
                        <div className="evidence-show-item">
                            <span>Vínculo</span>

                            <strong>#{evidence.link_id}</strong>
                        </div>

                        {evidence.link && (
                            <>
                                <div className="evidence-show-item">
                                    <span>Risco</span>

                                    <strong>
                                        {evidence.link.risk?.description ??
                                            `Risco #${evidence.link.risk_id}`}
                                    </strong>
                                </div>

                                <div className="evidence-show-item">
                                    <span>Mitigação</span>

                                    <strong>
                                        {evidence.link.mitigation
                                            ?.description ??
                                            `Mitigação #${evidence.link.mitigation_id}`}
                                    </strong>
                                </div>

                                <div className="evidence-show-item">
                                    <span>Status</span>

                                    <strong>{evidence.link.status}</strong>
                                </div>

                                <div className="evidence-show-item">
                                    <span>Responsável</span>

                                    <strong>
                                        {evidence.link.owner
                                            ?.organizational_role ??
                                            `Responsável #${evidence.link.owner_id}`}
                                    </strong>
                                </div>
                            </>
                        )}
                    </div>
                </section>

                <div className="evidence-actions">
                    <InertiaLink
                        href={`/links/${evidence.link_id}/evidence`}
                        className="evidence-button evidence-button-secondary"
                    >
                        Voltar para evidências
                    </InertiaLink>

                    <InertiaLink
                        href={`/evidence/${evidence.id}/edit`}
                        className="evidence-button evidence-button-primary"
                    >
                        Editar
                    </InertiaLink>
                </div>
            </main>
        </>
    );
}

EvidenceShow.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
