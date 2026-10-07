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

export type AdverseEventType =
    | 'malfunction'
    | 'data_breach'
    | 'biased_outcome'
    | 'safety_incident'
    | 'compliance_violation'
    | 'user_harm'
    | 'service_disruption';

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
