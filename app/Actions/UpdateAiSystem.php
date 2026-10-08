<?php

namespace App\Actions;

use App\Enums\AiSystemCategory;
use App\Enums\ChangeOrigin;
use App\Models\AiSystem;
use App\Models\Link;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Update an AI system. Reclassifying it into the unacceptable tier reverts
 * every verified link of the system in the same transaction (0019, item 9):
 * a system that may not operate keeps no verification. Moving between
 * operable tiers changes nothing in its links (0017).
 */
class UpdateAiSystem
{
    public function __construct(
        protected RecordStatusChange $recordStatusChange,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  The validated system fields.
     */
    public function handle(AiSystem $aiSystem, array $attributes): AiSystem
    {
        return DB::transaction(function () use ($aiSystem, $attributes): AiSystem {
            $aiSystem->update($attributes);

            if ($aiSystem->category === AiSystemCategory::Unacceptable) {
                $this->linksToRevert($aiSystem)->get()->each(fn (Link $link) => $this->recordStatusChange->revert(
                    $link,
                    ChangeOrigin::SystemReclassification,
                    reason: __('The system was reclassified into the unacceptable tier of the EU AI Act.'),
                ));
            }

            return $aiSystem;
        });
    }

    /**
     * The links a reclassification into the unacceptable tier reverts: the
     * verified ones still in the chain.
     *
     * @return Builder<Link>
     */
    public function linksToRevert(AiSystem $aiSystem): Builder
    {
        return Link::query()->verified()->ofSystem($aiSystem->id)->orderBy('id');
    }
}
