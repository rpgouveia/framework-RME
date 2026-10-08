<?php

use App\Actions\RecordEvidence;
use App\Actions\RecordReassessment;
use App\Actions\RecordStatusChange;
use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Enums\CauseStatus;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\VerificationStatus;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Reassessment;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A verified link, reverted with the given origin and so awaiting its
 * reassessment.
 */
function revertedLink(ChangeOrigin $origin = ChangeOrigin::Manual, AiSystemCategory $tier = AiSystemCategory::High): Link
{
    $link = Link::factory()
        ->for(Risk::factory()->for(AiSystem::factory()->state(['category' => $tier]))->inSubdomain('2.1'))
        ->create(['status' => LinkStatus::InProgress]);
    $record = app(RecordStatusChange::class);

    reassessmentEvidence($link);
    $record->verify($link, Owner::factory()->create());

    test()->travel(1)->minutes();

    if ($origin === ChangeOrigin::Manual) {
        $record->revert($link, ChangeOrigin::Manual, Owner::factory()->create(), 'Controle desativado.');
    } else {
        $record->revert($link, $origin);
    }

    test()->travel(1)->minutes();

    return $link->refresh();
}

function reassessmentEvidence(Link $link): void
{
    app(RecordEvidence::class)->handle($link, ['type' => EvidenceType::Report, 'description' => 'Prova']);
}

/**
 * The fields of a reassessment through the form.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function reassessmentPayload(Link $link, array $overrides = []): array
{
    return array_merge([
        'outcome' => ReassessmentOutcome::Close->value,
        'owner_id' => $link->owner_id,
        'justification' => 'A mitigação não se justifica mais.',
        'cause_status' => CauseStatus::NotIdentified->value,
    ], $overrides);
}

/**
 * The first message a reassessment fails with.
 *
 * @param  array<string, mixed>  $data
 */
function reassessmentRefusal(Link $link, array $data): string
{
    try {
        app(RecordReassessment::class)->handle($link, [
            'outcome' => ReassessmentOutcome::Close,
            'owner_id' => $link->owner_id,
            'justification' => 'Justificativa',
            'cause_status' => CauseStatus::NotIdentified,
            ...$data,
        ]);
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->first();
    }

    throw new RuntimeException('The reassessment should have been refused.');
}

// Preconditions (0020).

test('a reassessment needs a declared link still in the chain with a pending reversal', function (Closure $arrange, string $message) {
    expect(reassessmentRefusal($arrange(), []))->toBe($message)
        ->and(Reassessment::count())->toBeLessThanOrEqual(1);
})->with([
    'never reverted' => [fn () => Link::factory()->create(['status' => LinkStatus::Planned]), 'Este vínculo nunca foi revertido, então não há o que reavaliar.'],
    'verified' => [function (): Link {
        $link = revertedLink();
        reassessmentEvidence($link);
        app(RecordStatusChange::class)->verify($link, Owner::factory()->create());

        return $link;
    }, 'Um vínculo verificado não tem o que reavaliar.'],
    'cancelled' => [function (): Link {
        $link = revertedLink();
        $link->update(['status' => LinkStatus::Cancelled]);

        return $link;
    }, 'Um vínculo cancelado não pode ser reavaliado.'],
]);

test('a reversal admits a single reassessment', function () {
    $link = revertedLink();

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Adjust->value,
    ]))->assertSessionHasNoErrors();

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Adjust->value,
    ]))->assertSessionHasErrors(['reassessment' => 'A última reversão deste vínculo já foi reavaliada.']);

    expect(Reassessment::count())->toBe(1);

    // The database stands behind it too.
    expect(fn () => DB::transaction(fn () => DB::table('reassessments')->insert([
        ...Arr::except(Reassessment::sole()->getAttributes(), ['id']),
    ])))->toThrow(QueryException::class);
});

