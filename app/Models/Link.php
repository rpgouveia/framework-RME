<?php

namespace App\Models;

use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use Carbon\CarbonImmutable;
use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The link between a risk, the mitigation addressing it and its owner.
 *
 * This is the core entity of the framework: it carries the cost, schedule and
 * status of a mitigation applied to a specific risk.
 *
 * @property int $id
 * @property LifecyclePhase $lifecycle_phase
 * @property LinkStatus $status
 * @property float $estimated_cost
 * @property float|null $observed_cost
 * @property CarbonImmutable $creation_date
 * @property CarbonImmutable $next_review_date
 * @property int $risk_id
 * @property int $mitigation_id
 * @property int $owner_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Risk $risk
 * @property-read Mitigation $mitigation
 * @property-read Owner $owner
 * @property-read Collection<int, StatusHistory> $statusHistories
 * @property-read int|null $status_histories_count
 * @property-read Collection<int, Evidence> $evidence
 * @property-read int|null $evidence_count
 */
#[Fillable([
    'lifecycle_phase',
    'status',
    'estimated_cost',
    'observed_cost',
    'creation_date',
    'next_review_date',
    'risk_id',
    'mitigation_id',
    'owner_id',
])]
class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory;

    /**
     * The risk being mitigated.
     *
     * @return BelongsTo<Risk, $this>
     */
    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    /**
     * The mitigation being applied.
     *
     * @return BelongsTo<Mitigation, $this>
     */
    public function mitigation(): BelongsTo
    {
        return $this->belongsTo(Mitigation::class);
    }

    /**
     * The role accountable for this link.
     *
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * The trail of status changes for this link.
     *
     * @return HasMany<StatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    /**
     * The evidence collected for this link.
     *
     * @return HasMany<Evidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lifecycle_phase' => LifecyclePhase::class,
            'status' => LinkStatus::class,
            'estimated_cost' => 'float',
            'observed_cost' => 'float',
            'creation_date' => 'date',
            'next_review_date' => 'date',
        ];
    }
}
