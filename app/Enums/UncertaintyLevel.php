<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * How confident an assessment is: that a risk exists in the system, or the
 * estimates of a mitigation. High uncertainty means low confidence. Defined
 * in docs/glossary.md (decisions 0024 and 0028, item 3).
 */
enum UncertaintyLevel: string
{
    use EnumOptions;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
