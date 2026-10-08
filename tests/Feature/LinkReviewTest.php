<?php

use App\Enums\AiSystemCategory;
use App\Enums\LinkStatus;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

test('the scope returns the links whose review date has arrived', function () {
    $this->freezeTime();

    // Pin the status: the factory may pick Cancelled, which the scope skips.
    $overdue = Link::factory()->implemented()->create(['next_review_date' => today()->subDay()]);
    $dueToday = Link::factory()->implemented()->create(['next_review_date' => today()]);
    $upcoming = Link::factory()->implemented()->create(['next_review_date' => today()->addDay()]);

    $due = Link::dueForReview()->pluck('id');

    expect($due)->toContain($overdue->id, $dueToday->id)
        ->not->toContain($upcoming->id);
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
    // Its system is in the unacceptable tier: it never operates.
    $this->freezeTime();

    $undated = Link::factory()->implemented()
        ->for(Risk::factory()->for(AiSystem::factory()->unacceptable()))
        ->create();
    $due = Link::factory()->implemented()->create(['next_review_date' => today()]);

    expect($undated->next_review_date)->toBeNull()
        ->and(Link::dueForReview()->pluck('id')->all())->toBe([$due->id]);

    $this->artisan('links:flag-due-for-review')
        ->assertSuccessful()
        ->expectsOutputToContain((string) $due->id);
});

test('the scope, the command and the dashboard drop a link whose system becomes unacceptable, and take it back', function () {
    $this->freezeTime();
    $this->actingAs(User::factory()->create());

    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = Link::factory()->implemented()
        ->for(Risk::factory()->for($aiSystem))
        ->create(['next_review_date' => today()->subWeek()]);

    $seen = function () use ($link): array {
        $dashboard = $this->get(route('dashboard'))->viewData('page')['props'];

        return [
            'scope' => Link::dueForReview()->pluck('id')->contains($link->id),
            'monitorable' => Link::monitorable()->pluck('id')->contains($link->id),
            'dashboard' => collect($dashboard['reviews']['due'])->pluck('id')->contains($link->id),
            'dashboard count' => $dashboard['reviews']['dueCount'],
            'system count' => collect($dashboard['systems'])->firstWhere('id', $link->risk->ai_system_id)['due_reviews_count'],
        ];
    };

    expect($seen())->toBe(['scope' => true, 'monitorable' => true, 'dashboard' => true, 'dashboard count' => 1, 'system count' => 1]);
    $this->artisan('links:flag-due-for-review')->expectsOutputToContain(today()->subWeek()->toDateString());

    // Reclassified as unacceptable: no review is owed, but the date stays.
    $aiSystem->update(['category' => AiSystemCategory::Unacceptable]);

    expect($seen())->toBe(['scope' => false, 'monitorable' => false, 'dashboard' => false, 'dashboard count' => 0, 'system count' => 0])
        ->and($link->refresh()->next_review_date->toDateString())->toBe(today()->subWeek()->toDateString());
    $this->artisan('links:flag-due-for-review')->expectsOutput('No link is waiting for a review.');

    // Back to an operable tier: the old date is due again.
    $aiSystem->update(['category' => AiSystemCategory::Limited]);

    expect($seen())->toBe(['scope' => true, 'monitorable' => true, 'dashboard' => true, 'dashboard count' => 1, 'system count' => 1]);
    $this->artisan('links:flag-due-for-review')->expectsOutputToContain(today()->subWeek()->toDateString());
});

test('a monitorable link is not cancelled, has a date and belongs to an operable system', function () {
    $operable = Risk::factory()->for(AiSystem::factory()->state(['category' => AiSystemCategory::Minimal]))->create();

    $monitorable = Link::factory()->for($operable)->create(['status' => LinkStatus::Planned, 'next_review_date' => today()->addYear()]);
    Link::factory()->for($operable)->create(['status' => LinkStatus::Cancelled]);
    Link::factory()->for($operable)->create(['status' => LinkStatus::Planned, 'next_review_date' => null]);
    Link::factory()->for(Risk::factory()->for(AiSystem::factory()->unacceptable()))->create([
        'status' => LinkStatus::Planned,
        'next_review_date' => today()->addYear(),
    ]);

    expect(Link::monitorable()->pluck('id')->all())->toBe([$monitorable->id]);
});

test('the command lists the links waiting for a review', function () {
    $this->freezeTime();

    $due = Link::factory()->dueForReview()->create(['status' => LinkStatus::Implemented]);
    $upcoming = Link::factory()->create(['next_review_date' => today()->addMonth()]);

    $this->artisan('links:flag-due-for-review')
        ->assertSuccessful()
        ->expectsOutputToContain((string) $due->id);

    expect($due->refresh()->status)->toBe(LinkStatus::Implemented)
        ->and($upcoming->refresh()->next_review_date->toDateString())
        ->toBe(today()->addMonth()->toDateString());

    $this->assertDatabaseCount('status_histories', 0);
});

test('the command says when nothing is waiting for a review', function () {
    $this->freezeTime();

    Link::factory()->create(['next_review_date' => today()->addMonth()]);

    Log::spy();

    $this->artisan('links:flag-due-for-review')->assertSuccessful();

    Log::shouldNotHaveReceived('warning');
});

test('the command logs a warning with the count of overdue links', function () {
    $this->freezeTime();

    Link::factory(2)->dueForReview()->implemented()->create();

    Log::spy();

    $this->artisan('links:flag-due-for-review')->assertSuccessful();

    Log::shouldHaveReceived('warning')->once();
});

test('the command is scheduled to run every day', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'links:flag-due-for-review'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 7 * * *');
});
