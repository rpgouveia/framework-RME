import { Head, Link, useForm } from '@inertiajs/react';
import { index } from '@/routes/mitigations';
import type { EnumOption } from '@/types/models';

import '../../../css/mitigations.css';

type Props = {
    saeriCategories: EnumOption[];
    costLevels: EnumOption[];
    uncertaintyLevels: EnumOption[];
};

export default function MitigationsCreate({
    saeriCategories,
    costLevels,
    uncertaintyLevels,
}: Props) {
    /*
     * useForm guarda os dados do formulário.
     *
     * Cada propriedade corresponde a um campo
     * que será enviado para o Laravel.
     */
    const { data, setData, post, processing, errors } = useForm({
        description: '',
        saeri_category: '',
        suggested_target_risk: '',
        expected_evidence: '',
        suggested_cost: '',
        uncertainty_level: '',
        bibliography_source: '',
    });

    /*
     * Função executada quando o usuário
     * clicar em "Cadastrar mitigação".
     */
    function submit(event: React.FormEvent) {
        event.preventDefault();

        post('/mitigations');
    }

    return (
        <>
            <Head title="Cadastrar mitigação" />

            <main className="mitigations-page">
                {/* CABEÇALHO */}

                <header className="mitigations-header">
                    <p className="mitigations-subtitle">
                        Catálogo de mitigações
                    </p>

                    <h1 className="mitigations-title">Cadastrar mitigação</h1>

                    <p className="mitigations-description">
                        Cadastre uma nova mitigação no catálogo C2. Campos
                        marcados com * são obrigatórios.
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
                                className="mitigations-label"
                                htmlFor="description"
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
                                placeholder="Descreva a mitigação"
                            />

                            {errors.description && (
                                <p className="mitigations-error">
                                    {errors.description}
                                </p>
                            )}
                        </div>

                        {/* CATEGORIA */}

                        <div className="mitigations-form-group">
                            <label
                                className="mitigations-label"
                                htmlFor="saeri_category"
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
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">
                                    Selecione uma categoria
                                </option>

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

                        {/* RISCO ALVO */}

                        <div className="mitigations-form-group">
                            <label
                                className="mitigations-label"
                                htmlFor="suggested_target_risk"
                            >
                                Risco-alvo sugerido
                                <span className="mitigations-required"> *</span>
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
                                placeholder="Informe qual risco esta mitigação pretende reduzir"
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
                                className="mitigations-label"
                                htmlFor="expected_evidence"
                            >
                                Evidência esperada
                                <span className="mitigations-required"> *</span>
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
                                placeholder="Descreva a evidência esperada"
                            />

                            {errors.expected_evidence && (
                                <p className="mitigations-error">
                                    {errors.expected_evidence}
                                </p>
                            )}
                        </div>

                        {/* CUSTO E INCERTEZA */}

                        <div className="mitigations-details-grid">
                            {/* CUSTO */}

                            <div>
                                <label
                                    className="mitigations-label"
                                    htmlFor="suggested_cost"
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
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">Selecione o custo</option>

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

                            {/* INCERTEZA */}

                            <div>
                                <label
                                    className="mitigations-label"
                                    htmlFor="uncertainty_level"
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
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        Selecione o nível de incerteza
                                    </option>

                                    {uncertaintyLevels.map((uncertainty) => (
                                        <option
                                            key={uncertainty.value}
                                            value={uncertainty.value}
                                        >
                                            {uncertainty.label}
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
                                className="mitigations-label"
                                htmlFor="bibliography_source"
                            >
                                Fonte bibliográfica
                                <span className="mitigations-required"> *</span>
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
                                placeholder="Informe a referência ou fonte"
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
                            {processing
                                ? 'Cadastrando...'
                                : 'Cadastrar mitigação'}
                        </button>
                    </div>
                </form>
            </main>
        </>
    );
}

MitigationsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Mitigations',
            href: index(),
        },
    ],
};
