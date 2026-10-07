<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AdverseEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something that went wrong on an AI system once it was in production.
 *
 * An adverse event is the evidence that a risk materialized, and it is what
 * usually triggers a link to change status, so status history entries may
 * point back at the event that caused them. It speaks the language of the
 * risk register: it is classified by the MIT risk subdomains it materializes.
 *
 * @property int $id
 * @property string $description
 * @property CarbonImmutable $occurrence_date
 * @property int $ai_system_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AiSystem $aiSystem
 * @property-read Collection<int, TaxonomyTerm> $riskSubdomains
 * @property-read int|null $risk_subdomains_count
 * @property-read Collection<int, StatusHistory> $statusHistories
 * @property-read int|null $status_histories_count
 */
#[Fillable(['description', 'occurrence_date', 'ai_system_id'])]
class AdverseEvent extends Model
{
    /** @use HasFactory<AdverseEventFactory> */
    use HasFactory;

    /**
     * The AI system the event was observed on.
     *
     * @return BelongsTo<AiSystem, $this>
     */
    public function aiSystem(): BelongsTo
    {
        return $this->belongsTo(AiSystem::class);
    }

    /**
     * The MIT risk subdomains the event materializes; never empty.
     *
     * @return BelongsToMany<TaxonomyTerm, $this>
     */
    public function riskSubdomains(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyTerm::class, 'adverse_event_risk_subdomains', 'adverse_event_id', 'risk_subdomain_id')
            ->orderBy('position');
    }

    /**
     * The status changes this event triggered.
     *
     * @return HasMany<StatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurrence_date' => 'date',
        ];
    }
}
