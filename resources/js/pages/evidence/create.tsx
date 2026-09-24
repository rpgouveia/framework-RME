import { Head, Link as InertiaLink, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { index } from '@/routes/links';
import type { EnumOption, EvidenceType, Link } from '@/types/models';

import '../../../css/evidence.css';

type Props = {
    link: Link;
    types: EnumOption[];
};

export default function EvidenceCreate({ link, types }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        type: '' as EvidenceType | '',
        description: '',
        registration_date: new Date().toISOString().split('T')[0],
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        post(`/links/${link.id}/evidence`);
    }

    return (
        <>
            <Head title="Registrar evidência" />

            <main className="evidence-page evidence-create">
                <header className="evidence-header">
                    <div>
                        <span className="evidence-eyebrow">
                            Vínculo #{link.id}
                        </span>

                        <h1>Registrar evidência</h1>

                        <p>
                            Registre uma nova evidência relacionada a este
                            vínculo.
                        </p>
                    </div>
                </header>

                <section className="evidence-section">
                    <h2>Vínculo relacionado</h2>

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
                            <option value="">Selecione um tipo</option>

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
                            placeholder="Descreva a evidência registrada..."
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
                            href={`/links/${link.id}/evidence`}
                            className="evidence-button evidence-button-secondary"
                        >
                            Voltar
                        </InertiaLink>

                        <button
                            type="submit"
                            className="evidence-button evidence-button-primary"
                            disabled={processing}
                        >
                            {processing
                                ? 'Registrando...'
                                : 'Registrar evidência'}
                        </button>
                    </div>
                </form>
            </main>
        </>
    );
}

EvidenceCreate.layout = {
    breadcrumbs: [
        {
            title: 'Links',
            href: index(),
        },
    ],
};
