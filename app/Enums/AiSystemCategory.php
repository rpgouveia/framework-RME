<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Risk tier an AI system falls into, following the EU AI Act classification.
 *
 * @todo Confirm the value list against the RME framework definition. If "category"
 *       means the system's purpose rather than its risk tier, replace these cases.
 */
enum AiSystemCategory: string
{
    use EnumOptions;

    case Unacceptable = 'unacceptable';
    case High = 'high';
    case Limited = 'limited';
    case Minimal = 'minimal';

    /**
     * Whether a system in this tier may be in operation. The unacceptable
     * tier covers practices the EU AI Act prohibits: such a system is never
     * considered in operation, so its links have no periodic review.
     */
    public function isOperable(): bool
    {
        return $this !== self::Unacceptable;
    }
}
