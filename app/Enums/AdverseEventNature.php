<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Whether the adverse event caused harm or was caught before it did (0019,
 * item 5). Both trigger the directed reassessment.
 */
enum AdverseEventNature: string
{
    use EnumOptions;

    case Incident = 'incident';
    case NearMiss = 'near_miss';
}
