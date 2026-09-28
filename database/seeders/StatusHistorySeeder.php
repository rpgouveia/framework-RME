<?php

namespace Database\Seeders;

use App\Actions\RecordStatusChange;
use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Owner;
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
        $recordStatusChange = app(RecordStatusChange::class);

        /*
         * Write the trails the way the app does, so each one opens with the
         * status its link was born with and every later entry starts from
         * where the link actually was.
         */
        $links->each(function (Link $link) use ($owners, $adverseEvents, $recordStatusChange): void {
            $recordStatusChange->open($link);

            $date = $link->creation_date;

            for ($changes = fake()->numberBetween(0, 2); $changes > 0; $changes--) {
                $newStatus = LinkStatus::from(fake()->randomElement($link->status->transitionOptions())['value']);
                $date = $date->addDays(fake()->numberBetween(5, 60));

                $recordStatusChange->handle($link, $newStatus, [
                    'change_date' => $date,
                    'owner_id' => $owners->random()->id,
                    'trigger_reason' => $newStatus === LinkStatus::Cancelled || fake()->boolean()
                        ? fake()->sentence()
                        : null,
                    /*
                     * Roughly a third of the changes are forced by something
                     * that actually went wrong, the rest are routine.
                     */
                    'adverse_event_id' => $adverseEvents->isNotEmpty() && fake()->boolean(33)
                        ? $adverseEvents->random()->id
                        : null,
                ]);
            }
        });
    }
}
