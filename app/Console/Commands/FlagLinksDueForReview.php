<?php

namespace App\Console\Commands;

use App\Models\Link;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FlagLinksDueForReview extends Command
{
    protected $signature = 'links:flag-due-for-review';

    protected $description = 'List the mitigation links whose review date has arrived';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $links = Link::dueForReview()
            ->with(['risk', 'mitigation', 'owner'])
            ->orderBy('next_review_date')
            ->get();

        if ($links->isEmpty()) {
            $this->info('No link is waiting for a review.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Review date', 'Risk', 'Mitigation', 'Owner'],
            $links->map(fn (Link $link): array => [
                $link->id,
                $link->next_review_date->toDateString(),
                $link->risk->description,
                $link->mitigation->description,
                $link->owner->organizational_role,
            ]),
        );

        Log::warning('Mitigation links are waiting for a review.', [
            'count' => $links->count(),
            'links' => $links->pluck('id')->all(),
        ]);

        return self::SUCCESS;
    }
}
