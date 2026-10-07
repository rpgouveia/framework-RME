import type {
    AiSystemCategory,
    CostLevel,
    EvidenceType,
    LifecyclePhase,
    Link,
    LinkStatus,
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

/** Badge classes for a link's status, from not started to settled. */
export const linkStatusBadgeClasses: Record<LinkStatus, string> = {
    planned: 'border-transparent bg-secondary text-secondary-foreground',
    in_progress:
        'border-transparent bg-sky-100 text-sky-900 dark:bg-sky-900/40 dark:text-sky-200',
    implemented:
        'border-transparent bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200',
    monitoring:
        'border-transparent bg-violet-100 text-violet-900 dark:bg-violet-900/40 dark:text-violet-200',
    suspended:
        'border-transparent bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
    // Out of the flow, so outlined instead of filled, but still legible:
    // at least 7:1 on the page, a hovered row or a muted panel, in both
    // themes (WCAG 2.1 AA asks 4.5:1 for text this size).
    cancelled:
        'border-border bg-transparent text-neutral-600 dark:text-neutral-300',
};

/**
 * Names a link by its risk and mitigation pair, which is its identity.
 * Falls back to the ids when the relations are not loaded.
 */
export function linkLabel(link: Link): string {
    const risk = link.risk?.name ?? `Risco #${link.risk_id}`;
    const mitigation =
        link.mitigation?.name ?? `Mitigação #${link.mitigation_id}`;

    return `${risk} → ${mitigation}`;
}

export const costLevelLabels: Record<CostLevel, string> = {
    low: 'Baixo',
    medium: 'Médio',
    high: 'Alto',
};

export const evidenceTypeLabels: Record<EvidenceType, string> = {
    document: 'Documento',
    report: 'Relatório',
    audit_log: 'Log de auditoria',
    test_result: 'Resultado de teste',
    certification: 'Certificação',
    meeting_minutes: 'Ata de reunião',
};

/** A taxonomy term as its code and name, such as "1.2 Gestão de riscos". */
export function termLabel(term: { code: string; name: string }): string {
    return `${term.code} ${term.name}`;
}

/** The codes of an adverse event's risk subdomains, such as "2.1, 2.2". */
export function subdomainCodes(event: {
    risk_subdomains?: { code: string }[];
}): string {
    return (event.risk_subdomains ?? [])
        .map((subdomain) => subdomain.code)
        .join(', ');
}

/** Looks up an enum value's label, falling back to the raw value. */
export function labelFor(
    labels: Record<string, string>,
    value: string,
): string {
    return labels[value] ?? value;
}
