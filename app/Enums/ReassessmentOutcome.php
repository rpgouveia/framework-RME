<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * What a reassessment concluded (0020, items 2 and 3).
 */
enum ReassessmentOutcome: string
{
    use EnumOptions;

    /** The link stands as it is, verified again in the same act. */
    case Maintain = 'maintain';

    /** The link is changed; verifying in the same act is optional. */
    case Adjust = 'adjust';

    /** The link is cancelled and a new one, for the same risk, takes over. */
    case Replace = 'replace';

    /** The link is cancelled, with no replacement. */
    case Close = 'close';

    /**
     * Whether the outcome cancels the link.
     */
    public function cancels(): bool
    {
        return $this === self::Replace || $this === self::Close;
    }
}
