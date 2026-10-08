<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Whether evidence proves a link is in place (0013): a link is born
 * declared and becomes verified on evidence. A reversal takes it back to
 * declared, and then only evidence recorded after the reversal counts.
 */
enum VerificationStatus: string
{
    use EnumOptions;

    case Declared = 'declared';
    case Verified = 'verified';
}
