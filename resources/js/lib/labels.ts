import type {
    AdverseEventNature,
    AiSystemCategory,
    LinkStatus,
    UncertaintyLevel,
    VerificationStatus,
} from '@/types/models';

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

/** Badge classes for the uncertainty scale, from calm to alarming. */
export const uncertaintyBadgeClasses: Record<UncertaintyLevel, string> = {
    low: 'border-transparent bg-secondary text-secondary-foreground',
    medium: 'border-transparent bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
    high: 'border-transparent bg-red-100 text-red-900 dark:bg-red-900/40 dark:text-red-200',
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
 * Nature badges: an incident in red, a near miss outlined. Text contrast is
 * at least 8:1 (WCAG 2.1 AA) in both themes.
 */
export const adverseEventNatureBadgeClasses: Record<
    AdverseEventNature,
    string
> = {
    incident:
        'border-transparent bg-red-100 text-red-900 dark:bg-red-900/40 dark:text-red-200',
    near_miss: 'border-border bg-transparent text-foreground',
};

/**
 * Verification badges, apart from the progress ones: declared is outlined,
 * verified is green. Text contrast is at least 8.5:1 (WCAG 2.1 AA) in both
 * themes.
 */
export const verificationBadgeClasses: Record<VerificationStatus, string> = {
    declared: 'border-border bg-transparent text-foreground',
    verified:
        'border-transparent bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200',
};
