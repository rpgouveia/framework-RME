/**
 * Domain types mirroring the Eloquent models in `app/Models`.
 *
 * Keep these in sync with the PHP enums in `app/Enums` and the `#[Fillable]`
 * attributes on each model.
 */

export type SystemSourceType =
    | 'internal'
    | 'third_party'
    | 'open_source'
    | 'hybrid';

export type AiSystemCategory = 'unacceptable' | 'high' | 'limited' | 'minimal';

export type RiskCategory =
    | 'privacy'
    | 'security'
    | 'fairness'
    | 'safety'
    | 'transparency'
    | 'accountability'
    | 'robustness'
    | 'societal';

export type LifecyclePhase =
    | 'inception'
    | 'design'
    | 'data_collection'
    | 'development'
    | 'validation'
    | 'deployment'
    | 'monitoring'
    | 'decommissioning';

export type UncertaintyLevel = 'low' | 'medium' | 'high';

/** Qualitative effort a mitigation costs to put in place. */
export type CostLevel = 'low' | 'medium' | 'high';

export type AdverseEventType =
    | 'malfunction'
    | 'data_breach'
    | 'biased_outcome'
    | 'safety_incident'
    | 'compliance_violation'
    | 'user_harm'
    | 'service_disruption';

export type SaeriCategory =
    | 'governance'
    | 'technical'
    | 'process'
    | 'transparency';

export type LinkStatus =
    | 'planned'
    | 'in_progress'
    | 'implemented'
    | 'monitoring'
    | 'suspended'
    | 'cancelled';

export type EvidenceType =
    | 'document'
    | 'report'
    | 'audit_log'
    | 'test_result'
    | 'certification'
    | 'meeting_minutes';

/** A single `<option>` produced by an enum's `options()` helper. */
export interface EnumOption {
    value: string;
    label: string;
}

/** The JSON shape of a Laravel length aware paginator. */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: { url: string | null; label: string; active: boolean }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

interface Timestamps {
    created_at: string | null;
    updated_at: string | null;
}

export interface AiSystem extends Timestamps {
    id: number;
    name: string;
    source_type: SystemSourceType;
    category: AiSystemCategory;
    registration_date: string;
    risks?: Risk[];
    risks_count?: number;
    adverse_events?: AdverseEvent[];
    adverse_events_count?: number;
}

export interface Risk extends Timestamps {
    id: number;
    description: string;
    category: RiskCategory;
    lifecycle_phase: LifecyclePhase;
    uncertainty_level: UncertaintyLevel;
    ai_system_id: number;
    ai_system?: AiSystem;
    links?: Link[];
    links_count?: number;
}

export interface Mitigation extends Timestamps {
    id: number;
    description: string;
    saeri_category: SaeriCategory;
    suggested_target_risk: string;
    expected_evidence: string;
    suggested_cost: CostLevel;
    uncertainty_level: UncertaintyLevel;
    bibliography_source: string;
    links?: Link[];
    links_count?: number;
}

export interface Owner extends Timestamps {
    id: number;
    organizational_role: string;
    area: string;
    links?: Link[];
    links_count?: number;
    status_histories?: StatusHistory[];
}

/** The core entity: a mitigation applied to a risk, owned by someone. */
export interface Link extends Timestamps {
    id: number;
    lifecycle_phase: LifecyclePhase;
    status: LinkStatus;
    estimated_cost: CostLevel;
    observed_cost: CostLevel | null;
    creation_date: string;
    next_review_date: string;
    risk_id: number;
    mitigation_id: number;
    owner_id: number;
    risk?: Risk;
    mitigation?: Mitigation;
    owner?: Owner;
    evidence?: Evidence[];
    evidence_count?: number;
    status_histories?: StatusHistory[];
    status_histories_count?: number;
}

export interface StatusHistory extends Timestamps {
    id: number;
    /** Null on the first entry of a link's trail. */
    previous_status: LinkStatus | null;
    new_status: LinkStatus;
    trigger_reason: string | null;
    change_date: string;
    link_id: number;
    owner_id: number;
    adverse_event_id: number | null;
    link?: Link;
    owner?: Owner;
    adverse_event?: AdverseEvent | null;
}

/** Something that went wrong on a system once it was in production. */
export interface AdverseEvent extends Timestamps {
    id: number;
    event_type: AdverseEventType;
    description: string;
    occurrence_date: string;
    ai_system_id: number;
    ai_system?: AiSystem;
    status_histories?: StatusHistory[];
    status_histories_count?: number;
}

export interface Evidence extends Timestamps {
    id: number;
    type: EvidenceType;
    description: string;
    registration_date: string;
    link_id: number;
    link?: Link;
}
