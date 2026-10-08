<?php

namespace App\Actions;

use App\Enums\LinkStatus;
use App\Enums\VerificationStatus;
use App\Models\Link;
use Illuminate\Support\Facades\DB;

/**
 * Create a link between a risk and a mitigation, as UC005 describes it.
 *
 * The link and the opening entry of its status trail are written together:
 * creating a link always records its first status (UC007, RF09), so neither
 * row may exist without the other. The link is born declared and with no
 * review date: the periodic review starts at its first verification (R-7 as
 * revised by 0018).
 */
class CreateLink
{
    public function __construct(
        protected RecordStatusChange $recordStatusChange,
    ) {}

    /**
     * The link is born planned, declared and dated today, whatever the
     * attributes say.
     *
     * @param  array<string, mixed>  $attributes  The validated link fields.
     */
    public function handle(array $attributes): Link
    {
        return DB::transaction(function () use ($attributes): Link {
            $link = Link::create([
                ...$attributes,
                'status' => LinkStatus::Planned,
                'verification_status' => VerificationStatus::Declared,
                'creation_date' => today(),
                'next_review_date' => null,
            ]);

            $this->recordStatusChange->open($link);

            return $link;
        });
    }
}
