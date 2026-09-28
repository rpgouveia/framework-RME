<?php

namespace App\Actions;

use App\Enums\LinkStatus;
use App\Models\Link;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Create a link between a risk and a mitigation, as UC005 describes it.
 *
 * The link and the opening entry of its status trail are written together:
 * creating a link always records its first status (UC007, RF09), so neither
 * row may exist without the other. The next review date is computed here, not
 * taken from the user (R-7).
 */
class CreateLink
{
    public function __construct(
        protected RecordStatusChange $recordStatusChange,
    ) {}

    /**
     * The link is born planned and dated today, whatever the attributes say:
     * the review date is computed from the creation date (R-7), so a date
     * typed by the user would let them move the review.
     *
     * @param  array<string, mixed>  $attributes  The validated link fields.
     */
    public function handle(array $attributes): Link
    {
        return DB::transaction(function () use ($attributes): Link {
            $creationDate = today();

            $link = Link::create([
                ...$attributes,
                'status' => LinkStatus::Planned,
                'creation_date' => $creationDate,
                'next_review_date' => $creationDate->addDays(Config::integer('rme.review.interval_days')),
            ]);

            $this->recordStatusChange->open($link);

            return $link;
        });
    }
}
