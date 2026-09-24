import { Head, Link } from '@inertiajs/react';

import { index } from '@/routes/mitigations';

import type { Mitigation } from '@/types/models';

import '../../../css/mitigations.css';

type Props = {
    mitigation: Mitigation;
};

export default function MitigationsShow({ mitigation }: Props) {
    return (
        <>
            <Head title="Detalhes da mitigação" />

            <main className="mitigations-page">
                {/* CABEÇALHO */}

                <header className="mitigations-header">
                    <p className="mitigations-subtitle">
                        Catálogo de mitigações
                    </p>

                    <h1 className="mitigations-title">Detalhes da mitigação</h1>

                    <p className="mitigations-description">
                        Visualize as informações cadastradas para esta
                        mitigação.
                    </p>
                </header>

                {/* DADOS DA MITIGAÇÃO */}

                <section className="mitigations-section">
                    <h2 className="mitigations-section-title">
                        Informações da mitigação
                    </h2>

                    <div className="mitigations-show-group">
                        <span className="mitigations-show-label">
                            Descrição
                        </span>

                        <p className="mitigations-show-value">
                            {mitigation.description}
                        </p>
                    </div>

                    <div className="mitigations-show-group">
                        <span className="mitigations-show-label">
                            Categoria SAERI
                        </span>

                        <p className="mitigations-show-value">
                            {mitigation.saeri_category}
                        </p>
                    </div>

                    <div className="mitigations-show-group">
                        <span className="mitigations-show-label">
                            Risco-alvo sugerido
                        </span>

                        <p className="mitigations-show-value">
                            {mitigation.suggested_target_risk}
                        </p>
                    </div>

                    <div className="mitigations-show-group">
                        <span className="mitigations-show-label">
                            Evidência esperada
                        </span>

                        <p className="mitigations-show-value">
                            {mitigation.expected_evidence}
                        </p>
                    </div>

                    {/* CUSTO E INCERTEZA */}

                    <div className="mitigations-details-grid">
                        <div className="mitigations-show-group">
                            <span className="mitigations-show-label">
                                Custo sugerido
                            </span>

                            <p className="mitigations-show-value">
                                {mitigation.suggested_cost}
                            </p>
                        </div>

                        <div className="mitigations-show-group">
                            <span className="mitigations-show-label">
                                Nível de incerteza
                            </span>

                            <p className="mitigations-show-value">
                                {mitigation.uncertainty_level}
                            </p>
                        </div>
                    </div>

                    <div className="mitigations-show-group">
                        <span className="mitigations-show-label">
                            Fonte bibliográfica
                        </span>

                        <p className="mitigations-show-value">
                            {mitigation.bibliography_source}
                        </p>
                    </div>
                </section>

                {/* RISCOS VINCULADOS */}

                <section className="mitigations-section">
                    <h2 className="mitigations-section-title">
                        Riscos vinculados
                    </h2>

                    {mitigation.links && mitigation.links.length > 0 ? (
                        <div className="mitigations-linked-risks">
                            {mitigation.links.map((link) => (
                                <div
                                    key={link.id}
                                    className="mitigations-risk-card"
                                >
                                    <span className="mitigations-show-label">
                                        Risco
                                    </span>

                                    <p className="mitigations-show-value">
                                        {link.risk?.description ??
                                            'Risco não carregado'}
                                    </p>

                                    <div className="mitigations-risk-info">
                                        <span>
                                            Status:{' '}
                                            <strong>{link.status}</strong>
                                        </span>

                                        <span>
                                            Custo estimado:{' '}
                                            <strong>
                                                {link.estimated_cost}
                                            </strong>
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="mitigations-empty">
                            Esta mitigação ainda não possui riscos vinculados.
                        </p>
                    )}
                </section>

                {/* BOTÕES */}

                <div className="mitigations-actions">
                    <Link href={index()} className="mitigations-back-button">
                        Voltar
                    </Link>
                </div>
            </main>
        </>
    );
}

MitigationsShow.layout = {
    breadcrumbs: [
        {
            title: 'Mitigations',
            href: index(),
        },
    ],
};