test('the owner, the justification and the cause analysis are checked', function (array $overrides, string $field, string $message) {
    $link = revertedLink();

    if (($overrides['owner_id'] ?? null) === 'inactive') {
        $overrides['owner_id'] = Owner::factory()->create(['deactivated_at' => now()])->id;
    }

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, $overrides))
        ->assertSessionHasErrors([$field => $message]);

    expect(Reassessment::count())->toBe(0);
})->with([
    'inactive owner' => [['owner_id' => 'inactive'], 'owner_id', 'Escolha um responsável ativo.'],
    'no justification' => [['justification' => ''], 'justification', 'Informe a justificativa da reavaliação.'],
    'no cause status' => [['cause_status' => null], 'cause_status', 'Informe se a causa foi apurada.'],
    'identified without the cause' => [['cause_status' => 'identified', 'cause_phase' => 'design'], 'cause', 'Descreva a causa apurada.'],
    'identified without the phase' => [['cause_status' => 'identified', 'cause' => 'Uma causa'], 'cause_phase', 'Escolha a fase do ciclo de vida em que a causa se originou.'],
]);

test('the cause analysis follows the origin of the reversal', function (ChangeOrigin $origin, array $sent, CauseStatus $expected) {
    $link = revertedLink($origin);

    $reassessment = app(RecordReassessment::class)->handle($link, [
        'outcome' => ReassessmentOutcome::Close,
        'owner_id' => $link->owner_id,
        'justification' => 'Justificativa',
        ...$sent,
    ]);

    expect($reassessment->cause_status)->toBe($expected);

    if ($expected === CauseStatus::Identified) {
        expect($reassessment->cause)->toBe('Filtro desatualizado')
            ->and($reassessment->cause_phase)->toBe(LifecyclePhase::Monitoring);
    } else {
        expect($reassessment->cause)->toBeNull()->and($reassessment->cause_phase)->toBeNull();
    }
})->with([
    'manual, identified' => [ChangeOrigin::Manual, ['cause_status' => 'identified', 'cause' => 'Filtro desatualizado', 'cause_phase' => 'monitoring'], CauseStatus::Identified],
    'adverse event, not identified' => [ChangeOrigin::AdverseEvent, ['cause_status' => 'not_identified'], CauseStatus::NotIdentified],
    // No cause to find, whatever is sent.
    'review due' => [ChangeOrigin::ReviewDue, ['cause_status' => 'identified', 'cause' => 'x', 'cause_phase' => 'design'], CauseStatus::NotApplicable],
    'reclassification' => [ChangeOrigin::SystemReclassification, [], CauseStatus::NotApplicable],
]);

test('the database keeps the cause analysis consistent', function () {
    $link = revertedLink();
    $reassessment = app(RecordReassessment::class)->handle($link, [
        'outcome' => ReassessmentOutcome::Adjust,
        'owner_id' => $link->owner_id,
        'justification' => 'Justificativa',
        'cause_status' => CauseStatus::NotIdentified,
    ]);

    expect(fn () => DB::transaction(fn () => DB::table('reassessments')->where('id', $reassessment->id)->update(['cause_status' => 'identified', 'cause_phase' => 'design'])))
        ->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('reassessments')->where('id', $reassessment->id)->update(['cause' => 'Algo'])))
        ->toThrow(QueryException::class);
});

// Maintain.

test('maintaining needs evidence stored after the reversal and verifies in the same act', function () {
    $this->travelTo('2026-03-01 09:00');
    $link = revertedLink();
    $reversalDate = $link->lastReversal->change_date->format('d/m/Y');

    expect(reassessmentRefusal($link, ['outcome' => ReassessmentOutcome::Maintain]))
        ->toBe("Registre uma evidência nova: a última reversão foi em {$reversalDate}.");

    $this->get(route('links.reassessments.create', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('outcomes.0.value', 'maintain')
            ->where('outcomes.0.problem', "Registre uma evidência nova: a última reversão foi em {$reversalDate}.")
            ->where('outcomes.1.problem', null)
    );

    reassessmentEvidence($link);
    $this->travel(1)->minutes();

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Maintain->value,
    ]))->assertSessionHasNoErrors();

    $reassessment = Reassessment::sole();

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($link->next_review_date)->not->toBeNull()
        ->and($reassessment->verification_id)->toBe($link->lastVerification->id)
        // Linked both ways.
        ->and($link->lastVerification->concludedReassessment->id)->toBe($reassessment->id)
        ->and(Link::awaitingReassessment()->exists())->toBeFalse();
});

