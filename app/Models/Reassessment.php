<?php

namespace App\Models;

use App\Enums\CauseStatus;
use App\Enums\LifecyclePhase;
use App\Enums\ReassessmentOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The reassessment of a link after a reversal (0020): what was concluded, by
 * whom and why, with the cause analysis when it applies. It is append only,
 * concludes exactly one reversal, and is written only by RecordReassessment.
 *
 * @property int $id
 * @property int $link_id
 * @property int $reversal_id
 * @property ReassessmentOutcome $outcome
 * @property int $owner_id
 * @property string $justification
 * @property CauseStatus $cause_status
 * @property string|null $cause
 * @property LifecyclePhase|null $cause_phase
 * @property list<array{field: string, before: string|null, after: string|null}>|null $changes
 * @property int|null $verification_id
 * @property CarbonImmutable $reassessment_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Link $link
 * @property-read StatusHistory $reversal
 * @property-read Owner $owner
 * @property-read StatusHistory|null $verification
 */
#[Fillable([
    'link_id',
    'reversal_id',
    'outcome',
    'owner_id',
    'justification',
    'cause_status',
    'cause',
    'cause_phase',
    'changes',
    'verification_id',
    'reassessment_date',
])]
class Reassessment extends Model
{
    /**
     * The link reassessed.
     *
     * @return BelongsTo<Link, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * The reversal this reassessment concludes.
     *
     * @return BelongsTo<StatusHistory, $this>
     */
    public function reversal(): BelongsTo
    {
        return $this->belongsTo(StatusHistory::class, 'reversal_id');
    }

    /**
     * The role that reassessed: one organizational role (0020, item 5).
     *
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * The verification recorded in the same act, if any.
     *
     * @return BelongsTo<StatusHistory, $this>
     */
    public function verification(): BelongsTo
    {
        return $this->belongsTo(StatusHistory::class, 'verification_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => ReassessmentOutcome::class,
            'cause_status' => CauseStatus::class,
            'cause_phase' => LifecyclePhase::class,
            'changes' => 'array',
            'reassessment_date' => 'date',
        ];
    }
}
