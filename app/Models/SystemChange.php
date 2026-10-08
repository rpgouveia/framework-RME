<?php

namespace App\Models;

use App\Enums\ChangeOrigin;
use App\Enums\SystemChangeType;
use Carbon\CarbonImmutable;
use Database\Factories\SystemChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A change of an AI system, registered by hand, that triggers reassessment
 * (0021): a new model version or a change in the data. Recording it reverts
 * the system's verified links, those of the affected subdomains when they are
 * given, all of them otherwise. It is append only.
 *
 * @property int $id
 * @property int $ai_system_id
 * @property SystemChangeType $type
 * @property string $description
 * @property CarbonImmutable $change_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AiSystem $aiSystem
 * @property-read Collection<int, TaxonomyTerm> $riskSubdomains
 * @property-read Collection<int, StatusHistory> $reversals
 */
#[Fillable(['ai_system_id', 'type', 'description', 'change_date'])]
class SystemChange extends Model
{
    /** @use HasFactory<SystemChangeFactory> */
    use HasFactory;

    /**
     * The system that changed.
     *
     * @return BelongsTo<AiSystem, $this>
     */
    public function aiSystem(): BelongsTo
    {
        return $this->belongsTo(AiSystem::class);
    }

    /**
     * The MIT risk subdomains the change affects, if known.
     *
     * @return BelongsToMany<TaxonomyTerm, $this>
     */
    public function riskSubdomains(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyTerm::class, 'system_change_risk_subdomains', 'system_change_id', 'risk_subdomain_id')
            ->orderBy('position');
    }

    /**
     * The reversals the change caused when it was recorded.
     *
     * @return HasMany<StatusHistory, $this>
     */
    public function reversals(): HasMany
    {
        return $this->hasMany(StatusHistory::class)
            ->whereIn('origin', [ChangeOrigin::ModelVersion, ChangeOrigin::DataChange]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SystemChangeType::class,
            'change_date' => 'date',
        ];
    }
}
