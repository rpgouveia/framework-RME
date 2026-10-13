import type {
    AdverseEventNature,
    AiSystemCategory,
    CauseStatus,
    ChangeOrigin,
    CostLevel,
    EvidenceType,
    LifecyclePhase,
    LinkStatus,
    ReassessmentOutcome,
    SystemChangeType,
    SystemSourceType,
    UncertaintyLevel,
    VerificationStatus,
} from '@/types/models';

/**
 * The label of each case of the domain enums, keyed by the enum and the
 * value the backend sends raw, e.g. `third_party`. The backend resolves them
 * from the language file and shares them once (0022, item 4).
 */
export type EnumLabels = {
    AdverseEventNature: Record<AdverseEventNature, string>;
    AiSystemCategory: Record<AiSystemCategory, string>;
    CauseStatus: Record<CauseStatus, string>;
    ChangeOrigin: Record<ChangeOrigin, string>;
    CostLevel: Record<CostLevel, string>;
    EvidenceType: Record<EvidenceType, string>;
    LifecyclePhase: Record<LifecyclePhase, string>;
    LinkStatus: Record<LinkStatus, string>;
    ReassessmentOutcome: Record<ReassessmentOutcome, string>;
    SystemChangeType: Record<SystemChangeType, string>;
    SystemSourceType: Record<SystemSourceType, string>;
    UncertaintyLevel: Record<UncertaintyLevel, string>;
    VerificationStatus: Record<VerificationStatus, string>;
};

/** The page props that carry the shared enum labels. */
export type WithEnumLabels = { enumLabels: EnumLabels };

/** Looks up an enum value's label, falling back to the raw value. */
export function labelFor(
    labels: Record<string, string>,
    value: string,
): string {
    return labels[value] ?? value;
}
