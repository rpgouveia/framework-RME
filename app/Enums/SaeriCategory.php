<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * SAERI classification of a mitigation measure.
 *
 * @todo These cases are a placeholder. Replace them with the real SAERI
 *       taxonomy from the source paper before the team builds forms on top
 *       of them. The database column is a plain string, so changing this
 *       enum needs no migration.
 */
enum SaeriCategory: string
{
    use EnumOptions;

    case Technical = 'technical';
    case Organizational = 'organizational';
    case Regulatory = 'regulatory';
    case Ethical = 'ethical';
    case Educational = 'educational';
}
