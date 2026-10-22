<?php

namespace Database\Seeders;

use App\Actions\RecordAdverseEvent;
use App\Enums\AdverseEventNature;
use App\Models\AiSystem;
use App\Models\Risk;
use Carbon\CarbonImmutable;
use Database\Factories\AdverseEventFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Past adverse events, from before any link of the seeded portfolio, so they
 * revert nothing. They fall in subdomains of the system's own risks: the one
 * risk not yet mapped is told by the ReassessmentSeeder. They are recorded through RecordAdverseEvent with the
 * clock moved back to when each was registered. The events that trigger
 * reassessments are told by the ReassessmentSeeder, after the links.
 */
class AdverseEventSeeder extends Seeder
{
    public function run(RecordAdverseEvent $recordAdverseEvent): void
    {
        $aiSystems = AiSystem::all();

        if ($aiSystems->isEmpty()) {
            $aiSystems = AiSystem::factory(3)->create();
        }

        $today = CarbonImmutable::today();

        try {
            // Not every system misbehaves, so only some of them get events.
            foreach ($aiSystems->random(max(1, (int) ceil($aiSystems->count() / 2))) as $aiSystem) {
                $codes = Risk::query()->where('ai_system_id', $aiSystem->id)->with('riskSubdomain')->get()
                    ->map(fn (Risk $risk): string => $risk->riskSubdomain->code)->unique();

                if ($codes->isEmpty()) {
                    continue;
                }

                // One occurrence per subdomain, so no system tells the same
                // story twice.
                foreach ($codes->shuffle()->take(fake()->numberBetween(1, 2)) as $subdomain) {
                    $occurred = $today->subDays(fake()->numberBetween(600, 700));
                    $detected = fake()->boolean() ? $occurred->addDays(fake()->numberBetween(0, 10)) : null;

                    // Registered on detection, or a few days after it.
                    Date::setTestNow(($detected ?? $occurred)->addDays(fake()->numberBetween(0, 3))->setTime(10, 0));

                    $nature = fake()->randomElement(AdverseEventNature::cases());

                    $recordAdverseEvent->handle([
                        'ai_system_id' => $aiSystem->id,
                        'nature' => $nature,
                        'description' => AdverseEventFactory::describe($subdomain, $nature),
                        'occurrence_date' => $occurred,
                        'detected_at' => $detected,
                        'risk_subdomains' => [$subdomain],
                    ]);
                }
            }
        } finally {
            Date::setTestNow();
        }
    }
}
