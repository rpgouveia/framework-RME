import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { index } from '@/routes/mitigations';
import type { Mitigation, Paginated, SaeriCategory } from '@/types/models';

import '../../../css/mitigations.css';

type Props = {
    mitigations: Paginated<Mitigation>;
};

type FiltroCategoria = SaeriCategory | 'all';

export default function MitigationsIndex({ mitigations }: Props) {
    const [categoria, setCategoria] = useState<FiltroCategoria>('all');

    const [mitigacaoSelecionada, setMitigacaoSelecionada] = useState<
        number | null
    >(null);

    const [riscoSelecionado, setRiscoSelecionado] = useState('');

    const [responsavel, setResponsavel] = useState('');

    const [custoEstimado, setCustoEstimado] = useState('');

    const mitigacoesFiltradas =
        categoria === 'all'
            ? mitigations.data
            : mitigations.data.filter(
                  (mitigation) => mitigation.saeri_category === categoria,
              );

    const podeSalvar =
        riscoSelecionado !== '' &&
        mitigacaoSelecionada !== null &&
        responsavel !== '' &&
        custoEstimado !== '';

    return (
        <>
            <Head title="Mitigações" />

            <main className="mitigations-page">
                {/* CABEÇALHO */}

                <header className="mitigations-header">
                    <p className="mitigations-subtitle">
                        Cadastro de sistema de IA
                    </p>

                    <h1 className="mitigations-title">
                        Selecionar mitigação e vincular ao risco
                    </h1>

                    <p className="mitigations-description">
                        Escolha o risco já cadastrado, uma mitigação do catálogo
                        e defina o responsável e o custo estimado. Campos
                        marcados com * são obrigatórios.
                    </p>
                </header>

                {/* ETAPAS */}

                <div className="mitigations-steps">
                    <span>✓ Sistema e risco</span>

                    <span className="mitigations-step-active">
                        2 Mitigação e vínculo
                    </span>

                    <span>3 Registro de evidência</span>

                    <span>4 Monitoramento e eventos</span>

                    <span>5 Reavaliação</span>
                </div>

                {/* RISCO */}

                <section className="mitigations-section">
                    <h2 className="mitigations-section-title">
                        Risco a ser mitigado
                    </h2>

                    <label className="mitigations-label">
                        Risco cadastrado
                        <span className="mitigations-required"> *</span>
                    </label>

                    <select
                        className="mitigations-select"
                        value={riscoSelecionado}
                        onChange={(event) =>
                            setRiscoSelecionado(event.target.value)
                        }
                    >
                        <option value="">
                            Selecione um risco já cadastrado
                        </option>

                        <option value="1">Viés algorítmico</option>

                        <option value="2">Alucinação em respostas</option>
                    </select>

                    <p className="mitigations-help">
                        Somente riscos já cadastrados podem ser selecionados.
                    </p>
                </section>

                {/* CATÁLOGO */}

                <section className="mitigations-section">
                    <div className="mitigations-catalog-header">
                        <h2 className="mitigations-section-title">
                            Mitigação do catálogo
                        </h2>
                    </div>

                    <div className="mitigations-filter-area">
                        <p className="mitigations-filter-title">
                            Selecione uma mitigação
                            <span className="mitigations-required"> *</span>
                        </p>

                        <p className="mitigations-filter-title">
                            Filtrar por categoria
                        </p>

                        <div className="mitigations-filters">
                            <button
                                type="button"
                                className={`mitigations-filter-button ${
                                    categoria === 'all' ? 'active' : ''
                                }`}
                                onClick={() => setCategoria('all')}
                            >
                                Todas
                            </button>

                            <button
                                type="button"
                                className={`mitigations-filter-button ${
                                    categoria === 'technical' ? 'active' : ''
                                }`}
                                onClick={() => setCategoria('technical')}
                            >
                                Técnica
                            </button>

                            <button
                                type="button"
                                className={`mitigations-filter-button ${
                                    categoria === 'organizational'
                                        ? 'active'
                                        : ''
                                }`}
                                onClick={() => setCategoria('organizational')}
                            >
                                Organizacional
                            </button>

                            <button
                                type="button"
                                className={`mitigations-filter-button ${
                                    categoria === 'regulatory' ? 'active' : ''
                                }`}
                                onClick={() => setCategoria('regulatory')}
                            >
                                Regulatória
                            </button>

                            <button
                                type="button"
                                className={`mitigations-filter-button ${
                                    categoria === 'ethical' ? 'active' : ''
                                }`}
                                onClick={() => setCategoria('ethical')}
                            >
                                Ética
                            </button>

                            <button
                                type="button"
                                className={`mitigations-filter-button ${
                                    categoria === 'educational' ? 'active' : ''
                                }`}
                                onClick={() => setCategoria('educational')}
                            >
                                Educacional
                            </button>
                        </div>
                    </div>

                    {/* CARDS */}

                    <div className="mitigations-grid">
                        {mitigacoesFiltradas.map((mitigation) => {
                            const selecionada =
                                mitigacaoSelecionada === mitigation.id;

                            return (
                                <button
                                    key={mitigation.id}
                                    type="button"
                                    className={`mitigation-card ${
                                        selecionada ? 'selected' : ''
                                    }`}
                                    onClick={() =>
                                        setMitigacaoSelecionada(mitigation.id)
                                    }
                                >
                                    <h3 className="mitigation-card-title">
                                        {mitigation.description}
                                    </h3>

                                    <span className="mitigation-category">
                                        {mitigation.saeri_category}
                                    </span>

                                    <p className="mitigation-risk">
                                        {mitigation.suggested_target_risk}
                                    </p>

                                    <p className="mitigation-cost">
                                        Custo sugerido:{' '}
                                        <strong>
                                            {mitigation.suggested_cost}
                                        </strong>
                                    </p>
                                </button>
                            );
                        })}
                    </div>

                    {mitigacoesFiltradas.length === 0 && (
                        <p className="mitigations-empty">
                            Nenhuma mitigação encontrada nesta categoria.
                        </p>
                    )}
                </section>

                {/* DETALHES DO VÍNCULO */}

                <section className="mitigations-section">
                    <h2 className="mitigations-section-title">
                        Detalhes do vínculo
                    </h2>

                    <div className="mitigations-details-grid">
                        {/* RESPONSÁVEL */}

                        <div>
                            <label className="mitigations-label">
                                Responsável
                                <span className="mitigations-required"> *</span>
                            </label>

                            <select
                                className="mitigations-select"
                                value={responsavel}
                                onChange={(event) =>
                                    setResponsavel(event.target.value)
                                }
                            >
                                <option value="">
                                    Selecione um papel organizacional
                                </option>

                                <option value="gestor">
                                    Gestor de Risco de IA
                                </option>

                                <option value="seguranca">
                                    Equipe de Segurança da Informação
                                </option>

                                <option value="compliance">
                                    Equipe de Compliance
                                </option>
                            </select>

                            <p className="mitigations-help">
                                Papel organizacional — não é permitido registrar
                                o nome de uma pessoa.
                            </p>
                        </div>

                        {/* CUSTO */}

                        <div>
                            <label className="mitigations-label">
                                Custo estimado
                                <span className="mitigations-required"> *</span>
                            </label>

                            <select
                                className="mitigations-select"
                                value={custoEstimado}
                                onChange={(event) =>
                                    setCustoEstimado(event.target.value)
                                }
                            >
                                <option value="">
                                    Selecione o custo estimado
                                </option>

                                <option value="low">Baixo</option>

                                <option value="medium">Médio</option>

                                <option value="high">Alto</option>
                            </select>

                            <p className="mitigations-help">
                                Escala qualitativa de custo.
                            </p>
                        </div>
                    </div>
                </section>

                {/* REGRA DE NEGÓCIO */}

                <section className="mitigations-rule">
                    <p>
                        <strong>Antes de salvar:</strong> o botão "Salvar
                        vínculo" permanece desabilitado enquanto nenhum risco
                        estiver selecionado — não é possível cadastrar uma
                        mitigação sem um risco alvo.
                    </p>

                    <p>
                        <strong>Ao salvar:</strong> o vínculo nasce com status
                        Declarada, gera um registro no histórico e o sistema
                        calcula automaticamente a próxima data de revisão
                        somando a data de criação ao intervalo de revisão
                        periódica definido no protocolo de monitoramento.
                    </p>
                </section>

                {/* BOTÕES */}

                <div className="mitigations-actions">
                    <button type="button" className="mitigations-back-button">
                        Voltar
                    </button>

                    <button
                        type="button"
                        className="mitigations-save-button"
                        disabled={!podeSalvar}
                    >
                        Salvar vínculo
                    </button>
                </div>
            </main>
        </>
    );
}

MitigationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Mitigations',
            href: index(),
        },
    ],
};
