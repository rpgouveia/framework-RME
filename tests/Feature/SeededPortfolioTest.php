<?php

use App\Enums\AiSystemCategory;
use App\Enums\ChangeOrigin;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\VerificationStatus;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Reassessment;
use App\Models\Risk;
use App\Models\StatusHistory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;

/*
 * The seeded portfolio shows up in the demonstration video, the tutorial and
 * the reports exported from it, so it has to stand up to whoever reads it.
 * The seeders draw, so each invariant is checked on several seedings.
 */

/**
 * Whether the first record was stored before the second one: by the moment
 * stored, then by the order of the ids for records stored in the same second.
 */
function storedBefore(Model $first, Model $second): bool
{
    $a = $first->getAttribute('created_at');
    $b = $second->getAttribute('created_at');

    return $a->lt($b) || ($a->eq($b) && $first->getKey() < $second->getKey() && $first::class === $second::class);
}

/**
 * Whether a reassessment changed the link's progress: closing and replacing
 * cancel it, and an adjustment may move it (0020, item 3).
 */
function progressChangedByReassessment(Link $link): bool
{
    return $link->reassessments->contains(
        fn (Reassessment $reassessment): bool => in_array($reassessment->outcome, [ReassessmentOutcome::Close, ReassessmentOutcome::Replace], true)
            || collect($reassessment->changes ?? [])->contains('field', 'status'),
    );
}

