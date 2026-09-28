<?php

namespace App\Actions;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\StatusHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only way a link's status trail is written (UC007, RF09).
 *
 * The trail is append only: entries are never edited or deleted, which is
 * what lets links be permanent. Every entry is written here, so the previous
 * status always comes from the link itself and never from a request.
 */
class RecordStatusChange
{
    /**
     * Open the trail of a link that was just created: the entry records the
     * status the link was born with and has no previous status.
     */
    public function open(Link $link): StatusHistory
    {
        return $link->statusHistories()->create([
            'previous_status' => null,
            'new_status' => $link->status,
            'change_date' => $link->creation_date,
            'owner_id' => $link->owner_id,
        ]);
    }

    /**
     * Move a link to a new status and record the change.
     *
     * @param  array<string, mixed>  $attributes  The change date, owner, and optional
     *                                            reason and adverse event.
     *
     * @throws InvalidArgumentException When the link cannot move to that status.
     */
    public function handle(Link $link, LinkStatus $newStatus, array $attributes): StatusHistory
    {
        if (! $link->status->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "A {$link->status->value} link cannot move to {$newStatus->value}.",
            );
        }

        return DB::transaction(function () use ($link, $newStatus, $attributes): StatusHistory {
            $statusHistory = $link->statusHistories()->create([
                ...$attributes,
                'previous_status' => $link->status,
                'new_status' => $newStatus,
            ]);

            $link->update(['status' => $newStatus]);

            return $statusHistory;
        });
    }
}
