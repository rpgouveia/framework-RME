<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Phase of the AI system lifecycle, following ISO/IEC 22989.
 *
 * Shared by risks (where the risk was identified) and links (where the
 * mitigation is applied).
 *
 * @todo Confirm the value list against the RME framework definition.
 */
enum LifecyclePhase: string
{
    use EnumOptions;

    case Inception = 'inception';
    case Design = 'design';
    case DataCollection = 'data_collection';
    case Development = 'development';
    case Validation = 'validation';
    case Deployment = 'deployment';
    case Monitoring = 'monitoring';
    case Decommissioning = 'decommissioning';
}