test('the seeded portfolio is coherent', function () {
    $this->seed(DatabaseSeeder::class);

    $links = Link::query()
        ->with(['risk.aiSystem', 'mitigation.targetRiskSubdomains', 'statusHistories', 'evidence', 'reassessments'])
        ->get();

    expect($links)->not->toBeEmpty();

    // The systems reclassified into the unacceptable tier after their links
    // were verified: they operated before.
    $reclassified = StatusHistory::query()->where('origin', ChangeOrigin::SystemReclassification)->with('link.risk')->get()
        ->map(fn (StatusHistory $reversal): int => $reversal->link->risk->ai_system_id)
        ->unique();

    foreach ($links as $link) {
        $label = "link #{$link->id} ({$link->risk->name} -> {$link->mitigation->name})";
        $progress = $link->statusHistories->whereNotNull('new_status')->sortBy([['created_at', 'asc'], ['id', 'asc']])->values();
        $verifications = $link->statusHistories->where('new_verification', VerificationStatus::Verified);
        $firstEvidence = $link->evidence->sortBy([['created_at', 'asc'], ['id', 'asc']])->first();

        // The mitigation is one recommended for the risk, as on the link
        // creation screen (0012).
        expect($link->mitigation->targetRiskSubdomains->contains('id', $link->risk->risk_subdomain_id))->toBeTrue("{$label}: not recommended for the risk.");

        // Every link is born planned.
        expect($progress->first()?->new_status)->toBe(LinkStatus::Planned, "{$label}: not born planned.");

        // A link verified now, or ever, is implemented, unless a
        // reassessment moved it after its reversal.
        if ($verifications->isNotEmpty() && ! progressChangedByReassessment($link)) {
            expect($link->status)->toBe(LinkStatus::Implemented, "{$label}: verified and {$link->status->value}.");
        }

        // Each verification comes after the link was implemented and after
        // its first evidence, with the progress at that moment implemented.
        foreach ($verifications as $verification) {
            $before = $progress->filter(fn (StatusHistory $change): bool => storedBefore($change, $verification));

            expect($before->last()?->new_status)->toBe(LinkStatus::Implemented, "{$label}: verified while {$before->last()?->new_status?->value}.")
                ->and($firstEvidence)->not->toBeNull("{$label}: verified with no evidence.")
                ->and($firstEvidence->created_at->lte($verification->created_at))->toBeTrue("{$label}: verified before its first evidence.");
        }

        // Evidence only comes once the link is implemented.
        if ($firstEvidence !== null) {
            $implemented = $progress->firstWhere('new_status', LinkStatus::Implemented);

            expect($implemented)->not->toBeNull("{$label}: evidence of a link never implemented.")
                ->and($implemented->created_at->lte($firstEvidence->created_at))->toBeTrue("{$label}: evidence before the implementation.");
        }

        // A progress change quotes only an adverse event of the system known
        // between the link's creation and the change.
        foreach ($progress->whereNotNull('adverse_event_id') as $change) {
            $event = $change->adverseEvent;
            $known = ($event->detected_at ?? $event->occurrence_date)->toDateString();

            expect($event->ai_system_id)->toBe($link->risk->ai_system_id, "{$label}: event of another system.")
                ->and($known >= $link->creation_date->toDateString())->toBeTrue("{$label}: event known before the link existed.")
                ->and($known <= $change->change_date->toDateString())->toBeTrue("{$label}: event known after the change.");
        }

        // No continuous validation, which only systems that learn
        // continuously go through, and no retirement, except the link of
        // the system reclassified into the unacceptable tier, adjusted to
        // plan the discontinuation.
        expect($link->lifecycle_phase)->not->toBe(LifecyclePhase::ContinuousValidation, "{$label}: continuous validation.");

        if ($link->lifecycle_phase === LifecyclePhase::Retirement) {
            $discontinued = $link->risk->aiSystem->category === AiSystemCategory::Unacceptable
                && $link->reassessments->contains(fn (Reassessment $reassessment): bool => collect($reassessment->changes ?? [])
                    ->contains(fn (array $change): bool => $change['field'] === 'lifecycle_phase' && $change['after'] === LifecyclePhase::Retirement->value));

            expect($discontinued)->toBeTrue("{$label}: retirement.");
        }

        // A system in the unacceptable tier may have planned links and never
        // operates (glossary, section 1): its links stay planned and
        // declared, with no evidence, unless it was reclassified after it
        // operated.
        if ($link->risk->aiSystem->category === AiSystemCategory::Unacceptable && ! $reclassified->contains($link->risk->ai_system_id)) {
            expect($link->status)->toBe(LinkStatus::Planned, "{$label}: {$link->status->value} in a system that never operates.")
                ->and($progress)->toHaveCount(1, "{$label}: progress moved in a system that never operates.")
                ->and($link->statusHistories->whereNotNull('new_verification'))->toBeEmpty("{$label}: verified in a system that never operates.")
                ->and($link->evidence)->toBeEmpty("{$label}: evidence in a system that never operates.");
        }

        // An adjustment that moves the progress after an incident redoes the
        // mitigation: the link goes back in progress.
        foreach ($link->reassessments as $reassessment) {
            foreach (collect($reassessment->changes ?? [])->where('field', 'status') as $change) {
                expect($change['after'])->toBe(LinkStatus::InProgress->value, "{$label}: adjusted to {$change['after']}.");
            }
        }

        // No two pieces of evidence of the link read the same.
        expect($link->evidence->pluck('description')->duplicates())->toBeEmpty("{$label}: repeated evidence.");
    }

    expect(Risk::query()->whereIn('lifecycle_phase', [LifecyclePhase::ContinuousValidation, LifecyclePhase::Retirement])->exists())->toBeFalse();

    // The eight verification stories of the LinkSeeder still show up.
    $verifiedOnce = fn (Link $link): bool => $link->statusHistories->where('new_verification', VerificationStatus::Verified)->count() === 1;
    $lastVerificationChange = fn (Link $link): ?StatusHistory => $link->statusHistories->whereNotNull('new_verification')
        ->sortBy([['created_at', 'asc'], ['id', 'asc']])->last();
    $manuallyReverted = fn (Link $link): bool => $link->verification_status === VerificationStatus::Declared
        && $lastVerificationChange($link)?->origin === ChangeOrigin::Manual
        && $lastVerificationChange($link)->new_verification === VerificationStatus::Declared;
    $evidenceSince = fn (Link $link): bool => $link->evidence->contains(
        fn (Evidence $evidence): bool => $evidence->created_at->gt($lastVerificationChange($link)->created_at),
    );
    $neverVerified = fn (Link $link): bool => $link->statusHistories->whereNotNull('new_verification')->isEmpty();

    $stories = [
        'declared' => fn (Link $link): bool => $neverVerified($link) && $link->evidence->isEmpty(),
        'ready' => fn (Link $link): bool => $neverVerified($link) && $link->evidence->isNotEmpty(),
        'verified' => fn (Link $link): bool => $verifiedOnce($link) && $link->verification_status === VerificationStatus::Verified
            && $link->next_review_date->gt(today()),
        'due_today' => fn (Link $link): bool => $link->verification_status === VerificationStatus::Verified
            && $link->next_review_date->isSameDay(today()),
        'overdue' => fn (Link $link): bool => $link->statusHistories->contains('origin', ChangeOrigin::ReviewDue),
        'renewed' => fn (Link $link): bool => $link->statusHistories->contains(
            fn (StatusHistory $change): bool => $change->previous_verification === VerificationStatus::Verified
                && $change->new_verification === VerificationStatus::Verified,
        ),
        'reverted_stale' => fn (Link $link): bool => $manuallyReverted($link) && ! $evidenceSince($link),
        'reverted_ready' => fn (Link $link): bool => $manuallyReverted($link) && $evidenceSince($link),
    ];

    foreach ($stories as $story => $tells) {
        expect($links->contains($tells))->toBeTrue("No link tells the {$story} story.");
    }
})->repeat(5);
