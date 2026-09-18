<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * How an AI system came to be part of the organization's portfolio.
 *
 * @todo Confirm the value list against the RME framework definition.
 */
enum SystemSourceType: string
{
    use EnumOptions;

    case Internal = 'internal';
    case ThirdParty = 'third_party';
    case OpenSource = 'open_source';
    case Hybrid = 'hybrid';
}
