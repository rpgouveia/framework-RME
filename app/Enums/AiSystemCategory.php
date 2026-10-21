<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Risk tier an AI system falls into, following the EU AI Act classification.
 * Defined in docs/glossary.md (decisions 0016 and 0028, item 4); the
 * interface calls it the risk tier, keeping "category" for Saeri's taxonomy.
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
