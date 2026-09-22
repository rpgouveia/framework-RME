<?php

namespace App\Models;

use App\Enums\LinkStatus;
use Carbon\CarbonImmutable;
use Database\Factories\StatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An audit entry recording a status change on a link.
 *
 * The first entry of a trail has no previous status, and an entry may name the
 * adverse event that forced the change.
 *
 * @property int $id
 * @property LinkStatus|null $previous_status
 * @property LinkStatus $new_status
 * @property string|null $trigger_reason
 * @property CarbonImmutable $change_date
 * @property int $link_id
 * @property int $owner_id
 * @property int|null $adverse_event_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Link $link
 * @property-read Owner $owner
 * @property-read AdverseEvent|null $adverseEvent
 */
#[Fillable([
    'previous_status',
    'new_status',
    'trigger_reason',
    'change_date',
    'link_id',
    'owner_id',
    'adverse_event_id',
])]
class StatusHistory extends Model
{
    /** @use HasFactory<StatusHistoryFactory> */
    use HasFactory;

    /**
     * The link whose status changed.
     *
     * @return BelongsTo<Link, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * The role that recorded the change.
     *
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * The adverse event that triggered the change, when there was one.
     *
     * @return BelongsTo<AdverseEvent, $this>
     */
    public function adverseEvent(): BelongsTo
    {
        return $this->belongsTo(AdverseEvent::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_status' => LinkStatus::class,
            'new_status' => LinkStatus::class,
            'change_date' => 'date',
        ];
    }
}
