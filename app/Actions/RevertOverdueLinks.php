<?php

namespace App\Actions;

use App\Enums\ChangeOrigin;
use App\Models\Link;
use Carbon\CarbonImmutable;

/**
 * The review-due trigger (0019, item 1): a monitorable link whose review date
 * has passed goes back to declared. The review date is the last valid day,
 * so a link due today is left alone until tomorrow. Running it twice on the
 * same day changes nothing more: a reverted link is no longer monitorable.
 */
class RevertOverdueLinks
{
    public function __construct(
        protected RecordStatusChange $recordStatusChange,
    ) {}

    /**
     * Revert every overdue link.
     *
     * @return list<array{link: Link, due: CarbonImmutable}> The links reverted,
     *                                                       with the date that passed.
     */
    public function handle(): array
    {
        $reverted = [];

        $links = Link::query()->dueForReview()->with(['risk', 'mitigation'])->orderBy('next_review_date')->orderBy('id')->get();

        foreach ($links as $link) {
            /** @var CarbonImmutable $due */
            $due = $link->next_review_date;
            $this->revert($link);
            $reverted[] = ['link' => $link, 'due' => $due];
        }

        return $reverted;
    }

    /**
     * Revert one overdue link, with no author and the date that passed.
     */
    public function revert(Link $link): void
    {
        $this->recordStatusChange->revert(
            $link,
            ChangeOrigin::ReviewDue,
            reason: __('The review was due on :date.', ['date' => $link->next_review_date?->format('d/m/Y')]),
        );
    }
}
