<?php

namespace App\Models;

use App\Enums\EvidenceType;
use Carbon\CarbonImmutable;
use Database\Factories\EvidenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An artifact proving a mitigation link is in place.
 *
 * @property int $id
 * @property EvidenceType $type
 * @property string $description
 * @property CarbonImmutable $registration_date
 * @property int $link_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Link $link
 */
#[Table('evidence')]
#[Fillable(['type', 'description', 'registration_date', 'link_id'])]
class Evidence extends Model
{
    /** @use HasFactory<EvidenceFactory> */
    use HasFactory;

    /**
     * The link this evidence belongs to.
     *
     * @return BelongsTo<Link, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EvidenceType::class,
            'registration_date' => 'date',
        ];
    }
}
