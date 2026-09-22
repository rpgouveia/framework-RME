<?php

namespace App\Models;

use App\Enums\AiSystemCategory;
use App\Enums\SystemSourceType;
use Carbon\CarbonImmutable;
use Database\Factories\AiSystemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An AI system registered in the organization's portfolio.
 *
 * @property int $id
 * @property string $name
 * @property SystemSourceType $source_type
 * @property AiSystemCategory $category
 * @property CarbonImmutable $registration_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Risk> $risks
 * @property-read int|null $risks_count
 * @property-read Collection<int, AdverseEvent> $adverseEvents
 * @property-read int|null $adverse_events_count
 */
#[Fillable(['name', 'source_type', 'category', 'registration_date'])]
class AiSystem extends Model
{
    /** @use HasFactory<AiSystemFactory> */
    use HasFactory;

    /**
     * The risks identified for this system.
     *
     * @return HasMany<Risk, $this>
     */
    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class);
    }

    /**
     * The adverse events observed on this system.
     *
     * @return HasMany<AdverseEvent, $this>
     */
    public function adverseEvents(): HasMany
    {
        return $this->hasMany(AdverseEvent::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => SystemSourceType::class,
            'category' => AiSystemCategory::class,
            'registration_date' => 'date',
        ];
    }
}
