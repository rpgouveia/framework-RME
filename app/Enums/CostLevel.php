<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Qualitative effort a mitigation costs to put in place.
 *
 * Shared by a mitigation's suggested cost and by both cost columns of a link,
 * so an estimate and the cost actually observed stay comparable.
 *
 * @todo Confirm the value list against the RME framework definition. If the
 *       team later needs real currency amounts, add a numeric column next to
 *       these rather than widening the scale.
 */
enum CostLevel: string
{
    use EnumOptions;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