// Adjust.

test('adjusting applies and records the changes, and leaves the link awaiting verification', function () {
    $link = revertedLink();
    $newOwner = Owner::factory()->create();
    $before = ['owner_id' => (string) $link->owner_id, 'estimated_cost' => $link->estimated_cost->value, 'lifecycle_phase' => $link->lifecycle_phase->value];
    $cost = $link->estimated_cost === CostLevel::High ? CostLevel::Low : CostLevel::High;
    $phase = $link->lifecycle_phase === LifecyclePhase::Monitoring ? LifecyclePhase::Design : LifecyclePhase::Monitoring;

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Adjust->value,
        'changes' => [
            'owner_id' => $newOwner->id,
            'estimated_cost' => $cost->value,
            'lifecycle_phase' => $phase->value,
            'status' => LinkStatus::Implemented->value,
        ],
    ]))->assertSessionHasNoErrors();

    $reassessment = Reassessment::sole();
    $statusEntry = $link->statusHistories()->where('new_status', LinkStatus::Implemented)->sole();

    expect($link->refresh()->owner_id)->toBe($newOwner->id)
        ->and($link->estimated_cost)->toBe($cost)
        ->and($link->lifecycle_phase)->toBe($phase)
        ->and($link->status)->toBe(LinkStatus::Implemented)
        ->and($reassessment->changes)->toBe([
            ['field' => 'owner_id', 'before' => $before['owner_id'], 'after' => (string) $newOwner->id],
            ['field' => 'estimated_cost', 'before' => $before['estimated_cost'], 'after' => $cost->value],
            ['field' => 'lifecycle_phase', 'before' => $before['lifecycle_phase'], 'after' => $phase->value],
            ['field' => 'status', 'before' => 'in_progress', 'after' => 'implemented'],
        ])
        // The progress move went through the single path (0007).
        ->and($statusEntry->previous_status)->toBe(LinkStatus::InProgress)
        ->and($reassessment->verification_id)->toBeNull()
        ->and($link->verification_status)->toBe(VerificationStatus::Declared)
        ->and(Link::awaitingVerification()->pluck('id')->all())->toBe([$link->id])
        ->and(Link::awaitingReassessment()->exists())->toBeFalse();
});

test('adjusting may verify in the same act', function () {
    $link = revertedLink();
    reassessmentEvidence($link);
    $this->travel(1)->minutes();

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Adjust->value,
        'verify' => '1',
    ]))->assertSessionHasNoErrors();

    $reassessment = Reassessment::sole();

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($reassessment->verification_id)->toBe($link->lastVerification->id)
        ->and($reassessment->changes)->toBe([]);
});

test('an adjustment cannot cancel the link nor move it to an inactive owner', function () {
    $link = revertedLink();

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Adjust->value,
        'changes' => ['status' => LinkStatus::Cancelled->value],
    ]))->assertSessionHasErrors(['changes.status' => 'Para cancelar o vínculo, escolha encerrar ou substituir.']);

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Adjust->value,
        'changes' => ['owner_id' => Owner::factory()->create(['deactivated_at' => now()])->id],
    ]))->assertSessionHasErrors('changes.owner_id');

    expect(Reassessment::count())->toBe(0)
        ->and($link->refresh()->status)->toBe(LinkStatus::InProgress);
});

// Replace and close.

test('replacing cancels the link and leads to the new link, which records it', function () {
    $link = revertedLink();
    $other = Mitigation::factory()->create();

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Replace->value,
        'justification' => 'Outra mitigação trata melhor este risco.',
    ]))->assertRedirect(route('links.create', ['risk' => $link->risk_id, 'replaces' => $link->id]));

    $cancel = $link->statusHistories()->where('new_status', LinkStatus::Cancelled)->sole();

    expect($link->refresh()->status)->toBe(LinkStatus::Cancelled)
        ->and($cancel->trigger_reason)->toBe('Outra mitigação trata melhor este risco.');

    $this->get(route('links.create', ['risk' => $link->risk_id, 'replaces' => $link->id]))->assertInertia(
        fn (AssertableInertia $page) => $page->where('replacing.id', $link->id)->where('replacing.risk_id', $link->risk_id)
    );

    $this->post(route('links.store'), [
        'risk_id' => $link->risk_id,
        'mitigation_id' => $other->id,
        'owner_id' => $link->owner_id,
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'estimated_cost' => CostLevel::Medium->value,
        'replaces_link_id' => $link->id,
    ])->assertSessionHasNoErrors();

    $replacement = Link::query()->where('mitigation_id', $other->id)->sole();

    expect($replacement->replaces_link_id)->toBe($link->id)
        ->and($link->replacedBy->id)->toBe($replacement->id);

    $this->get(route('links.show', $replacement))->assertInertia(
        fn (AssertableInertia $page) => $page->where('link.replaces.id', $link->id)
    );
    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page->where('link.replaced_by.id', $replacement->id)
    );
});

