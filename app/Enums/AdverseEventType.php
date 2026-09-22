<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Nature of an adverse event observed on an AI system in production.
 *
 * @todo These cases are a placeholder. Replace them with the real taxonomy
 *       from the source paper before the team builds forms on top of them.
 *       The database column is a plain string, so changing this enum needs
 *       no migration.
 */
enum AdverseEventType: string
{
    use EnumOptions;

    case Malfunction = 'malfunction';
    case DataBreach = 'data_breach';
    case BiasedOutcome = 'biased_outcome';
    case SafetyIncident = 'safety_incident';
    case ComplianceViolation = 'compliance_violation';
    case UserHarm = 'user_harm';
    case ServiceDisruption = 'service_disruption';
}
