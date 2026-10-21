<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Qualitative effort a mitigation costs to put in place, not only money.
 * Defined in docs/glossary.md (decisions 0024 and 0028, item 2).
 *
 * Shared by a mitigation's suggested cost, a link's estimated cost and the
 * observed cost recorded on evidence (0018). The rubric of 0024 applies to
 * all three, changing only the reference, so they stay comparable.
 */
enum CostLevel: string
{
    use EnumOptions;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
