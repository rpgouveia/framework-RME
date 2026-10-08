<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * What triggered a status history entry (0018). Only a manual entry has a
 * person behind it; the others are recorded by the system.
 */
enum ChangeOrigin: string
{
    use EnumOptions;

    case Manual = 'manual';
    case ReviewDue = 'review_due';
    case AdverseEvent = 'adverse_event';
    case SystemReclassification = 'system_reclassification';

    public function isAutomatic(): bool
    {
        return $this !== self::Manual;
    }
}
