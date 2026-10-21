<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * Kind of artifact recorded as evidence that a mitigation is in place, told
 * apart by what it proves. Defined in docs/glossary.md (decision 0028, item 7).
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
