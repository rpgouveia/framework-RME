import { Head, Link as InertiaLink, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { index } from '@/routes/links';
import type { EnumOption, Evidence, EvidenceType } from '@/types/models';
import '../../../css/evidence.css';

type Props = {
    evidence: Evidence;
    types: EnumOption[];
};

export default function EvidenceEdit({ evidence, types }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        type: evidence.type,
        description: evidence.description,
        registration_date: evidence.registration_date,
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        put(`/evidence/${evidence.id}`);
    }

    return (
        <>
            <Head title="Editar evidência" />

            <main className="evidence-page evidence-edit">
                <header className="evidence-header">
                    <div>
                        <span className="evidence-eyebrow">
                            Evidência #{evidence.id}
                        </span>

                        <h1>Editar evidência</h1>

                        <p>
                            Altere as informações registradas para esta
                            evidência.
                        </p>
                    </div>
                </header>

                <section className="evidence-section">
                    <h2>Vínculo relacionado</h2>

                    <div className="evidence-link-grid">
                        <div>
                            <span>Vínculo</span>
                            <strong>#{evidence.link_id}</strong>
                        </div>

                        {evidence.link && (
                            <>
                                <div>
                                    <span>Risco</span>

                                    <strong>
                                        {evidence.link.risk?.description ??
                                            `Risco #${evidence.link.risk_id}`}
                                    </strong>
                                </div>

                                <div>
                                    <span>Mitigação</span>

                                    <strong>
                                        {evidence.link.mitigation
                                            ?.description ??
                                            `Mitigação #${evidence.link.mitigation_id}`}
                                    </strong>
                                </div>
                            </>
                        )}
                    </div>
                </section>

                <form
                    className="evidence-section evidence-form"
                    onSubmit={submit}
                >
                    <h2>Dados da evidência</h2>

                    <div className="evidence-field">
                        <label htmlFor="type">Tipo da evidência</label>

                        <select
                            id="type"
                            value={data.type}
                            onChange={(event) =>
                                setData(
                                    'type',
                                    event.target.value as EvidenceType,
                                )
                            }
                            required
                        >
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>

                        {errors.type && (
                            <p className="evidence-error">{errors.type}</p>
                        )}
                    </div>

                    <div className="evidence-field">
                        <label htmlFor="description">Descrição</label>

                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            rows={5}
                            required
                        />

                        {errors.description && (
                            <p className="evidence-error">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div className="evidence-field">
                        <label htmlFor="registration_date">
                            Data de registro
                        </label>

                        <input
                            id="registration_date"
                            type="date"
                            value={data.registration_date}
                            onChange={(event) =>
                                setData('registration_date', event.target.value)
                            }
                            required
                        />

                        {errors.registration_date && (
                            <p className="evidence-error">
                                {errors.registration_date}
                            </p>
                        )}
                    </div>

                    <div className="evidence-actions">
                        <InertiaLink
                            href={`/evidence/${evidence.id}`}
                            className="evidence-button evidence-button-secondary"
                        >
                            Cancelar
                        </InertiaLink>

                        <button
                            type="submit"
                            className="evidence-button evidence-button-primary"
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

EvidenceEdit.layout = {
    breadcrumbs: [
        {
            title: 'Links',
            href: index(),
        },
    ],
};
