<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OwnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The organizational role accountable for a mitigation link.
 *
 * An owner is a role in an area, never a person (R-2). Once used in the
 * traceability chain it can no longer be edited or deleted, since the trail
 * points at it; a role that no longer exists is deactivated instead.
 *
 * @property int $id
 * @property string $organizational_role
 * @property string $area
 * @property CarbonImmutable|null $deactivated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Link> $links
 * @property-read int|null $links_count
 * @property-read Collection<int, StatusHistory> $statusHistories
 * @property-read int|null $status_histories_count
 */
#[Fillable(['organizational_role', 'area'])]
class Owner extends Model
{
    /** @use HasFactory<OwnerFactory> */
    use HasFactory;

    /**
     * The links this owner is accountable for.
     *
     * @return HasMany<Link, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * The status changes this owner recorded.
     *
     * @return HasMany<StatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    /**
     * Whether a link or a status history entry points at this owner.
     */
    public function isUsed(): bool
    {
        return $this->links()->exists() || $this->statusHistories()->exists();
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * Only the owners that can take on new links and status changes.
     *
     * @param  Builder<Owner>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
        ];
    }
}
