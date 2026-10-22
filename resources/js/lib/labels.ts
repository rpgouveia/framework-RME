import type {
    AdverseEventNature,
    AiSystemCategory,
    LinkStatus,
    UncertaintyLevel,
    VerificationStatus,
} from '@/types/models';

/*
 * Badge classes over the design system's tag tokens (tag-gray, tag-blue,
 * tag-green, tag-yellow, tag-red). Each pair holds at least 5.8:1 in both
 * themes; outlined badges use the page text on a strong border.
 */
const gray = 'border-transparent bg-tag-gray text-tag-gray-foreground';
const blue = 'border-transparent bg-tag-blue text-tag-blue-foreground';
const green = 'border-transparent bg-tag-green text-tag-green-foreground';
const yellow = 'border-transparent bg-tag-yellow text-tag-yellow-foreground';
const red = 'border-transparent bg-tag-red text-tag-red-foreground';
const outlined = 'border-border-strong bg-transparent text-foreground';

/** Badge classes for the EU AI Act risk tiers. */
export const categoryBadgeClasses: Record<AiSystemCategory, string> = {
    unacceptable: 'border-transparent bg-destructive text-white',
    high: yellow,
    limited: gray,
    minimal: gray,
};

/**
 * Badge classes for the uncertainty scale. Uncertainty is the confidence in
 * an assessment, not a severity (0028), so every level stays neutral.
 */
export const uncertaintyBadgeClasses: Record<UncertaintyLevel, string> = {
    low: gray,
    medium: gray,
    high: gray,
};

/** How many of the three level bars each uncertainty level fills. */
export const uncertaintyLevelBars: Record<UncertaintyLevel, number> = {
    low: 1,
    medium: 2,
    high: 3,
};

/** Badge classes for a link's progress, from not started to settled. */
export const linkStatusBadgeClasses: Record<LinkStatus, string> = {
    planned: gray,
    in_progress: blue,
    implemented: green,
    suspended: yellow,
    // Out of the flow, so outlined instead of filled.
    cancelled: outlined,
};

/** Nature badges: an incident in red, a near miss outlined. */
export const adverseEventNatureBadgeClasses: Record<
    AdverseEventNature,
    string
> = {
    incident: red,
    near_miss: outlined,
};

/** Verification badges: declared is outlined, verified is green. */
export const verificationBadgeClasses: Record<VerificationStatus, string> = {
    declared: outlined,
    verified: green,
};
