import { Head, Link as InertiaLink } from '@inertiajs/react';
import { index } from '@/routes/links';
import type {
    Link,
    LinkStatus,
    Paginated,
    StatusHistory,
} from '@/types/models';
import '../../../css/status-histories.css';

type Props = {
    link: Link;
    statusHistories: Paginated<StatusHistory>;
};

const statusLabels: Record<LinkStatus, string> = {
    planned: 'Planejado',
    in_progress: 'Em andamento',
    implemented: 'Implementado',
    monitoring: 'Em monitoramento',
    suspended: 'Suspenso',
    cancelled: 'Cancelado',
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

function getStatusLabel(status: LinkStatus | null) {
    if (!status) {
        return 'Status inicial';
    }

    return statusLabels[status] ?? status;
}

export default function StatusHistoriesIndex({ link, statusHistories }: Props) {
    return (
        <>
            <Head title="Histórico de status" />

            <main className="status-history-page">
                <header className="status-history-header">
                    <div>
                        <span className="status-history-eyebrow">
                            Vínculo #{link.id}
                        </span>

                        <h1>Histórico de status</h1>

                        <p>
                            Acompanhe as alterações de status registradas para
                            este vínculo.
                        </p>
                    </div>
                </header>

                <section className="status-history-section">
                    <h2>Informações do vínculo</h2>

                    <div className="status-history-link-grid">
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
                            <span>Status atual</span>

                            <strong>{getStatusLabel(link.status)}</strong>
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

                <section className="status-history-section">
                    <div className="status-history-section-header">
                        <div>
                            <h2>Alterações registradas</h2>

                            <p>
                                {statusHistories.total}{' '}
                                {statusHistories.total === 1
                                    ? 'alteração registrada'
                                    : 'alterações registradas'}
                            </p>
                        </div>
                    </div>

                    {statusHistories.data.length > 0 ? (
                        <div className="status-history-timeline">
                            {statusHistories.data.map((history) => (
                                <article
                                    className="status-history-item"
                                    key={history.id}
                                >
                                    <div className="status-history-marker">
                                        <span />
                                    </div>

                                    <div className="status-history-card">
                                        <div className="status-history-card-header">
                                            <div>
                                                <span className="status-history-id">
                                                    Alteração #{history.id}
                                                </span>

                                                <span className="status-history-date">
                                                    {formatDate(
                                                        history.change_date,
                                                    )}
                                                </span>
                                            </div>

                                            <InertiaLink
                                                href={`/status-histories/${history.id}`}
                                                className="status-history-button status-history-button-primary"
                                            >
                                                Visualizar
                                            </InertiaLink>
                                        </div>

                                        <div className="status-history-change">
                                            <div>
                                                <span>Status anterior</span>

                                                <strong>
                                                    {getStatusLabel(
                                                        history.previous_status,
                                                    )}
                                                </strong>
                                            </div>

                                            <span className="status-history-arrow">
                                                →
                                            </span>

                                            <div>
                                                <span>Novo status</span>

                                                <strong>
                                                    {getStatusLabel(
                                                        history.new_status,
                                                    )}
                                                </strong>
                                            </div>
                                        </div>

                                        <div className="status-history-details">
                                            <div>
                                                <span>Responsável</span>

                                                <strong>
                                                    {history.owner
                                                        ?.organizational_role ??
                                                        `Responsável #${history.owner_id}`}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Motivo da alteração</span>

                                                <strong>
                                                    {history.trigger_reason ??
                                                        'Não informado'}
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="status-history-empty">
                            <p>
                                Nenhuma alteração de status foi registrada para
                                este vínculo.
                            </p>
                        </div>
                    )}
                </section>

                <div className="status-history-actions">
                    <InertiaLink
                        href="/links"
                        className="status-history-button status-history-button-secondary"
                    >
                        Voltar para vínculos
                    </InertiaLink>
                </div>
            </main>
        </>
    );
}

StatusHistoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Links',
            href: index(),
        },
    ],
};
