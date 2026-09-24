import { Head, Link as InertiaLink, router } from '@inertiajs/react';
import { index } from '@/routes/links';
import type { Evidence, EvidenceType, Link, Paginated } from '@/types/models';

import '../../../css/evidence.css';

type Props = {
    link: Link;
    evidence: Paginated<Evidence>;
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

export default function EvidenceIndex({ link, evidence }: Props) {
    function deleteEvidence(evidenceId: number) {
        const confirmed = window.confirm(
            'Tem certeza que deseja excluir esta evidência?',
        );

        if (!confirmed) {
            return;
        }

        router.delete(`/evidence/${evidenceId}`);
    }

    return (
        <>
            <Head title="Evidências" />

            <main className="evidence-page">
                <header className="evidence-header">
                    <div>
                        <span className="evidence-eyebrow">
                            Vínculo #{link.id}
                        </span>

                        <h1>Evidências do vínculo</h1>

                        <p>
                            Evidências registradas para este vínculo entre risco
                            e mitigação.
                        </p>
                    </div>

                    <InertiaLink
                        href={`/links/${link.id}/evidence/create`}
                        className="evidence-button evidence-button-primary"
                    >
                        Nova evidência
                    </InertiaLink>
                </header>

                <section className="evidence-section">
                    <h2>Informações do vínculo</h2>

                    <div className="evidence-link-grid">
                        <div>
                            <span>Risco</span>

                            <strong>
                                {link.risk?.description ??
                                    `Risco #${link.risk_id}`}
                            </strong>
                        </div>

                        <div>
                            <span>Mitigação</span>

                            <strong>
                                {link.mitigation?.description ??
                                    `Mitigação #${link.mitigation_id}`}
                            </strong>
                        </div>

                        <div>
                            <span>Status</span>

                            <strong>{link.status}</strong>
                        </div>

                        <div>
                            <span>Responsável</span>

                            <strong>
                                {link.owner?.organizational_role ??
                                    `Responsável #${link.owner_id}`}
                            </strong>
                        </div>
                    </div>
                </section>

                <section className="evidence-section">
                    <h2>Evidências registradas</h2>

                    {evidence.data.length > 0 ? (
                        <div className="evidence-list">
                            {evidence.data.map((item) => (
                                <article
                                    className="evidence-card"
                                    key={item.id}
                                >
                                    <div className="evidence-card-header">
                                        <div>
                                            <span className="evidence-type">
                                                {evidenceTypeLabels[
                                                    item.type
                                                ] ?? item.type}
                                            </span>

                                            <span className="evidence-id">
                                                #{item.id}
                                            </span>
                                        </div>
                                    </div>

                                    <p className="evidence-card-description">
                                        {item.description}
                                    </p>

                                    <div className="evidence-card-footer">
                                        <span>
                                            Registrada em{' '}
                                            {formatDate(item.registration_date)}
                                        </span>

                                        <div className="evidence-card-actions">
                                            <InertiaLink
                                                href={`/evidence/${item.id}`}
                                                className="evidence-button evidence-button-view"
                                            >
                                                Visualizar
                                            </InertiaLink>

                                            <InertiaLink
                                                href={`/evidence/${item.id}/edit`}
                                                className="evidence-button evidence-button-secondary"
                                            >
                                                Editar
                                            </InertiaLink>

                                            <button
                                                type="button"
                                                className="evidence-button evidence-button-danger"
                                                onClick={() =>
                                                    deleteEvidence(item.id)
                                                }
                                            >
                                                Deletar
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="evidence-empty">
                            <p>
                                Nenhuma evidência foi registrada para este
                                vínculo.
                            </p>
                        </div>
                    )}
                </section>

                <div className="evidence-actions">
                    <InertiaLink
                        href="/links"
                        className="evidence-button evidence-button-secondary"
                    >
                        Voltar para vínculos
                    </InertiaLink>
                </div>
            </main>
        </>
    );
}

EvidenceIndex.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};
