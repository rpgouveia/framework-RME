import { Head, Link as InertiaLink, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { index } from '@/routes/links';
import type { EnumOption, Link, Mitigation, Owner, Risk } from '@/types/models';

import '../../../css/links.css';

type Props = {
    link: Link;
    risks: Risk[];
    mitigations: Mitigation[];
    owners: Owner[];
    lifecyclePhases: EnumOption[];
    statuses: EnumOption[];
    costLevels: EnumOption[];
};

export default function LinksEdit({
    link,
    risks,
    mitigations,
    owners,
    lifecyclePhases,
    statuses,
    costLevels,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        risk_id: String(link.risk_id),
        mitigation_id: String(link.mitigation_id),
        owner_id: String(link.owner_id),
        lifecycle_phase: link.lifecycle_phase,
        status: link.status,
        estimated_cost: link.estimated_cost,
        observed_cost: link.observed_cost ?? '',
        next_review_date: link.next_review_date ?? '',
    });

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        put(`/links/${link.id}`);
    }

    return (
        <>
            <Head title={`Editar vínculo #${link.id}`} />

            <main className="links-page">
                <header className="links-edit-header">
                    <div>
                        <span className="links-eyebrow">
                            Vínculo #{link.id}
                        </span>

                        <h1>Editar vínculo</h1>

                        <p>
                            Atualize as informações de acompanhamento do vínculo
                            entre risco e mitigação.
                        </p>
                    </div>
                </header>

                <form className="links-edit-form" onSubmit={handleSubmit}>
                    <section className="links-section">
                        <div className="links-show-section-title">
                            <div>
                                <h2>Risco e mitigação</h2>

                                <p>
                                    Selecione os elementos relacionados ao
                                    vínculo.
                                </p>
                            </div>
                        </div>

                        <div className="links-edit-grid">
                            <div className="links-edit-field">
                                <label htmlFor="risk_id">Risco</label>

                                <select
                                    id="risk_id"
                                    value={data.risk_id}
                                    onChange={(event) =>
                                        setData('risk_id', event.target.value)
                                    }
                                >
                                    <option value="">Selecione um risco</option>

                                    {risks.map((risk) => (
                                        <option key={risk.id} value={risk.id}>
                                            #{risk.id} - {risk.description}
                                        </option>
                                    ))}
                                </select>

                                {errors.risk_id && (
                                    <span className="links-edit-error">
                                        {errors.risk_id}
                                    </span>
                                )}
                            </div>

                            <div className="links-edit-field">
                                <label htmlFor="mitigation_id">Mitigação</label>

                                <select
                                    id="mitigation_id"
                                    value={data.mitigation_id}
                                    onChange={(event) =>
                                        setData(
                                            'mitigation_id',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        Selecione uma mitigação
                                    </option>

                                    {mitigations.map((mitigation) => (
                                        <option
                                            key={mitigation.id}
                                            value={mitigation.id}
                                        >
                                            #{mitigation.id} -{' '}
                                            {mitigation.description}
                                        </option>
                                    ))}
                                </select>

                                {errors.mitigation_id && (
                                    <span className="links-edit-error">
                                        {errors.mitigation_id}
                                    </span>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="links-section">
                        <div className="links-show-section-title">
                            <div>
                                <h2>Informações do vínculo</h2>

                                <p>
                                    Atualize o responsável, status, fase e
                                    custos.
                                </p>
                            </div>
                        </div>

                        <div className="links-edit-grid">
                            <div className="links-edit-field">
                                <label htmlFor="owner_id">Responsável</label>

                                <select
                                    id="owner_id"
                                    value={data.owner_id}
                                    onChange={(event) =>
                                        setData('owner_id', event.target.value)
                                    }
                                >
                                    <option value="">
                                        Selecione um responsável
                                    </option>

                                    {owners.map((owner) => (
                                        <option key={owner.id} value={owner.id}>
                                            {owner.organizational_role}
                                            {owner.area
                                                ? ` - ${owner.area}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>

                                {errors.owner_id && (
                                    <span className="links-edit-error">
                                        {errors.owner_id}
                                    </span>
                                )}
                            </div>

                            <div className="links-edit-field">
                                <label htmlFor="lifecycle_phase">
                                    Fase do ciclo de vida
                                </label>

                                <select
                                    id="lifecycle_phase"
                                    value={data.lifecycle_phase}
                                    onChange={(event) =>
                                        setData(
                                            'lifecycle_phase',
                                            event.target
                                                .value as typeof data.lifecycle_phase,
                                        )
                                    }
                                >
                                    {lifecyclePhases.map((phase) => (
                                        <option
                                            key={phase.value}
                                            value={phase.value}
                                        >
                                            {phase.label}
                                        </option>
                                    ))}
                                </select>

                                {errors.lifecycle_phase && (
                                    <span className="links-edit-error">
                                        {errors.lifecycle_phase}
                                    </span>
                                )}
                            </div>

                            <div className="links-edit-field">
                                <label htmlFor="status">Status</label>

                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(event) =>
                                        setData(
                                            'status',
                                            event.target
                                                .value as typeof data.status,
                                        )
                                    }
                                >
                                    {statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </select>

                                {errors.status && (
                                    <span className="links-edit-error">
                                        {errors.status}
                                    </span>
                                )}
                            </div>

                            <div className="links-edit-field">
                                <label htmlFor="estimated_cost">
                                    Custo estimado
                                </label>

                                <select
                                    id="estimated_cost"
                                    value={data.estimated_cost}
                                    onChange={(event) =>
                                        setData(
                                            'estimated_cost',
                                            event.target
                                                .value as typeof data.estimated_cost,
                                        )
                                    }
                                >
                                    {costLevels.map((cost) => (
                                        <option
                                            key={cost.value}
                                            value={cost.value}
                                        >
                                            {cost.label}
                                        </option>
                                    ))}
                                </select>

                                {errors.estimated_cost && (
                                    <span className="links-edit-error">
                                        {errors.estimated_cost}
                                    </span>
                                )}
                            </div>

                            <div className="links-edit-field">
                                <label htmlFor="observed_cost">
                                    Custo observado
                                </label>

                                <select
                                    id="observed_cost"
                                    value={data.observed_cost}
                                    onChange={(event) =>
                                        setData(
                                            'observed_cost',
                                            event.target
                                                .value as typeof data.observed_cost,
                                        )
                                    }
                                >
                                    <option value="">Não informado</option>

                                    {costLevels.map((cost) => (
                                        <option
                                            key={cost.value}
                                            value={cost.value}
                                        >
                                            {cost.label}
                                        </option>
                                    ))}
                                </select>

                                {errors.observed_cost && (
                                    <span className="links-edit-error">
                                        {errors.observed_cost}
                                    </span>
                                )}
                            </div>

                            <div className="links-edit-field">
                                <label htmlFor="next_review_date">
                                    Próxima revisão
                                </label>

                                <input
                                    id="next_review_date"
                                    type="date"
                                    value={data.next_review_date}
                                    onChange={(event) =>
                                        setData(
                                            'next_review_date',
                                            event.target.value,
                                        )
                                    }
                                />

                                {errors.next_review_date && (
                                    <span className="links-edit-error">
                                        {errors.next_review_date}
                                    </span>
                                )}
                            </div>
                        </div>
                    </section>

                    <div className="links-edit-actions">
                        <InertiaLink
                            href={`/links/${link.id}`}
                            className="links-button links-button-secondary"
                        >
                            Cancelar
                        </InertiaLink>

                        <button
                            type="submit"
                            className="links-button links-button-primary"
                            disabled={processing}
                        >
                            {processing ? 'Salvando...' : 'Salvar alterações'}
                        </button>
                    </div>
                </form>
            </main>
        </>
    );
}

LinksEdit.layout = {
    breadcrumbs: [
        {
            title: 'Links',
            href: index(),
        },
    ],
};
