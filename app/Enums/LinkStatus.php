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

    /**
     * Whether a link in this status may move to the given one.
     *
     * A change must actually change the status. A cancelled link can only be
     * reactivated, going back to planned: links are permanent, so this is how
     * a closed risk and mitigation pair is reopened. The other moves stay free
     * until the reassessment step defines the full matrix.
     */
    public function canTransitionTo(self $status): bool
    {
        if ($status === $this) {
            return false;
        }

        return $this !== self::Cancelled || $status === self::Planned;
    }

    /**
     * The statuses a link in this status may move to, shaped for a select.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function transitionOptions(): array
    {
        return array_values(array_filter(
            self::options(),
            fn (array $option): bool => $this->canTransitionTo(self::from($option['value'])),
        ));
    }
}
