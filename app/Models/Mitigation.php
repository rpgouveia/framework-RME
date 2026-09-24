<?php

namespace App\Models;

use App\Enums\CostLevel;
use App\Enums\SaeriCategory;
use App\Enums\UncertaintyLevel;
use Carbon\CarbonImmutable;
use Database\Factories\MitigationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A mitigation measure that can be applied to one or more risks.
 *
 * The catalogue entry is written once and reused: it carries what the measure
 * is meant to address, what proof it should produce, what it tends to cost and
 * where it came from. The cost and uncertainty here are the catalogue's
 * suggestion; a link records what a specific application actually cost.
 *
 * @property int $id
 * @property string $description
 * @property SaeriCategory $saeri_category
 * @property string $suggested_target_risk
 * @property string $expected_evidence
 * @property CostLevel $suggested_cost
 * @property UncertaintyLevel $uncertainty_level
 * @property string $bibliography_source
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Link> $links
 * @property-read int|null $links_count
 */
#[Fillable([
    'description',
    'saeri_category',
    'suggested_target_risk',
    'expected_evidence',
    'suggested_cost',
    'uncertainty_level',
    'bibliography_source',
])]
class Mitigation extends Model
{
    /** @use HasFactory<MitigationFactory> */
    use HasFactory;

    /**
     * The links applying this mitigation to a risk.
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
            'saeri_category' => SaeriCategory::class,
            'suggested_cost' => CostLevel::class,
            'uncertainty_level' => UncertaintyLevel::class,
        ];
    }
}