test('a link can be replaced only by one of its risk, after a replacing reassessment, once', function (Closure $arrange, string $message) {
    [$replaced, $riskId] = $arrange();

    $this->post(route('links.store'), [
        'risk_id' => $riskId,
        'mitigation_id' => Mitigation::factory()->create()->id,
        'owner_id' => Owner::factory()->create()->id,
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'estimated_cost' => CostLevel::Medium->value,
        'replaces_link_id' => $replaced->id,
    ])->assertSessionHasErrors(['replaces_link_id' => $message]);
})->with([
    'another risk' => [function (): array {
        $link = revertedLink();
        app(RecordReassessment::class)->handle($link, ['outcome' => ReassessmentOutcome::Replace, 'owner_id' => $link->owner_id, 'justification' => 'x', 'cause_status' => CauseStatus::NotIdentified]);

        return [$link, Risk::factory()->create()->id];
    }, 'O vínculo substituído deve ser do mesmo risco.'],
    'closed, not replaced' => [function (): array {
        $link = revertedLink();
        app(RecordReassessment::class)->handle($link, ['outcome' => ReassessmentOutcome::Close, 'owner_id' => $link->owner_id, 'justification' => 'x', 'cause_status' => CauseStatus::NotIdentified]);

        return [$link, $link->risk_id];
    }, 'Só pode ser substituído um vínculo cancelado por uma reavaliação que escolheu substituí-lo.'],
    'still active' => [fn (): array => [($link = revertedLink()), $link->risk_id], 'Só pode ser substituído um vínculo cancelado por uma reavaliação que escolheu substituí-lo.'],
    'already replaced' => [function (): array {
        $link = revertedLink();
        app(RecordReassessment::class)->handle($link, ['outcome' => ReassessmentOutcome::Replace, 'owner_id' => $link->owner_id, 'justification' => 'x', 'cause_status' => CauseStatus::NotIdentified]);
        Link::factory()->for($link->risk)->create(['replaces_link_id' => $link->id]);

        return [$link, $link->risk_id];
    }, 'Este vínculo já foi substituído.'],
]);

test('closing cancels the link with no replacement', function () {
    $link = revertedLink();

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link))
        ->assertRedirect(route('reassessments.show', Reassessment::sole()));

    expect($link->refresh()->status)->toBe(LinkStatus::Cancelled)
        ->and($link->replacedBy)->toBeNull()
        ->and(Reassessment::sole()->outcome)->toBe(ReassessmentOutcome::Close)
        ->and(Link::awaitingReassessment()->exists())->toBeFalse()
        ->and(Link::awaitingVerification()->exists())->toBeFalse();
});

// Unacceptable systems (0020, item 8).

