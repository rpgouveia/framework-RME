import { Head, Link as InertiaLink } from '@inertiajs/react';
import { index } from '@/routes/links';
import type {Link,LinkStatus,Paginated,} from '@/types/models';

import '../../../css/links.css';

type Props = {
    links: Paginated<Link>;
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

function getStatusLabel(status: LinkStatus) {
    return statusLabels[status] ?? status;
}

export default function LinksIndex({ links }: Props) {
    return (
        <>
            <Head title="Vínculos" />

            <main className="links-page">
                <header className="links-header">
                    <div>
                        <span className="links-eyebrow">
                            Gestão de riscos
                        </span>

                        <h1>Vínculos</h1>

                        <p>
                            Acompanhe os vínculos entre riscos e medidas de
                            mitigação.
                        </p>
                    </div>
                </header>

                <section className="links-summary">
                    <div>
                        <span>Total de vínculos</span>

                        <strong>{links.total}</strong>
                    </div>
                </section>

                <section className="links-section">
                    <div className="links-section-header">
                        <div>
                            <h2>Vínculos registrados</h2>

                            <p>
                                Ordenados pela próxima data de revisão.
                            </p>
                        </div>
                    </div>

                    {links.data.length > 0 ? (
                        <div className="links-list">
                            {links.data.map((link) => (
                                <article
                                    className="link-card"
                                    key={link.id}
                                >
                                    <div className="link-card-header">
                                        <span className="link-id">
                                            Vínculo #{link.id}
                                        </span>

                                        <span
                                            className={`link-status link-status-${link.status}`}
                                        >
                                            {getStatusLabel(link.status)}
                                        </span>
                                    </div>

                                    <div className="link-main-info">
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
                                                {link.mitigation
                                                    ?.description ??
                                                    `Mitigação #${link.mitigation_id}`}
                                            </strong>
                                        </div>
                                    </div>

                                    <div className="link-details">
                                        <div>
                                            <span>Responsável</span>

                                            <strong>
                                                {link.owner
                                                    ?.organizational_role ??
                                                    `Responsável #${link.owner_id}`}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Fase do ciclo de vida</span>

                                            <strong>
                                                {link.lifecycle_phase}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Próxima revisão</span>

                                            <strong>
                                                {formatDate(
                                                    link.next_review_date,
                                                )}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Custo estimado</span>

                                            <strong>
                                                {link.estimated_cost}
                                            </strong>
                                        </div>
                                    </div>

                                    <div className="link-card-footer">
                                        <div>
                                            <span>
                                                Criado em{' '}
                                                {formatDate(
                                                    link.creation_date,
                                                )}
                                            </span>
                                        </div>

                                        <div className="link-card-actions">
                                            <InertiaLink
                                                href={`/links/${link.id}`}
                                                className="links-button links-button-primary"
                                            >
                                                Visualizar
                                            </InertiaLink>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="links-empty">
                            <p>
                                Nenhum vínculo foi registrado.
                            </p>
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}

LinksIndex.layout = {
    breadcrumbs: [{ title: 'Links', href: index() }],
};