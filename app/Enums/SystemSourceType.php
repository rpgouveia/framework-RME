<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * How an AI system came to be part of the organization's portfolio: the
 * organization's relation to it, after the roles of ISO/IEC 22989 (5.19).
 * Defined in docs/glossary.md (decision 0028, item 8).
 */
enum SystemSourceType: string
{
    use EnumOptions;

    case Internal = 'internal';
    case ThirdParty = 'third_party';
    case OpenSource = 'open_source';
    case Hybrid = 'hybrid';
}