test('a link of an unacceptable system can only be adjusted or closed', function () {
    $link = revertedLink(ChangeOrigin::SystemReclassification);
    $link->risk->aiSystem->update(['category' => AiSystemCategory::Unacceptable]);
    reassessmentEvidence($link);
    $message = 'Sistema na faixa inaceitável: só é possível ajustar, para planejar a descontinuação, ou encerrar.';

    expect(reassessmentRefusal($link, ['outcome' => ReassessmentOutcome::Maintain]))->toBe($message)
        ->and(reassessmentRefusal($link, ['outcome' => ReassessmentOutcome::Replace]))->toBe($message);

    $this->get(route('links.reassessments.create', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('outcomes', fn ($outcomes) => collect($outcomes)->mapWithKeys(fn ($o) => [$o['value'] => $o['problem']])->all() === [
                'maintain' => $message,
                'adjust' => null,
                'replace' => $message,
                'close' => null,
            ])
            ->where('causeApplies', false)
    );

    $this->post(route('links.reassessments.store', $link), reassessmentPayload($link, [
        'outcome' => ReassessmentOutcome::Adjust->value,
        'cause_status' => null,
        'changes' => ['lifecycle_phase' => LifecyclePhase::Decommissioning->value],
    ]))->assertSessionHasNoErrors();

    expect($link->refresh()->lifecycle_phase)->toBe(LifecyclePhase::Decommissioning)
        ->and(Reassessment::sole()->cause_status)->toBe(CauseStatus::NotApplicable);
});

// States and filters (0020, item 4).

test('the link list and the dashboard follow the reassessment states, oldest first', function () {
    $this->travelTo('2026-05-01 09:00');
    $older = revertedLink();
    $this->travelTo('2026-05-10 09:00');
    $newer = revertedLink(ChangeOrigin::ReviewDue);
    $adjusted = revertedLink();
    app(RecordReassessment::class)->handle($adjusted, ['outcome' => ReassessmentOutcome::Adjust, 'owner_id' => $adjusted->owner_id, 'justification' => 'x', 'cause_status' => CauseStatus::NotIdentified]);
    $neverVerified = Link::factory()->for(Risk::factory()->for(AiSystem::factory()->highRisk()))->create(['status' => LinkStatus::Planned]);

    $ids = fn (string $filter, array $extra = []): array => collect(
        $this->get(route('links.index', ['verification' => $filter, ...$extra]))->viewData('page')['props']['links']['data'],
    )->pluck('id')->all();

    expect($ids('awaiting_reassessment'))->toBe([$older->id, $newer->id])
        ->and($ids('awaiting_reassessment', ['origin' => 'review_due']))->toBe([$newer->id])
        ->and(collect($ids('awaiting_verification'))->sort()->values()->all())->toBe(collect([$adjusted->id, $neverVerified->id])->sort()->values()->all());

    $this->get(route('links.index', ['verification' => 'awaiting_reassessment']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('links.data.0.last_reversal.change_date', fn ($date) => str_starts_with($date, '2026-05-01'))
    );

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('verification.awaitingReassessment', 2)
            ->where('verification.awaitingVerification', 2)
            ->where('verification.longestAwaiting.0.id', $older->id)
            ->where('verification.longestAwaiting.1.id', $newer->id)
    );
});

test('the link page offers to reassess only while a reversal is pending', function () {
    $link = revertedLink();

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page->where('awaitingReassessment', true)
    );

    app(RecordReassessment::class)->handle($link, ['outcome' => ReassessmentOutcome::Adjust, 'owner_id' => $link->owner_id, 'justification' => 'x', 'cause_status' => CauseStatus::NotIdentified]);

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('awaitingReassessment', false)
            ->has('link.reassessments', 1)
            ->where('link.last_reversal.reassessment.outcome', 'adjust')
    );

    $this->get(route('links.reassessments.create', $link))->assertRedirect(route('links.show', $link));
});

test('the timeline shows the reassessments among the changes', function () {
    $link = revertedLink();
    $this->travel(1)->minutes();
    app(RecordReassessment::class)->handle($link, ['outcome' => ReassessmentOutcome::Close, 'owner_id' => $link->owner_id, 'justification' => 'Encerrado', 'cause_status' => CauseStatus::NotIdentified]);

    $this->get(route('links.status-histories.index', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            // Verification, reversal, cancellation and the reassessment.
            ->has('entries.data', 4)
            ->where('entries.data.0.type', 'reassessment')
            ->where('entries.data.0.entry.outcome', 'close')
            ->where('entries.data.1.entry.new_status', 'cancelled')
    );

    $this->get(route('reassessments.show', Reassessment::sole()))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('reassessments/show')
            ->where('reassessment.outcome', 'close')
            ->where('reassessment.reversal.origin', 'manual')
            ->has('reassessment.owner.organizational_role')
    );
});

// The event and the report.

