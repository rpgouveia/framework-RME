<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Phase of the AI system lifecycle: the eight stages of ISO/IEC 22989,
 * section 6.2. Defined in docs/glossary.md (decision 0028, item 6).
 *
 * Shared by risks (where the risk was identified), links (where the
 * mitigation is applied) and reassessments (where the cause originated).
 */
enum LifecyclePhase: string
{
    use EnumOptions;

    case Inception = 'inception';
    case DesignAndDevelopment = 'design_and_development';
    case VerificationAndValidation = 'verification_and_validation';
    case Deployment = 'deployment';
    case OperationAndMonitoring = 'operation_and_monitoring';
    case ContinuousValidation = 'continuous_validation';
    case ReEvaluation = 're_evaluation';
    case Retirement = 'retirement';
}
