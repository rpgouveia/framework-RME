<?php

namespace App\Console\Commands;

use App\Actions\RevertOverdueLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * The review-due trigger (0019, item 1), run daily by the scheduler: every
 * monitorable link past its review date goes back to declared, awaiting
 * reassessment. A link due today is still valid. Running it again on the
 * same day reverts nothing more.
 */
class FlagLinksDueForReview extends Command
{
    protected $signature = 'links:flag-due-for-review';

    protected $description = 'Revert the verification of the links past their review date';

    /**
     * Execute the console command.
     */
    public function handle(RevertOverdueLinks $revertOverdueLinks): int
    {
        $reverted = $revertOverdueLinks->handle();

        if ($reverted === []) {
            $this->info('No link is past its review date.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Review date', 'Risk', 'Mitigation'],
            array_map(fn (array $row): array => [
                $row['link']->id,
                $row['due']->toDateString(),
                $row['link']->risk->name,
                $row['link']->mitigation->name,
            ], $reverted),
        );

        Log::info('Links past their review date were reverted to declared.', [
            'count' => count($reverted),
            'links' => array_map(fn (array $row): int => $row['link']->id, $reverted),
        ]);

        return self::SUCCESS;
    }
}
