<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reference taxonomy (RNF05), loaded from a versioned file and read only in
 * the app.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $citation
 * @property string $version
 * @property string $url
 * @property CarbonImmutable $accessed_at
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, TaxonomyTerm> $terms
 */
#[Fillable(['key', 'name', 'citation', 'version', 'url', 'accessed_at', 'metadata'])]
class Taxonomy extends Model
{
    /**
     * @return HasMany<TaxonomyTerm, $this>
     */
    public function terms(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessed_at' => 'date',
            'metadata' => 'array',
        ];
    }
}
