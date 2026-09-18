<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Implementation status of a risk/mitigation link.
 *
 * Also used for both columns of a status history entry.
 *
 * @todo Confirm the value list against the RME framework definition.
 */
enum LinkStatus: string
{
    use EnumOptions;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Implemented = 'implemented';
    case Monitoring = 'monitoring';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
}
