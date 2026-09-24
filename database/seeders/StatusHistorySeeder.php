<?php

namespace Database\Seeders;

use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Owner;
use App\Models\StatusHistory;
use Illuminate\Database\Seeder;

class StatusHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $links = Link::all();

        if ($links->isEmpty()) {
            $links = Link::factory(3)->create();
        }

        $owners = Owner::all();

        if ($owners->isEmpty()) {
            $owners = Owner::factory(3)->create();
        }

        $adverseEvents = AdverseEvent::all();

        $links->each(function (Link $link) use ($owners, $adverseEvents): void {
            /*
             * A trail always opens with an entry that has no previous status,
             * then carries the changes that followed.
             */
            StatusHistory::factory()
                ->opening()
                ->for($link)
                ->recycle($owners)
                ->create();

            StatusHistory::factory(fake()->numberBetween(0, 2))
                ->for($link)
                ->recycle($owners)
                ->create([
                    /*
                     * Roughly a third of the changes are forced by something
                     * that actually went wrong, the rest are routine.
                     */
                    'adverse_event_id' => $adverseEvents->isNotEmpty() && fake()->boolean(33)
                        ? $adverseEvents->random()->id
                        : null,
                ]);
        });
    }
}
