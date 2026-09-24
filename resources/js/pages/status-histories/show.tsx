import { Head, Link as InertiaLink } from '@inertiajs/react';
import { index } from '@/routes/links';
import type { LinkStatus, StatusHistory } from '@/types/models';
import '../../../css/status-histories.css';

type Props = {
    statusHistory: StatusHistory;
};

const statusLabels: Record<LinkStatus, string> = {
    planned: 'Planejado',
    in_progress: 'Em andamento',
    implemented: 'Implementado',
    monitoring: 'Em monitoramento',
    suspended: 'Suspenso',
    cancelled: 'Cancelado',
};

function getStatusLabel(status: LinkStatus | null) {
    if (!status) {
        return 'Status inicial';
    }

    return statusLabels[status] ?? status;
}

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

export default function StatusHistoriesShow({ statusHistory }: Props) {
    return (
        <>
            <Head title="Alteração de status" />

            <main className="status-history-page status-history-show">
                <header className="status-history-header">
                    <div>
                        <span className="status-history-eyebrow">
                            Alteração #{statusHistory.id}
                        </span>

                        <h1>Detalhes da alteração de status</h1>

                        <p>
                            Visualize as informações registradas nesta mudança
                            de status.
                        </p>
                    </div>
                </header>

                <section className="status-history-section">
                    <h2>Alteração de status</h2>

                    <div className="status-history-show-change">
                        <div className="status-history-show-status">
                            <span>Status anterior</span>

                            <strong>
                                {getStatusLabel(statusHistory.previous_status)}
                            </strong>
                        </div>

                        <span className="status-history-show-arrow">→</span>

                        <div className="status-history-show-status">
                            <span>Novo status</span>

                            <strong>
                                {getStatusLabel(statusHistory.new_status)}
                            </strong>
                        </div>
                    </div>
                </section>

                <section className="status-history-section">
                    <h2>Informações da alteração</h2>

                    <div className="status-history-show-grid">
                        <div className="status-history-show-item">
                            <span>Data da alteração</span>

                            <strong>
                                {formatDate(statusHistory.change_date)}
                            </strong>
                        </div>

                        <div className="status-history-show-item">
                            <span>Responsável</span>

                            <strong>
                                {statusHistory.owner?.organizational_role ??
                                    `Responsável #${statusHistory.owner_id}`}
                            </strong>
                        </div>

                        <div className="status-history-show-item">
                            <span>Vínculo</span>

                            <strong>#{statusHistory.link_id}</strong>
                        </div>
                    </div>
                </section>

                <section className="status-history-section">
                    <h2>Motivo da alteração</h2>

                    <div className="status-history-show-reason">
                        <p>
                            {statusHistory.trigger_reason ??
                                'Nenhum motivo foi informado para esta alteração.'}
                        </p>
                    </div>
                </section>

                {statusHistory.adverse_event_id && (
                    <section className="status-history-section">
                        <h2>Evento adverso relacionado</h2>

                        <div className="status-history-show-grid">
                            <div className="status-history-show-item">
                                <span>Evento adverso</span>

                                <strong>
                                    #{statusHistory.adverse_event_id}
                                </strong>
                            </div>

                            {statusHistory.adverse_event && (
                                <div className="status-history-show-item">
                                    <span>Descrição</span>

                                    <strong>
                                        {
                                            statusHistory.adverse_event
                                                .description
                                        }
                                    </strong>
                                </div>
                            )}
                        </div>
                    </section>
                )}

                <div className="status-history-actions">
                    <InertiaLink
                        href={`/links/${statusHistory.link_id}/status-histories`}
                        className="status-history-button status-history-button-secondary"
                    >
                        Voltar para histórico
                    </InertiaLink>
                </div>
            </main>
        </>
    );
}

StatusHistoriesShow.layout = {
    breadcrumbs: [
        {
            title: 'Links',
            href: index(),
        },
    ],
};
