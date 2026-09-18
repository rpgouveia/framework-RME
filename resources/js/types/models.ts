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

export type SaeriCategory =
    | 'technical'
    | 'organizational'
    | 'regulatory'
    | 'ethical'
    | 'educational';

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
    estimated_cost: number;
    observed_cost: number | null;
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
    previous_status: LinkStatus;
    new_status: LinkStatus;
    change_date: string;
    link_id: number;
    owner_id: number;
    link?: Link;
    owner?: Owner;
}

export interface Evidence extends Timestamps {
    id: number;
    type: EvidenceType;
    description: string;
    registration_date: string;
    link_id: number;
    link?: Link;
}
