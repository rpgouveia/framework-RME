import type {
    AiSystemCategory,
    CostLevel,
    LifecyclePhase,
    LinkStatus,
    RiskCategory,
    SystemSourceType,
    UncertaintyLevel,
} from '@/types/models';

/**
 * Brazilian Portuguese labels for the enum values the backend sends raw,
 * e.g. `third_party`. Typed by the enum unions, so a new case that is missing
 * here fails the type check.
 */
export const sourceTypeLabels: Record<SystemSourceType, string> = {
    internal: 'Interno',
    third_party: 'Terceiros',
    open_source: 'Código aberto',
    hybrid: 'Híbrido',
};

export const categoryLabels: Record<AiSystemCategory, string> = {
    unacceptable: 'Inaceitável',
    high: 'Alto',
    limited: 'Limitado',
    minimal: 'Mínimo',
};

/**
 * Badge classes for the EU AI Act risk tiers, coloured by severity. shadcn has
 * no warning variant, so the tones are spelled out here.
 */
export const categoryBadgeClasses: Record<AiSystemCategory, string> = {
    unacceptable:
        'border-transparent bg-destructive text-white dark:bg-destructive/60',
    high: 'border-transparent bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
    limited: 'border-transparent bg-secondary text-secondary-foreground',
    minimal: 'border-transparent bg-secondary text-secondary-foreground',
};

export const riskCategoryLabels: Record<RiskCategory, string> = {
    privacy: 'Privacidade',
    security: 'Cibersegurança',
    fairness: 'Equidade',
    safety: 'Segurança',
    transparency: 'Transparência',
    accountability: 'Responsabilização',
    robustness: 'Robustez',
    societal: 'Impacto social',
};

export const lifecyclePhaseLabels: Record<LifecyclePhase, string> = {
    inception: 'Concepção',
    design: 'Projeto',
    data_collection: 'Coleta de dados',
    development: 'Desenvolvimento',
    validation: 'Validação',
    deployment: 'Implantação',
    monitoring: 'Monitoramento',
    decommissioning: 'Descomissionamento',
};

export const uncertaintyLevelLabels: Record<UncertaintyLevel, string> = {
    low: 'Baixa',
    medium: 'Média',
    high: 'Alta',
};

/** Badge classes for the uncertainty scale, from calm to alarming. */
export const uncertaintyBadgeClasses: Record<UncertaintyLevel, string> = {
    low: 'border-transparent bg-secondary text-secondary-foreground',
    medium: 'border-transparent bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
    high: 'border-transparent bg-red-100 text-red-900 dark:bg-red-900/40 dark:text-red-200',
};

export const linkStatusLabels: Record<LinkStatus, string> = {
    planned: 'Planejado',
    in_progress: 'Em andamento',
    implemented: 'Implementado',
    monitoring: 'Em monitoramento',
    suspended: 'Suspenso',
    cancelled: 'Cancelado',
};

export const costLevelLabels: Record<CostLevel, string> = {
    low: 'Baixo',
    medium: 'Médio',
    high: 'Alto',
};

/** Looks up an enum value's label, falling back to the raw value. */
export function labelFor(
    labels: Record<string, string>,
    value: string,
): string {
    return labels[value] ?? value;
}
