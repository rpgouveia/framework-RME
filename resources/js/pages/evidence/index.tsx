import { Head, Link as InertiaLink } from '@inertiajs/react';
import { index } from '@/routes/links';
import type {Evidence,EvidenceType,Link,Paginated,} from '@/types/models';

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


export default function EvidenceIndex({
    link,
    evidence,
}: Props) {

    function formatDate(date: string) {
        return new Date(`${date}T00:00:00`).toLocaleDateString('pt-BR');
    }


    return (
        <>
            <Head title="Evidências" />

            <main className="evidence-page">

                {/* CABEÇALHO */}

                <header className="evidence-header">

                    <p className="evidence-subtitle">
                        Evidências
                    </p>

                    <h1 className="evidence-title">
                        Evidências do vínculo
                    </h1>

                    <p className="evidence-description">
                        Consulte as evidências registradas para
                        este vínculo entre risco e mitigação.
                    </p>

                </header>


                {/* INFORMAÇÕES DO VÍNCULO */}

                <section className="evidence-section">

                    <h2 className="evidence-section-title">
                        Vínculo #{link.id}
                    </h2>

                    <div className="evidence-link-grid">

                        <div className="evidence-info">

                            <span className="evidence-info-label">
                                Risco
                            </span>

                            <p className="evidence-info-value">
                                {link.risk?.description ??
                                    `Risco #${link.risk_id}`}
                            </p>

                        </div>


                        <div className="evidence-info">

                            <span className="evidence-info-label">
                                Mitigação
                            </span>

                            <p className="evidence-info-value">
                                {link.mitigation?.description ??
                                    `Mitigação #${link.mitigation_id}`}
                            </p>

                        </div>


                        <div className="evidence-info">

                            <span className="evidence-info-label">
                                Status
                            </span>

                            <p className="evidence-info-value">
                                {link.status}
                            </p>

                        </div>


                        <div className="evidence-info">

                            <span className="evidence-info-label">
                                Responsável
                            </span>

                            <p className="evidence-info-value">
                                {link.owner?.organizational_role ??
                                    `Responsável #${link.owner_id}`}
                            </p>

                        </div>

                    </div>

                </section>


                {/* LISTA DE EVIDÊNCIAS */}

                <section className="evidence-section">

                    <div className="evidence-list-header">

                        <div>
                            <h2 className="evidence-section-title">
                                Evidências registradas
                            </h2>

                            <p className="evidence-count">
                                {evidence.total} evidência(s)
                            </p>
                        </div>

                    </div>


                    {evidence.data.length > 0 ? (

                        <div className="evidence-grid">

                            {evidence.data.map((item) => (

                                <article
                                    key={item.id}
                                    className="evidence-card"
                                >

                                    <div className="evidence-card-header">

                                        <span className="evidence-type">
                                            {evidenceTypeLabels[item.type]}
                                        </span>

                                        <span className="evidence-id">
                                            #{item.id}
                                        </span>

                                    </div>


                                    <p className="evidence-card-description">
                                        {item.description}
                                    </p>


                                    <div className="evidence-card-footer">

                                        <span>
                                            Registrada em
                                        </span>

                                        <strong>
                                            {formatDate(
                                                item.registration_date,
                                            )}
                                        </strong>

                                    </div>

                                </article>

                            ))}

                        </div>

                    ) : (

                        <div className="evidence-empty">

                            <p>
                                Nenhuma evidência foi registrada
                                para este vínculo.
                            </p>

                        </div>

                    )}

                </section>


                {/* BOTÕES */}

                <div className="evidence-actions">

                    <InertiaLink
                        href={index()}
                        className="evidence-back-button"
                    >
                        Voltar para vínculos
                    </InertiaLink>

                </div>

            </main>
        </>
    );
}


EvidenceIndex.layout = {
    breadcrumbs: [
        {
            title: 'Links',
            href: index(),
        },
    ],
};