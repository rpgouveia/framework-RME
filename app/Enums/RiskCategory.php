<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Nature of the harm an identified risk may cause.
 *
 * @todo Confirm the value list against the RME framework taxonomy.
 */
enum RiskCategory: string
{
    use EnumOptions;

    case Privacy = 'privacy';
    case Security = 'security';
    case Fairness = 'fairness';
    case Safety = 'safety';
    case Transparency = 'transparency';
    case Accountability = 'accountability';
    case Robustness = 'robustness';
    case Societal = 'societal';
}
