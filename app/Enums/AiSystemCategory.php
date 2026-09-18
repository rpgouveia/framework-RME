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
}
