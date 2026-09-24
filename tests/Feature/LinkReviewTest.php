<?php

use App\Enums\LinkStatus;
use App\Models\Link;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

test('the scope returns the links whose review date has arrived', function () {
    $this->freezeTime();

    $overdue = Link::factory()->create(['next_review_date' => today()->subDay()]);
    $dueToday = Link::factory()->create(['next_review_date' => today()]);
    $upcoming = Link::factory()->create(['next_review_date' => today()->addDay()]);

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

    Link::factory(2)->dueForReview()->create();

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
