import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { index } from '@/routes/mitigations';

import type {
    CostLevel,
    EnumOption,
    Mitigation,
    SaeriCategory,
    UncertaintyLevel,
} from '@/types/models';

import '../../../css/mitigations.css';

type Props = {
    mitigation: Mitigation;
    saeriCategories: EnumOption[];
    costLevels: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function MitigationsEdit({
    mitigation,
    saeriCategories,
    costLevels,
    uncertaintyLevels,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        description: mitigation.description ?? '',
        saeri_category: mitigation.saeri_category,
        suggested_target_risk: mitigation.suggested_target_risk ?? '',
        expected_evidence: mitigation.expected_evidence ?? '',
        suggested_cost: mitigation.suggested_cost,
        uncertainty_level: mitigation.uncertainty_level,
        bibliography_source: mitigation.bibliography_source ?? '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        put(`/mitigations/${mitigation.id}`);
    }

    return (
        <>
            <Head title="Editar mitigação" />

            <main className="mitigations-page">
                {/* CABEÇALHO */}

                <header className="mitigations-header">
                    <p className="mitigations-subtitle">
                        Catálogo de mitigações
                    </p>

                    <h1 className="mitigations-title">Editar mitigação</h1>

                    <p className="mitigations-description">
                        Atualize as informações da mitigação selecionada.
                    </p>
                </header>

                {/* FORMULÁRIO */}

                <form onSubmit={submit}>
                    <section className="mitigations-section">
                        <h2 className="mitigations-section-title">
                            Informações da mitigação
                        </h2>

                        {/* DESCRIÇÃO */}

                        <div className="mitigations-form-group">
                            <label
                                htmlFor="description"
                                className="mitigations-label"
                            >
                                Descrição
                                <span className="mitigations-required"> *</span>
                            </label>

                            <textarea
                                id="description"
                                className="mitigations-input mitigations-textarea"
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                            />

                            {errors.description && (
                                <p className="mitigations-error">
                                    {errors.description}
                                </p>
                            )}
                        </div>

                        {/* CATEGORIA SAERI */}

                        <div className="mitigations-form-group">
                            <label
                                htmlFor="saeri_category"
                                className="mitigations-label"
                            >
                                Categoria SAERI
                                <span className="mitigations-required"> *</span>
                            </label>

                            <select
                                id="saeri_category"
                                className="mitigations-select"
                                value={data.saeri_category}
                                onChange={(event) =>
                                    setData(
                                        'saeri_category',
                                        event.target.value as SaeriCategory,
                                    )
                                }
                            >
                                {saeriCategories.map((category) => (
                                    <option
                                        key={category.value}
                                        value={category.value}
                                    >
                                        {category.label}
                                    </option>
                                ))}
                            </select>

                            {errors.saeri_category && (
                                <p className="mitigations-error">
                                    {errors.saeri_category}
                                </p>
                            )}
                        </div>

                        {/* RISCO-ALVO SUGERIDO */}

                        <div className="mitigations-form-group">
                            <label
                                htmlFor="suggested_target_risk"
                                className="mitigations-label"
                            >
                                Risco-alvo sugerido
                            </label>

                            <textarea
                                id="suggested_target_risk"
                                className="mitigations-input mitigations-textarea"
                                value={data.suggested_target_risk}
                                onChange={(event) =>
                                    setData(
                                        'suggested_target_risk',
                                        event.target.value,
                                    )
                                }
                            />

                            {errors.suggested_target_risk && (
                                <p className="mitigations-error">
                                    {errors.suggested_target_risk}
                                </p>
                            )}
                        </div>

                        {/* EVIDÊNCIA ESPERADA */}

                        <div className="mitigations-form-group">
                            <label
                                htmlFor="expected_evidence"
                                className="mitigations-label"
                            >
                                Evidência esperada
                            </label>

                            <textarea
                                id="expected_evidence"
                                className="mitigations-input mitigations-textarea"
                                value={data.expected_evidence}
                                onChange={(event) =>
                                    setData(
                                        'expected_evidence',
                                        event.target.value,
                                    )
                                }
                            />

                            {errors.expected_evidence && (
                                <p className="mitigations-error">
                                    {errors.expected_evidence}
                                </p>
                            )}
                        </div>

                        {/* CUSTO E INCERTEZA */}

                        <div className="mitigations-details-grid">
                            {/* CUSTO SUGERIDO */}

                            <div className="mitigations-form-group">
                                <label
                                    htmlFor="suggested_cost"
                                    className="mitigations-label"
                                >
                                    Custo sugerido
                                    <span className="mitigations-required">
                                        {' '}
                                        *
                                    </span>
                                </label>

                                <select
                                    id="suggested_cost"
                                    className="mitigations-select"
                                    value={data.suggested_cost}
                                    onChange={(event) =>
                                        setData(
                                            'suggested_cost',
                                            event.target.value as CostLevel,
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

                                {errors.suggested_cost && (
                                    <p className="mitigations-error">
                                        {errors.suggested_cost}
                                    </p>
                                )}
                            </div>

                            {/* NÍVEL DE INCERTEZA */}

                            <div className="mitigations-form-group">
                                <label
                                    htmlFor="uncertainty_level"
                                    className="mitigations-label"
                                >
                                    Nível de incerteza
                                    <span className="mitigations-required">
                                        {' '}
                                        *
                                    </span>
                                </label>

                                <select
                                    id="uncertainty_level"
                                    className="mitigations-select"
                                    value={data.uncertainty_level}
                                    onChange={(event) =>
                                        setData(
                                            'uncertainty_level',
                                            event.target
                                                .value as UncertaintyLevel,
                                        )
                                    }
                                >
                                    {uncertaintyLevels.map((level) => (
                                        <option
                                            key={level.value}
                                            value={level.value}
                                        >
                                            {level.label}
                                        </option>
                                    ))}
                                </select>

                                {errors.uncertainty_level && (
                                    <p className="mitigations-error">
                                        {errors.uncertainty_level}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* FONTE BIBLIOGRÁFICA */}

                        <div className="mitigations-form-group">
                            <label
                                htmlFor="bibliography_source"
                                className="mitigations-label"
                            >
                                Fonte bibliográfica
                            </label>

                            <input
                                id="bibliography_source"
                                type="text"
                                className="mitigations-input"
                                value={data.bibliography_source}
                                onChange={(event) =>
                                    setData(
                                        'bibliography_source',
                                        event.target.value,
                                    )
                                }
                            />

                            {errors.bibliography_source && (
                                <p className="mitigations-error">
                                    {errors.bibliography_source}
                                </p>
                            )}
                        </div>
                    </section>

                    {/* BOTÕES */}

                    <div className="mitigations-actions">
                        <Link
                            href={index()}
                            className="mitigations-back-button"
                        >
                            Cancelar
                        </Link>

                        <button
                            type="submit"
                            className="mitigations-save-button"
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

MitigationsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Mitigations',
            href: index(),
        },
    ],
};
