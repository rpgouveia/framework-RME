<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Kind of artifact recorded as evidence that a mitigation is in place.
 *
 * @todo Confirm the value list against the RME framework definition.
 */
enum EvidenceType: string
{
    use EnumOptions;

    case Document = 'document';
    case Report = 'report';
    case AuditLog = 'audit_log';
    case TestResult = 'test_result';
    case Certification = 'certification';
    case MeetingMinutes = 'meeting_minutes';
}
