<?php

namespace App\Models;

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use Carbon\CarbonImmutable;
use Database\Factories\RiskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A risk identified for a given AI system.
 *
 * @property int $id
 * @property string $description
 * @property RiskCategory $category
 * @property LifecyclePhase $lifecycle_phase
 * @property UncertaintyLevel $uncertainty_level
 * @property int $ai_system_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AiSystem $aiSystem
 * @property-read Collection<int, Link> $links
 * @property-read int|null $links_count
 */
#[Fillable(['description', 'category', 'lifecycle_phase', 'uncertainty_level', 'ai_system_id'])]
class Risk extends Model
{
    /** @use HasFactory<RiskFactory> */
    use HasFactory;

    /**
     * The AI system this risk was identified for.
     *
     * @return BelongsTo<AiSystem, $this>
     */
    public function aiSystem(): BelongsTo
    {
        return $this->belongsTo(AiSystem::class);
    }

    /**
     * The mitigation links addressing this risk.
     *
     * @return HasMany<Link, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RiskCategory::class,
            'lifecycle_phase' => LifecyclePhase::class,
            'uncertainty_level' => UncertaintyLevel::class,
        ];
    }
}
