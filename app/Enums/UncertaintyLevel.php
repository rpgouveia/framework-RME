<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * How confident the organization is in its assessment of a risk.
 *
 * @todo Confirm the value list against the RME framework definition.
 */
enum UncertaintyLevel: string
{
    use EnumOptions;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
