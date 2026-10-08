<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Where the cause analysis of a reassessment stands (0020, item 6).
 */
enum CauseStatus: string
{
    use EnumOptions;

    /** The cause and the lifecycle phase it came from were found. */
    case Identified = 'identified';

    /** Whoever reassessed chose, explicitly, not to state a cause. */
    case NotIdentified = 'not_identified';

    /** A review due or a reclassification has no cause to find. */
    case NotApplicable = 'not_applicable';
}
