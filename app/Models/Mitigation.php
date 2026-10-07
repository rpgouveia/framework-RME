<?php

namespace App\Models;

use App\Enums\CostLevel;
use App\Enums\UncertaintyLevel;
use Carbon\CarbonImmutable;
use Database\Factories\MitigationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A mitigation measure that can be applied to one or more risks.
 *
 * The catalogue entry (C2) is written once and reused. It is traceable to
 * Saeri et al. (2025): a subcategory of their taxonomy, the literal name and
 * identifier in their database, and the document it came from. The target
 * risk, evidence, cost and uncertainty are the framework's own contribution,
 * backed by the estimate source (RNF03); a link records what a specific
 * application actually cost.
 *
 * @property int $id
 * @property string $name
 * @property string $source_name
 * @property string $source_reference
 * @property string $source_document
 * @property int $saeri_subcategory_id
 * @property string $description
 * @property string $suggested_target_risk
 * @property string $expected_evidence
 * @property CostLevel $suggested_cost
 * @property UncertaintyLevel $uncertainty_level
 * @property string $estimate_source
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read TaxonomyTerm $saeriSubcategory
 * @property-read Collection<int, TaxonomyTerm> $targetRiskSubdomains
 * @property-read Collection<int, Link> $links
 * @property-read int|null $links_count
 */
#[Fillable([
    'name',
    'source_name',
    'source_reference',
    'source_document',
    'saeri_subcategory_id',
    'description',
    'suggested_target_risk',
    'expected_evidence',
    'suggested_cost',
    'uncertainty_level',
    'estimate_source',
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
     * The Saeri subcategory (level 2); its parent is the category.
     *
     * @return BelongsTo<TaxonomyTerm, $this>
     */
    public function saeriSubcategory(): BelongsTo
    {
        return $this->belongsTo(TaxonomyTerm::class, 'saeri_subcategory_id');
    }

    /**
     * The risk subdomains (MIT AI Risk Repository) the mitigation treats.
     * Every catalogue entry names at least one: it is what lets a risk be
     * matched with the mitigations meant for it.
     *
     * @return BelongsToMany<TaxonomyTerm, $this>
     */
    public function targetRiskSubdomains(): BelongsToMany
    {
        return $this->belongsToMany(TaxonomyTerm::class, 'mitigation_target_subdomains', 'mitigation_id', 'risk_subdomain_id')
            ->orderBy('position');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suggested_cost' => CostLevel::class,
            'uncertainty_level' => UncertaintyLevel::class,
        ];
    }
}
