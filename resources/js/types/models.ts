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

/** Whether an adverse event caused harm or was caught first (0019). */
export type AdverseEventNature = 'incident' | 'near_miss';

/** Whether evidence proves a link (0013): born declared, verified on evidence. */
export type VerificationStatus = 'declared' | 'verified';

/** What triggered a status history entry; only manual has an author (0018). */
export type ChangeOrigin =
    | 'manual'
    | 'review_due'
    | 'adverse_event'
    | 'system_reclassification';

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
    /** Descriptive only, such as "Atendimento ao cliente". */
    application_domain: string | null;
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
    name: string;
    description: string;
    /** A subdomain (level 2) of the MIT AI risk domain taxonomy. */
    risk_subdomain_id: number;
    /** The subdomain; its parent is the domain. */
    risk_subdomain?: TaxonomyTerm;
    lifecycle_phase: LifecyclePhase;
    uncertainty_level: UncertaintyLevel;
    ai_system_id: number;
    ai_system?: AiSystem;
    links?: Link[];
    links_count?: number;
}

/** A node of a reference taxonomy, such as a Saeri category or subcategory. */
export interface TaxonomyTerm extends Timestamps {
    id: number;
    taxonomy_id: number;
    parent_id: number | null;
    code: string;
    level: number;
    /** The Portuguese name shown in the app. */
    name: string;
    /** The name in the source, verbatim. */
    original_name: string;
    description: string | null;
    position: number;
    parent?: TaxonomyTerm | null;
}

/** A category of a taxonomy with its subcategories, for the filters. */
export interface TaxonomyCategory {
    code: string;
    name: string;
    children: { code: string; name: string }[];
}

/** A MIT risk domain with the subdomains a form offers, described. */
export interface RiskDomainOption {
    code: string;
    name: string;
    children: { code: string; name: string; description: string | null }[];
}

/** A MIT risk domain with its subdomains, as the risk form offers them. */
export interface RiskDomain {
    code: string;
    name: string;
    children: {
        id: number;
        code: string;
        name: string;
        description: string | null;
    }[];
}

export interface Mitigation extends Timestamps {
    id: number;
    /** The Portuguese name shown in the app. */
    name: string;
    /** The name in the Saeri database, verbatim. */
    source_name: string;
    /** The identifier of the mitigation in the Saeri database. */
    source_reference: string;
    /** The key of one of the taxonomy's source documents. */
    source_document: string;
    saeri_subcategory_id: number;
    /** The subcategory; its parent is the category. */
    saeri_subcategory?: TaxonomyTerm;
    description: string;
    suggested_target_risk: string;
    expected_evidence: string;
    suggested_cost: CostLevel;
    uncertainty_level: UncertaintyLevel;
    /** What backs the cost, uncertainty and expected evidence (RNF03). */
    estimate_source: string;
    /** The MIT risk subdomains the mitigation treats; never empty. */
    target_risk_subdomains?: TaxonomyTerm[];
    links?: Link[];
    links_count?: number;
}

export interface Owner extends Timestamps {
    id: number;
    organizational_role: string;
    area: string;
    /** Null while the owner is active. */
    deactivated_at: string | null;
    links?: Link[];
    links_count?: number;
    /** Links not cancelled, which keep the owner from being deactivated. */
    active_links_count?: number;
    status_histories?: StatusHistory[];
    status_histories_count?: number;
}

/** The core entity: a mitigation applied to a risk, owned by someone. */
export interface Link extends Timestamps {
    id: number;
    lifecycle_phase: LifecyclePhase;
    /** Progress of the implementation. */
    status: LinkStatus;
    /** Whether evidence proves it; changed only by verifying or reverting. */
    verification_status: VerificationStatus;
    estimated_cost: CostLevel;
    creation_date: string;
    /**
     * Null while declared (the review starts at the first verification) and
     * for a system in the unacceptable tier, which never operates.
     */
    next_review_date: string | null;
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
    /** The latest evidence that reported a cost: the observed cost (0018). */
    observed_cost_evidence?: Evidence | null;
    last_verification?: StatusHistory | null;
    last_reversal?: StatusHistory | null;
}

/** One entry of a link's trail; it changes progress or verification, never both. */
export interface StatusHistory extends Timestamps {
    id: number;
    /** Null on the first entry of a link's trail and on verification entries. */
    previous_status: LinkStatus | null;
    new_status: LinkStatus | null;
    previous_verification: VerificationStatus | null;
    new_verification: VerificationStatus | null;
    origin: ChangeOrigin;
    trigger_reason: string | null;
    change_date: string;
    link_id: number;
    /** Null only for an automatic entry. */
    owner_id: number | null;
    adverse_event_id: number | null;
    link?: Link;
    owner?: Owner | null;
    adverse_event?: AdverseEvent | null;
}

/** Something that went wrong on a system once it was in production. */
export interface AdverseEvent extends Timestamps {
    id: number;
    nature: AdverseEventNature;
    description: string;
    occurrence_date: string;
    /** When monitoring noticed it; optional (0019, item 8). */
    detected_at: string | null;
    /** The link that intercepted a near miss; it is not reverted. */
    intercepting_link_id: number | null;
    intercepting_link?: Link | null;
    /** The reversals the event triggered when it was recorded. */
    reversals?: StatusHistory[];
    /** The MIT risk subdomains the event materializes; never empty. */
    risk_subdomains?: TaxonomyTerm[];
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
    /** Optional (RF07); the latest one is the link's observed cost. */
    observed_cost: CostLevel | null;
    link_id: number;
    link?: Link;
}
