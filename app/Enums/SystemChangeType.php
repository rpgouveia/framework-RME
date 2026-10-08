<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * What changed in an AI system (0021): the two reassessment triggers of RF05
 * besides adverse events and review due.
 */
enum SystemChangeType: string
{
    use EnumOptions;

    case ModelVersion = 'model_version';
    case DataChange = 'data_change';

    /**
     * The origin a reversal caused by this change records.
     */
    public function origin(): ChangeOrigin
    {
        return match ($this) {
            self::ModelVersion => ChangeOrigin::ModelVersion,
            self::DataChange => ChangeOrigin::DataChange,
        };
    }
}
