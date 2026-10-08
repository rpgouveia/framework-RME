<?php

use App\Enums\AiSystemCategory;
use App\Enums\ChangeOrigin;
use App\Enums\LinkStatus;
use App\Enums\VerificationStatus;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

/**
 * A verified link of an operable system, due on the given day.
 */
function verifiedLinkDueOn(string|DateTimeInterface $date, AiSystemCategory $tier = AiSystemCategory::High): Link
{
    return Link::factory()->implemented()->verified()
        ->for(Risk::factory()->for(AiSystem::factory()->state(['category' => $tier])))
        ->create(['next_review_date' => $date]);
}

// The review date is the last valid day (0019, item 1).

test('a link is due for review from the day after its review date', function () {
    $this->freezeTime();

    $overdue = verifiedLinkDueOn(today()->subDay());
    $dueToday = verifiedLinkDueOn(today());
    $upcoming = verifiedLinkDueOn(today()->addDay());

    expect(Link::dueForReview()->pluck('id')->all())->toBe([$overdue->id])
        ->and(Link::monitorable()->pluck('id')->sort()->values()->all())
        ->toBe([$overdue->id, $dueToday->id, $upcoming->id]);
});

test('a cancelled link is left out of the review queue', function () {
    $this->freezeTime();

    $cancelled = Link::factory()->dueForReview()->create(['status' => LinkStatus::Cancelled]);
    $suspended = Link::factory()->dueForReview()->create(['status' => LinkStatus::Suspended]);

    $due = Link::dueForReview()->pluck('id');

    expect($due)->toContain($suspended->id)
        ->not->toContain($cancelled->id);
});

test('a link with no review date is never due', function () {
    $this->freezeTime();

    $undated = Link::factory()->implemented()->verified()
        ->for(Risk::factory()->for(AiSystem::factory()->unacceptable()))
        ->create();
    $overdue = verifiedLinkDueOn(today()->subDay());

    expect($undated->next_review_date)->toBeNull()
        ->and(Link::dueForReview()->pluck('id')->all())->toBe([$overdue->id]);
});

test('a monitorable link is verified, not cancelled, has a date and belongs to an operable system', function () {
    $operable = Risk::factory()->for(AiSystem::factory()->state(['category' => AiSystemCategory::Minimal]))->create();

    $monitorable = Link::factory()->verified()->for($operable)->create(['status' => LinkStatus::Planned, 'next_review_date' => today()->addYear()]);
    Link::factory()->verified()->for($operable)->create(['status' => LinkStatus::Cancelled]);
    Link::factory()->verified()->for($operable)->create(['status' => LinkStatus::Planned, 'next_review_date' => null]);
    // Declared: the review clock has not started (0018).
    Link::factory()->for($operable)->create(['status' => LinkStatus::Planned, 'next_review_date' => today()->addYear()]);
    Link::factory()->verified()->for(Risk::factory()->for(AiSystem::factory()->unacceptable()))->create([
        'status' => LinkStatus::Planned,
        'next_review_date' => today()->addYear(),
    ]);

    expect(Link::monitorable()->pluck('id')->all())->toBe([$monitorable->id]);
});

// The daily command reverts (0019, item 1).

test('the command reverts the links past their review date, not those due today', function () {
    $this->travelTo('2026-05-10 07:00');

    $overdue = verifiedLinkDueOn('2026-05-09');
    $dueToday = verifiedLinkDueOn('2026-05-10');

    $this->artisan('links:flag-due-for-review')
        ->assertSuccessful()
        ->expectsOutputToContain('2026-05-09');

    $entry = $overdue->lastReversal()->first();

    expect($overdue->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($overdue->next_review_date)->toBeNull()
        ->and($entry->origin)->toBe(ChangeOrigin::ReviewDue)
        ->and($entry->owner_id)->toBeNull()
        ->and($entry->trigger_reason)->toBe('A revisão venceu em 09/05/2026.')
        ->and($entry->change_date->toDateString())->toBe('2026-05-10')
        ->and($dueToday->refresh()->verification_status)->toBe(VerificationStatus::Verified);

    // The next day, the link due yesterday is reverted too.
    $this->travelTo('2026-05-11 07:00');
    $this->artisan('links:flag-due-for-review')->assertSuccessful();

    expect($dueToday->refresh()->verification_status)->toBe(VerificationStatus::Declared);
});

test('running the command twice on the same day reverts nothing more', function () {
    $this->travelTo('2026-05-10 07:00');
    $overdue = verifiedLinkDueOn('2026-05-01');

    $this->artisan('links:flag-due-for-review')->assertSuccessful();
    $this->artisan('links:flag-due-for-review')
        ->assertSuccessful()
        ->expectsOutput('No link is past its review date.');

    expect($overdue->statusHistories()->where('origin', ChangeOrigin::ReviewDue)->count())->toBe(1);
});

test('the command leaves declared, cancelled and unacceptable links alone', function () {
    $this->travelTo('2026-05-10 07:00');

    $declared = Link::factory()->create(['status' => LinkStatus::Planned, 'next_review_date' => '2026-05-01']);
    $cancelled = Link::factory()->verified()->create(['status' => LinkStatus::Cancelled, 'next_review_date' => '2026-05-01']);
    $prohibited = Link::factory()->verified()
        ->for(Risk::factory()->for(AiSystem::factory()->unacceptable()))
        ->create(['status' => LinkStatus::Planned, 'next_review_date' => '2026-05-01']);

    $this->artisan('links:flag-due-for-review')
        ->assertSuccessful()
        ->expectsOutput('No link is past its review date.');

    expect($declared->statusHistories()->count())->toBe(0)
        ->and($cancelled->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($prohibited->refresh()->verification_status)->toBe(VerificationStatus::Verified);
});

test('the command logs how many links it reverted', function () {
    $this->freezeTime();

    Link::factory(2)->dueForReview()->implemented()->create();

    Log::spy();

    $this->artisan('links:flag-due-for-review')->assertSuccessful();

    Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context): bool => $context['count'] === 2)->once();
});

test('the scope and the dashboard leave out a link whose system becomes unacceptable, and take it back', function () {
    $this->freezeTime();
    $this->actingAs(User::factory()->create());

    $aiSystem = AiSystem::factory()->highRisk()->create();
    // A tier change outside UpdateAiSystem reverts nothing, so the link keeps
    // its verification and date, as an older reclassification would have.
    $link = Link::factory()->implemented()->verified()
        ->for(Risk::factory()->for($aiSystem))
        ->create(['next_review_date' => today()->addDays(3)]);

    $upcoming = fn (): bool => collect($this->get(route('dashboard'))->viewData('page')['props']['reviews']['upcoming'])
        ->pluck('id')->contains($link->id);

    expect(Link::monitorable()->pluck('id')->all())->toBe([$link->id])
        ->and($upcoming())->toBeTrue();

    $aiSystem->update(['category' => AiSystemCategory::Unacceptable]);

    expect(Link::monitorable()->exists())->toBeFalse()
        ->and($upcoming())->toBeFalse();

    $aiSystem->update(['category' => AiSystemCategory::Limited]);

    expect(Link::monitorable()->pluck('id')->all())->toBe([$link->id])
        ->and($upcoming())->toBeTrue();
});

test('the command is scheduled to run every day', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'links:flag-due-for-review'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 7 * * *');
});