test('the event page shows how far the reassessments of its links went', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $links = collect(range(1, 3))->map(function () use ($aiSystem): Link {
        $link = Link::factory()->for(Risk::factory()->for($aiSystem)->inSubdomain('2.1'))->create(['status' => LinkStatus::InProgress]);
        reassessmentEvidence($link);
        app(RecordStatusChange::class)->verify($link, Owner::factory()->create());

        return $link;
    });
    $this->travel(1)->minutes();

    $this->post(route('adverse-events.store'), [
        'nature' => AdverseEventNature::Incident->value,
        'risk_subdomains' => ['2.1'],
        'description' => 'Vazamento',
        'occurrence_date' => today()->subDay()->toDateString(),
        'ai_system_id' => $aiSystem->id,
    ])->assertSessionHasNoErrors();
    $this->travel(1)->minutes();

    $first = $links->first()->refresh();
    app(RecordReassessment::class)->handle($first, ['outcome' => ReassessmentOutcome::Close, 'owner_id' => $first->owner_id, 'justification' => 'x', 'cause_status' => CauseStatus::NotIdentified]);

    $this->get(route('adverse-events.show', AdverseEvent::sole()))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('adverseEvent.reversals', 3)
            ->where('adverseEvent.reversals', fn ($reversals) => collect($reversals)->filter(fn ($r) => $r['reassessment'] !== null)->pluck('reassessment.outcome')->values()->all() === ['close'])
    );
});

test('the report exports the events and the reassessments', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = Link::factory()->for(Risk::factory()->for($aiSystem)->inSubdomain('2.1'))->create(['status' => LinkStatus::InProgress]);
    reassessmentEvidence($link);
    app(RecordStatusChange::class)->verify($link, Owner::factory()->create());
    $this->travel(1)->minutes();

    $this->post(route('adverse-events.store'), [
        'nature' => AdverseEventNature::Incident->value,
        'risk_subdomains' => ['2.1'],
        'description' => 'Vazamento',
        'occurrence_date' => '2026-01-10',
        'detected_at' => '2026-01-12',
        'ai_system_id' => $aiSystem->id,
    ])->assertSessionHasNoErrors();
    $this->travel(1)->minutes();

    $owner = Owner::factory()->create(['organizational_role' => 'Revisor de riscos']);
    app(RecordReassessment::class)->handle($link->refresh(), [
        'outcome' => ReassessmentOutcome::Close,
        'owner_id' => $owner->id,
        'justification' => 'Não se justifica mais.',
        'cause_status' => CauseStatus::Identified,
        'cause' => 'Filtro desatualizado',
        'cause_phase' => LifecyclePhase::Monitoring,
    ]);
    $event = AdverseEvent::sole();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('adverse_events.0', [
            'id' => $event->id,
            'nature' => 'incident',
            'risk_subdomains' => ['2.1'],
            'occurrence_date' => '2026-01-10',
            'detected_at' => '2026-01-12',
            'intercepting_link_id' => null,
            'reverted_link_ids' => [$link->id],
        ])
        ->assertJsonPath('links.0.reassessments.0.outcome', 'close')
        ->assertJsonPath('links.0.reassessments.0.owner', 'Revisor de riscos')
        ->assertJsonPath('links.0.reassessments.0.justification', 'Não se justifica mais.')
        ->assertJsonPath('links.0.reassessments.0.cause_status', 'identified')
        ->assertJsonPath('links.0.reassessments.0.cause', 'Filtro desatualizado')
        ->assertJsonPath('links.0.reassessments.0.cause_phase', 'monitoring')
        ->assertJsonPath('links.0.reassessments.0.reversal_origin', 'adverse_event')
        ->assertJsonPath('links.0.reverting_adverse_events.0.id', $event->id);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());
    $row = array_combine($rows[0], $rows[1]);

    expect($row['reassessments'])->toContain('close by Revisor de riscos')
        ->toContain('cause: identified, monitoring, Filtro desatualizado')
        ->and($row['reverting_adverse_events'])->toContain("#{$event->id} incident [2.1] occurred 2026-01-10, detected 2026-01-12")
        ->and($row['replaces_link_id'])->toBe('');
});
