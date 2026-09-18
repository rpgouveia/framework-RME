<?php

namespace App\Models;

use App\Enums\SaeriCategory;
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
 * @property int $id
 * @property string $description
 * @property SaeriCategory $saeri_category
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Link> $links
 * @property-read int|null $links_count
 */
#[Fillable(['description', 'saeri_category'])]
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
        ];
    }
}
