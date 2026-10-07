<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A node of a reference taxonomy, such as a Saeri category (level 1) or
 * subcategory (level 2).
 *
 * @property int $id
 * @property int $taxonomy_id
 * @property int|null $parent_id
 * @property string $code
 * @property int $level
 * @property string $name The Portuguese name shown in the app.
 * @property string $original_name The name in the source, verbatim.
 * @property string|null $description
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Taxonomy $taxonomy
 * @property-read TaxonomyTerm|null $parent
 * @property-read Collection<int, TaxonomyTerm> $children
 */
#[Fillable(['taxonomy_id', 'parent_id', 'code', 'level', 'name', 'original_name', 'description', 'position'])]
class TaxonomyTerm extends Model
{
    /**
     * @return BelongsTo<Taxonomy, $this>
     */
    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }

    /**
     * @return BelongsTo<TaxonomyTerm, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaxonomyTerm::class, 'parent_id');
    }

    /**
     * @return HasMany<TaxonomyTerm, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class, 'parent_id')->orderBy('position');
    }
}
