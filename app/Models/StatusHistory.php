<?php

namespace App\Models;

use App\Enums\ChangeOrigin;
use App\Enums\LinkStatus;
use App\Enums\VerificationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\StatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An audit entry recording a change on a link.
 *
 * Each entry changes exactly one dimension (0013): progress (the status) or
 * verification. The first entry of a trail has no previous status. An entry
 * may name the adverse event that forced the change, and says what triggered
 * it: a manual entry has an owner, an automatic one has none (0018).
 *
 * @property int $id
 * @property LinkStatus|null $previous_status
 * @property LinkStatus|null $new_status
 * @property VerificationStatus|null $previous_verification
 * @property VerificationStatus|null $new_verification
 * @property ChangeOrigin $origin
 * @property string|null $trigger_reason
 * @property CarbonImmutable $change_date
 * @property int $link_id
 * @property int|null $owner_id
 * @property int|null $adverse_event_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Link $link
 * @property-read Owner|null $owner
 * @property-read AdverseEvent|null $adverseEvent
 */
#[Fillable([
    'previous_status',
    'new_status',
    'previous_verification',
    'new_verification',
    'origin',
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'origin' => 'manual',
    ];

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
     * The role that recorded the change; none for an automatic one.
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
     * Whether the entry changed the verification rather than the progress.
     */
    public function isVerificationChange(): bool
    {
        return $this->new_verification !== null;
    }

    /**
     * The evidence a verification rested on: what counted when it was
     * recorded (0013), stored up to that moment and, after a reversal, only
     * what came after the reversal. Empty for any other entry.
     *
     * @return Collection<int, Evidence>
     */
    public function supportingEvidence(): Collection
    {
        if ($this->new_verification !== VerificationStatus::Verified) {
            return new Collection;
        }

        $reversal = self::query()
            ->where('link_id', $this->link_id)
            ->where('previous_verification', VerificationStatus::Verified)
            ->where('new_verification', VerificationStatus::Declared)
            ->where('created_at', '<=', $this->created_at)
            ->where('id', '<', $this->id)
            ->latest()
            ->latest('id')
            ->first();

        return Evidence::query()
            ->where('link_id', $this->link_id)
            ->where('created_at', '<=', $this->created_at)
            ->when($reversal, fn (Builder $query) => $query->where('created_at', '>', $reversal?->created_at))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
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
            'previous_verification' => VerificationStatus::class,
            'new_verification' => VerificationStatus::class,
            'origin' => ChangeOrigin::class,
            'change_date' => 'date',
        ];
    }
}
