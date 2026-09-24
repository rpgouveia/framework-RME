import { Head, Link as InertiaLink } from '@inertiajs/react';
import { index } from '@/routes/links';
import type {
    CostLevel,
    LifecyclePhase,
    Link,
    LinkStatus,
} from '@/types/models';

import '../../../css/links.css';

type Props = {
    link: Link;
};

const statusLabels: Record<LinkStatus, string> = {
    planned: 'Planejado',
    in_progress: 'Em andamento',
    implemented: 'Implementado',
    monitoring: 'Em monitoramento',
    suspended: 'Suspenso',
    cancelled: 'Cancelado',
};

const lifecycleLabels: Record<LifecyclePhase, string> = {
    inception: 'Concepção',
    design: 'Design',
    data_collection: 'Coleta de dados',
    development: 'Desenvolvimento',
    validation: 'Validação',
    deployment: 'Implantação',
    monitoring: 'Monitoramento',
    decommissioning: 'Desativação',
};

const costLabels: Record<CostLevel, string> = {
    low: 'Baixo',
    medium: 'Médio',
    high: 'Alto',
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

export default function LinksShow({ link }: Props) {
    return (
        <>
            <Head title={`Vínculo #${link.id}`} />

            <main className="links-page">
                <header className="links-show-header">
                    <div>
                        <span className="links-eyebrow">
                            Vínculo #{link.id}
                        </span>

                        <h1>Detalhes do vínculo</h1>

                        <p>
                            Visualize as informações do risco, da mitigação e do
                            acompanhamento deste vínculo.
                        </p>
                    </div>
                </header>

                <section className="links-section">
                    <div className="links-show-section-title">
                        <div>
                            <h2>Risco e mitigação</h2>

                            <p>Elementos associados a este vínculo.</p>
                        </div>

                        <span
                            className={`link-status link-status-${link.status}`}
                        >
                            {statusLabels[link.status] ?? link.status}
                        </span>
                    </div>

                    <div className="links-show-main">
                        <div className="links-show-main-card">
                            <span>Risco</span>

                            <strong>
                                {link.risk?.description ??
                                    `Risco #${link.risk_id}`}
                            </strong>

                            {link.risk && (
                                <div className="links-show-subinfo">
                                    <span>Categoria: {link.risk.category}</span>

                                    <span>
                                        Incerteza: {link.risk.uncertainty_level}
                                    </span>
                                </div>
                            )}
                        </div>

                        <div className="links-show-main-card">
                            <span>Mitigação</span>

                            <strong>
                                {link.mitigation?.description ??
                                    `Mitigação #${link.mitigation_id}`}
                            </strong>

                            {link.mitigation && (
                                <div className="links-show-subinfo">
                                    <span>
                                        Categoria:{' '}
                                        {link.mitigation.saeri_category}
                                    </span>

                                    <span>
                                        Incerteza:{' '}
                                        {link.mitigation.uncertainty_level}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                </section>

                <section className="links-section">
                    <h2>Informações do vínculo</h2>

                    <div className="links-show-grid">
                        <div className="links-show-item">
                            <span>Status</span>

                            <strong>
                                {statusLabels[link.status] ?? link.status}
                            </strong>
                        </div>

                        <div className="links-show-item">
                            <span>Responsável</span>

                            <strong>
                                {link.owner?.organizational_role ??
                                    `Responsável #${link.owner_id}`}
                            </strong>

                            {link.owner?.area && (
                                <small>{link.owner.area}</small>
                            )}
                        </div>

                        <div className="links-show-item">
                            <span>Fase do ciclo de vida</span>

                            <strong>
                                {lifecycleLabels[link.lifecycle_phase] ??
                                    link.lifecycle_phase}
                            </strong>
                        </div>

                        <div className="links-show-item">
                            <span>Custo estimado</span>

                            <strong>
                                {costLabels[link.estimated_cost] ??
                                    link.estimated_cost}
                            </strong>
                        </div>

                        <div className="links-show-item">
                            <span>Custo observado</span>

                            <strong>
                                {link.observed_cost
                                    ? costLabels[link.observed_cost]
                                    : 'Não informado'}
                            </strong>
                        </div>

                        <div className="links-show-item">
                            <span>Data de criação</span>

                            <strong>{formatDate(link.creation_date)}</strong>
                        </div>

                        <div className="links-show-item">
                            <span>Próxima revisão</span>

                            <strong>{formatDate(link.next_review_date)}</strong>
                        </div>
                    </div>
                </section>

                <section className="links-section">
                    <div className="links-show-section-title">
                        <div>
                            <h2>Gerenciamento do vínculo</h2>

                            <p>
                                Consulte as evidências e o histórico de
                                alterações deste vínculo.
                            </p>
                        </div>
                    </div>

                    <div className="links-management">
                        <div className="links-management-card">
                            <div>
                                <span>Evidências</span>

                                <strong>{link.evidence_count ?? 0}</strong>

                                <p>
                                    Consulte os documentos e registros que
                                    comprovam a aplicação da mitigação.
                                </p>
                            </div>

                            <InertiaLink
                                href={`/links/${link.id}/evidence`}
                                className="links-button links-button-secondary"
                            >
                                Ver evidências
                            </InertiaLink>
                        </div>

                        <div className="links-management-card">
                            <div>
                                <span>Histórico de status</span>

                                <strong>
                                    {link.status_histories_count ?? 0}
                                </strong>

                                <p>
                                    Consulte todas as mudanças de status
                                    registradas para este vínculo.
                                </p>
                            </div>

                            <InertiaLink
                                href={`/links/${link.id}/status-histories`}
                                className="links-button links-button-secondary"
                            >
                                Ver histórico
                            </InertiaLink>
                        </div>
                    </div>
                </section>

                <div className="links-show-actions">
                    <InertiaLink
                        href="/links"
                        className="links-button links-button-secondary"
                    >
                        Voltar para vínculos
                    </InertiaLink>

                    <InertiaLink
                        href={`/links/${link.id}/edit`}
                        className="links-button links-button-primary"
                    >
                        Editar vínculo
                    </InertiaLink>
                </div>
            </main>
        </>
    );
}

LinksShow.layout = {
    breadcrumbs: [
        {
            title: 'Links',
            href: index(),
        },
    ],
};
