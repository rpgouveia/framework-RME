<?php

use App\Actions\RecordEvidence;
use App\Actions\RecordStatusChange;
use App\Enums\AiSystemCategory;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\SystemChangeType;
use App\Enums\VerificationStatus;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Owner;
use App\Models\Reassessment;
use App\Models\Risk;
use App\Models\StatusHistory;
use App\Models\SystemChange;
use App\Models\User;
use App\Support\MonitoringProtocol;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A declared link of an operable system, with its opening trail entry.
 */
function declaredLink(AiSystemCategory $tier = AiSystemCategory::High): Link
{
    $link = Link::factory()
        ->for(Risk::factory()->for(AiSystem::factory()->state(['category' => $tier])))
        ->create(['status' => LinkStatus::Planned]);

    app(RecordStatusChange::class)->open($link);

    return $link;
}

/**
 * Register evidence the way the app does.
 */
function evidenceFor(Link $link, ?CostLevel $cost = null): Evidence
{
    return app(RecordEvidence::class)->handle($link, [
        'type' => EvidenceType::Report,
        'description' => 'Relatório de auditoria',
        'observed_cost' => $cost,
    ]);
}

/**
 * The validation message a verification attempt fails with.
 */
function verificationRefusal(Link $link, ?Owner $verifier = null): string
{
    try {
        app(RecordStatusChange::class)->verify($link, $verifier ?? Owner::factory()->create());
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->first();
    }

    throw new RuntimeException('The verification should have been refused.');
}

// Verifying (0018).

test('verifying records the change, starts the review by the tier and leaves the progress alone', function (AiSystemCategory $tier, string $due) {
    $this->travelTo('2026-03-01 10:00');
    $link = declaredLink($tier);
    $link->update(['status' => LinkStatus::InProgress]);
    evidenceFor($link);
    $verifier = Owner::factory()->create();

    $this->travelTo('2026-03-10 15:00');
    $entry = app(RecordStatusChange::class)->verify($link, $verifier);

    expect($link->verification_status)->toBe(VerificationStatus::Verified)
        ->and($link->next_review_date->toDateString())->toBe($due)
        ->and($link->status)->toBe(LinkStatus::InProgress)
        ->and($entry->previous_verification)->toBe(VerificationStatus::Declared)
        ->and($entry->new_verification)->toBe(VerificationStatus::Verified)
        ->and($entry->previous_status)->toBeNull()
        ->and($entry->new_status)->toBeNull()
        ->and($entry->origin)->toBe(ChangeOrigin::Manual)
        ->and($entry->owner_id)->toBe($verifier->id)
        ->and($entry->change_date->toDateString())->toBe('2026-03-10');
})->with([
    'high: 90 days' => [AiSystemCategory::High, '2026-06-08'],
    'limited: 180 days' => [AiSystemCategory::Limited, '2026-09-06'],
    'minimal: 365 days' => [AiSystemCategory::Minimal, '2027-03-10'],
]);

test('the review interval is the one of the tier at the verification', function () {
    $this->travelTo('2026-03-10 10:00');
    $link = declaredLink(AiSystemCategory::Minimal);
    evidenceFor($link);

    $link->risk->aiSystem->update(['category' => AiSystemCategory::High]);

    app(RecordStatusChange::class)->verify($link, Owner::factory()->create());

    expect($link->next_review_date->toDateString())->toBe('2026-06-08');
});

test('a verification is refused with a readable reason', function (Closure $arrange, string $message) {
    $link = declaredLink();
    $arrange($link);

    expect(verificationRefusal($link))->toBe($message)
        ->and($link->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($link->statusHistories()->whereNotNull('new_verification')->count())->toBe(0);
})->with([
    'no evidence' => [fn (Link $link) => null, 'Registre uma evidência antes de verificar o vínculo.'],
    'cancelled' => [function (Link $link): void {
        evidenceFor($link);
        $link->update(['status' => LinkStatus::Cancelled]);
    }, 'Um vínculo cancelado não pode ser verificado.'],
    'unacceptable system' => [function (Link $link): void {
        evidenceFor($link);
        $link->risk->aiSystem->update(['category' => AiSystemCategory::Unacceptable]);
    }, 'Um vínculo de sistema na faixa inaceitável não pode ser verificado: o sistema nunca opera.'],
]);

test('a verified link is renewed only on evidence stored after its verification', function () {
    $this->travelTo('2026-03-01 09:00');
    $link = declaredLink();
    evidenceFor($link);
    app(RecordStatusChange::class)->verify($link, Owner::factory()->create());

    expect(verificationRefusal($link))->toBe('Registre uma evidência nova para renovar: a última verificação foi em 01/03/2026.');
});

test('the verifier must be an active owner', function () {
    $link = declaredLink();
    evidenceFor($link);

    expect(verificationRefusal($link, Owner::factory()->create(['deactivated_at' => now()])))
        ->toBe('Escolha um responsável ativo.');

    $this->post(route('links.verification.store', $link), [
        'owner_id' => Owner::factory()->create(['deactivated_at' => now()])->id,
    ])->assertSessionHasErrors(['owner_id' => 'Escolha um responsável ativo.']);

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Declared);
});

test('after a reversal only evidence stored later counts, even on the same day', function () {
    $this->travelTo('2026-03-01 09:00');
    $link = declaredLink();
    evidenceFor($link);
    $owner = Owner::factory()->create();
    app(RecordStatusChange::class)->verify($link, $owner);

    // Evidence in the morning, reversal in the afternoon of the same day.
    $this->travelTo('2026-04-15 09:00');
    evidenceFor($link);
    $this->travelTo('2026-04-15 16:00');
    app(RecordStatusChange::class)->revert($link, ChangeOrigin::Manual, $owner, 'Controle desativado.');

    expect(verificationRefusal($link))->toBe('Registre uma evidência nova: a última reversão foi em 15/04/2026.');

    $this->travelTo('2026-04-15 16:30');
    evidenceFor($link);

    app(RecordStatusChange::class)->verify($link, $owner);

    expect($link->verification_status)->toBe(VerificationStatus::Verified)
        ->and($link->next_review_date->toDateString())->toBe('2026-07-14');
});

// Reverting.

test('a manual reversal needs an owner and a reason, and empties the review date', function () {
    $this->travelTo('2026-03-01 09:00');
    $link = declaredLink();
    evidenceFor($link);
    $owner = Owner::factory()->create();
    app(RecordStatusChange::class)->verify($link, $owner);

    $this->delete(route('links.verification.destroy', $link), [])
        ->assertSessionHasErrors(['owner_id', 'trigger_reason' => 'Informe o motivo da reversão da verificação.']);

    expect(fn () => app(RecordStatusChange::class)->revert($link, ChangeOrigin::Manual, null, 'Motivo'))
        ->toThrow(ValidationException::class);
    expect(fn () => app(RecordStatusChange::class)->revert($link, ChangeOrigin::Manual, $owner, '  '))
        ->toThrow(ValidationException::class);
    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Verified);

    $this->travelTo('2026-04-01 09:00');
    $this->delete(route('links.verification.destroy', $link), [
        'owner_id' => $owner->id,
        'trigger_reason' => 'A auditoria encontrou o controle desativado.',
    ])->assertSessionHasNoErrors()->assertRedirect(route('links.show', $link));

    $entry = $link->lastReversal()->first();

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($link->next_review_date)->toBeNull()
        ->and($entry->origin)->toBe(ChangeOrigin::Manual)
        ->and($entry->owner_id)->toBe($owner->id)
        ->and($entry->trigger_reason)->toBe('A auditoria encontrou o controle desativado.')
        ->and($entry->change_date->toDateString())->toBe('2026-04-01');
});

test('only a verified link can be reverted', function () {
    $link = declaredLink();

    $this->delete(route('links.verification.destroy', $link), [
        'owner_id' => $link->owner_id,
        'trigger_reason' => 'Motivo',
    ])->assertSessionHasErrors(['verification' => 'Só um vínculo verificado pode ter a verificação revertida.']);
});

test('an automatic reversal has no author and keeps its origin', function () {
    $link = declaredLink();
    evidenceFor($link);
    app(RecordStatusChange::class)->verify($link, Owner::factory()->create());

    $entry = app(RecordStatusChange::class)->revert($link, ChangeOrigin::ReviewDue);

    expect($entry->owner_id)->toBeNull()
        ->and($entry->origin)->toBe(ChangeOrigin::ReviewDue)
        ->and($link->verification_status)->toBe(VerificationStatus::Declared);
});

// The trail (0013): one dimension per entry; a manual entry has an owner.

test('every entry written by the actions changes exactly one dimension', function () {
    $link = declaredLink();
    $owner = Owner::factory()->create();
    evidenceFor($link);
    $record = app(RecordStatusChange::class);

    $record->handle($link, LinkStatus::InProgress, ['change_date' => today(), 'owner_id' => $owner->id]);
    $record->verify($link, $owner);
    $record->handle($link, LinkStatus::Implemented, ['change_date' => today(), 'owner_id' => $owner->id]);
    $record->revert($link, ChangeOrigin::Manual, $owner, 'Motivo');

    $link->statusHistories->each(function (StatusHistory $entry): void {
        $progress = $entry->new_status !== null;
        $verification = $entry->new_verification !== null;

        expect($progress xor $verification)->toBeTrue()
            ->and($progress ? $entry->previous_verification : $entry->previous_status)->toBeNull();
    });

    expect($link->statusHistories)->toHaveCount(5);
});

test('the database refuses an entry that changes both dimensions or none', function (array $columns) {
    $link = declaredLink();

    expect(fn () => StatusHistory::query()->insert([
        'link_id' => $link->id,
        'owner_id' => $link->owner_id,
        'change_date' => today(),
        'origin' => 'manual',
        'created_at' => now(),
        'updated_at' => now(),
        ...$columns,
    ]))->toThrow(QueryException::class);
})->with([
    'both' => [['previous_status' => 'planned', 'new_status' => 'in_progress', 'previous_verification' => 'declared', 'new_verification' => 'verified']],
    'none' => [['trigger_reason' => 'Nada muda']],
    'verification with a previous status' => [['previous_status' => 'planned', 'previous_verification' => 'declared', 'new_verification' => 'verified']],
]);

test('a manual entry without an owner is refused, an automatic one is accepted', function () {
    $link = declaredLink();

    expect(fn () => app(RecordStatusChange::class)->handle($link, LinkStatus::InProgress, ['change_date' => today()]))
        ->toThrow(ValidationException::class);

    $insert = fn (string $origin) => StatusHistory::query()->insert([
        'link_id' => $link->id,
        'owner_id' => null,
        'change_date' => today(),
        'origin' => $origin,
        'previous_verification' => 'verified',
        'new_verification' => 'declared',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // In a savepoint: on PostgreSQL a failed statement aborts the whole
    // transaction the test runs in.
    expect(fn () => DB::transaction(fn () => $insert('manual')))->toThrow(QueryException::class);

    $insert('adverse_event');

    expect(StatusHistory::query()->where('origin', 'adverse_event')->whereNull('owner_id')->count())->toBe(1);
});

// Cancelling.

test('cancelling a verified link keeps its verification but takes it out of monitoring', function () {
    $link = declaredLink();
    evidenceFor($link);
    app(RecordStatusChange::class)->verify($link, Owner::factory()->create());

    app(RecordStatusChange::class)->handle($link, LinkStatus::Cancelled, [
        'change_date' => today(),
        'owner_id' => $link->owner_id,
        'trigger_reason' => 'Mitigação substituída',
    ]);

    expect($link->verification_status)->toBe(VerificationStatus::Verified)
        ->and($link->next_review_date)->not->toBeNull()
        ->and(Link::monitorable()->pluck('id'))->not->toContain($link->id);
});

// Monitoring only counts verified links.

test('only verified links are monitorable, due for review or flagged by the command', function () {
    $this->freezeTime();

    $declared = Link::factory()->for(Risk::factory()->for(AiSystem::factory()->highRisk()))
        ->create(['status' => LinkStatus::Planned, 'next_review_date' => today()->subDay()]);
    $verified = Link::factory()->dueForReview()->for(Risk::factory()->for(AiSystem::factory()->highRisk()))
        ->create(['status' => LinkStatus::Planned]);

    expect(Link::monitorable()->pluck('id')->all())->toBe([$verified->id])
        ->and(Link::dueForReview()->pluck('id')->all())->toBe([$verified->id]);

    $this->artisan('links:flag-due-for-review')
        ->assertSuccessful()
        ->expectsOutputToContain($verified->next_review_date->toDateString())
        ->doesntExpectOutputToContain((string) $declared->risk->name);
});

// The observed cost comes with the evidence (RF07).

test('the observed cost is the one of the latest evidence that reported it', function () {
    $this->travelTo('2026-03-01 09:00');
    $link = declaredLink();

    $this->post(route('links.evidence.store', $link), [
        'type' => EvidenceType::Report->value,
        'description' => 'Primeira medição',
        'observed_cost' => CostLevel::Low->value,
    ])->assertSessionHasNoErrors();

    $this->travelTo('2026-03-02 09:00');
    $this->post(route('links.evidence.store', $link), [
        'type' => EvidenceType::Report->value,
        'description' => 'Custo revisto',
        'observed_cost' => CostLevel::High->value,
    ])->assertSessionHasNoErrors();

    // Evidence that reports no cost leaves the last one in place.
    $this->travelTo('2026-03-03 09:00');
    $this->post(route('links.evidence.store', $link), [
        'type' => EvidenceType::Document->value,
        'description' => 'Ata',
        'observed_cost' => '',
    ])->assertSessionHasNoErrors();

    expect($link->observedCostEvidence->observed_cost)->toBe(CostLevel::High)
        ->and($link->observedCostEvidence->description)->toBe('Custo revisto')
        ->and(Evidence::query()->where('description', 'Ata')->sole()->observed_cost)->toBeNull();

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('link.observed_cost_evidence.observed_cost', 'high')
            ->where('link.observed_cost_evidence.description', 'Custo revisto')
    );
});

test('an observed cost outside the scale is refused', function () {
    $link = declaredLink();

    $this->post(route('links.evidence.store', $link), [
        'type' => EvidenceType::Report->value,
        'description' => 'Relatório',
        'observed_cost' => 'astronomical',
    ])->assertSessionHasErrors('observed_cost');
});

// Screens.

test('the link page carries the verification state, the last moves and why it cannot be verified', function () {
    $this->travelTo('2026-03-01 09:00');
    $link = declaredLink();
    $owner = Owner::factory()->create(['organizational_role' => 'Verificador independente']);

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('links/show')
            ->where('link.verification_status', 'declared')
            ->where('link.last_verification', null)
            ->where('link.last_reversal', null)
            ->where('verification.problem', 'Registre uma evidência antes de verificar o vínculo.')
            ->where('verification.owners', fn ($owners) => collect($owners)->pluck('id')->contains($owner->id))
    );

    evidenceFor($link);
    $this->post(route('links.verification.store', $link), ['owner_id' => $owner->id])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('links.show', $link));

    $this->travelTo('2026-03-20 09:00');
    app(RecordStatusChange::class)->revert($link, ChangeOrigin::Manual, $owner, 'Controle desativado.');

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('link.verification_status', 'declared')
            ->where('link.last_verification.change_date', '2026-03-01T00:00:00.000000Z')
            ->where('link.last_verification.owner.organizational_role', 'Verificador independente')
            ->where('link.last_reversal.trigger_reason', 'Controle desativado.')
            ->where('link.last_reversal.origin', 'manual')
            ->where('verification.problem', 'Registre uma evidência nova: a última reversão foi em 20/03/2026.')
    );
});

test('the evidence list offers to verify a declared link that is ready', function () {
    $link = declaredLink();

    $this->get(route('links.evidence.index', $link))->assertInertia(
        fn (AssertableInertia $page) => $page->where('canVerify', false)
    );

    evidenceFor($link);

    $this->get(route('links.evidence.index', $link))->assertInertia(
        fn (AssertableInertia $page) => $page->where('canVerify', true)
    );

    app(RecordStatusChange::class)->verify($link, Owner::factory()->create());

    $this->get(route('links.evidence.index', $link))->assertInertia(
        fn (AssertableInertia $page) => $page->where('canVerify', false)
    );
});

test('the link list filters by verification', function () {
    $owner = Owner::factory()->create();
    $record = app(RecordStatusChange::class);

    $neverVerified = declaredLink();
    $verified = declaredLink();
    evidenceFor($verified);
    $record->verify($verified, $owner);
    $reverted = declaredLink();
    evidenceFor($reverted);
    $record->verify($reverted, $owner);
    $record->revert($reverted, ChangeOrigin::Manual, $owner, 'Motivo');
    // Out of the pending lists: closed, or never verifiable.
    $cancelled = declaredLink();
    $cancelled->update(['status' => LinkStatus::Cancelled]);
    $prohibited = declaredLink(AiSystemCategory::Unacceptable);

    $ids = fn (?string $filter): array => collect(
        $this->get(route('links.index', array_filter(['verification' => $filter])))->viewData('page')['props']['links']['data'],
    )->pluck('id')->sort()->values()->all();

    expect($ids('awaiting_verification'))->toBe([$neverVerified->id])
        ->and($ids('awaiting_reassessment'))->toBe([$reverted->id])
        ->and($ids('verified'))->toBe([$verified->id])
        ->and($ids(null))->toBe(collect([$neverVerified, $verified, $reverted, $cancelled, $prohibited])->pluck('id')->sort()->values()->all())
        // An unknown filter shows every link.
        ->and($ids('everything'))->toHaveCount(5);

    $this->get(route('links.index', ['verification' => 'verified']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('filters.verification', 'verified')
    );

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('verification.awaitingVerification', 1)
            ->where('verification.awaitingReassessment', 1)
            ->where('verification.verified', 1)
    );
});

test('the trail shows verification entries and automatic ones', function () {
    $link = declaredLink();
    evidenceFor($link);
    $verification = app(RecordStatusChange::class)->verify($link, Owner::factory()->create());
    $automatic = app(RecordStatusChange::class)->revert($link, ChangeOrigin::SystemReclassification);

    $this->get(route('links.status-histories.index', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('entries.data', 3)
            ->where('entries.data', fn ($entries) => collect($entries)->contains(
                fn (array $item): bool => $item['type'] === 'change'
                    && $item['entry']['id'] === $automatic->id
                    && $item['entry']['origin'] === 'system_reclassification'
                    && $item['entry']['owner'] === null
                    && $item['entry']['new_verification'] === 'declared',
            ))
    );

    $this->get(route('status-histories.show', $automatic))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('statusHistory.origin', 'system_reclassification')
            ->where('statusHistory.owner', null)
            ->where('statusHistory.previous_verification', 'verified')
    );

    $this->get(route('status-histories.show', $verification))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('statusHistory.new_verification', 'verified')
            ->where('statusHistory.new_status', null)
            ->has('statusHistory.owner.organizational_role')
    );
});

test('a verification entry shows the evidence it rested on instead of a reason', function () {
    $owner = Owner::factory()->create();
    $record = app(RecordStatusChange::class);

    $this->travelTo('2026-03-01 09:00');
    $link = declaredLink();
    $first = evidenceFor($link);
    $this->travelTo('2026-03-02 09:00');
    $second = evidenceFor($link);
    $this->travelTo('2026-03-03 09:00');
    $firstVerification = $record->verify($link, $owner);

    // Stored after the verification: it did not back it.
    $this->travelTo('2026-03-10 09:00');
    evidenceFor($link);
    $this->travelTo('2026-04-01 09:00');
    $record->revert($link, ChangeOrigin::Manual, $owner, 'Controle desativado.');
    $this->travelTo('2026-04-02 09:00');
    $afterReversal = evidenceFor($link);
    $this->travelTo('2026-04-03 09:00');
    $secondVerification = $record->verify($link, $owner);

    $supporting = fn (StatusHistory $entry): array => collect(
        $this->get(route('status-histories.show', $entry))->viewData('page')['props']['supportingEvidence'],
    )->pluck('id')->all();

    // First cycle: any evidence stored up to the verification.
    expect($supporting($firstVerification))->toBe([$first->id, $second->id])
        // After a reversal: only what came after it.
        ->and($supporting($secondVerification))->toBe([$afterReversal->id])
        // Other entries rest on nothing.
        ->and($supporting($link->lastReversal))->toBe([])
        ->and($firstVerification->trigger_reason)->toBeNull();
});

// Report.

test('the report exports the verification and the observed cost', function () {
    $this->travelTo('2026-03-01 09:00');
    $link = declaredLink();
    $verifier = Owner::factory()->create(['organizational_role' => 'Verificador independente']);
    evidenceFor($link, CostLevel::Medium);
    app(RecordStatusChange::class)->verify($link, $verifier);
    $aiSystem = $link->risk->aiSystem;

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('links.0.verification.status', 'verified')
        ->assertJsonPath('links.0.verification.last_verified_on', '2026-03-01')
        ->assertJsonPath('links.0.verification.verified_by', 'Verificador independente')
        ->assertJsonPath('links.0.verification.changes', [[
            'date' => '2026-03-01',
            'from' => 'declared',
            'to' => 'verified',
            'origin' => 'manual',
            'recorded_by' => 'Verificador independente',
            'reason' => null,
            'adverse_event_id' => null,
            'system_change_id' => null,
        ]])
        ->assertJsonPath('links.0.observed_cost', 'medium')
        ->assertJsonPath('links.0.evidence.0.observed_cost', 'medium');

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());
    $row = array_combine($rows[0], $rows[1]);

    expect($row['verification_status'])->toBe('verified')
        ->and($row['last_verification_date'])->toBe('2026-03-01')
        ->and($row['last_verified_by'])->toBe('Verificador independente')
        ->and($row['observed_cost'])->toBe('medium');
});

test('a declared link exports no verification', function () {
    $link = declaredLink();

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $link->risk->aiSystem))->streamedContent());
    $row = array_combine($rows[0], $rows[1]);

    expect($row['verification_status'])->toBe('declared')
        ->and($row['last_verification_date'])->toBe('')
        ->and($row['last_verified_by'])->toBe('')
        ->and($row['next_review_date'])->toBe('');
});

// Seeders.

test('the seeders tell every verification story through the actions', function () {
    $this->seed(DatabaseSeeder::class);

    $unacceptable = fn ($query) => $query->where('category', AiSystemCategory::Unacceptable);

    expect(Link::awaitingVerification()->doesntHave('evidence')->exists())->toBeTrue()
        ->and(Link::awaitingVerification()->has('evidence')->exists())->toBeTrue()
        ->and(Link::verified()->exists())->toBeTrue()
        // Overdue links are reverted as the daily trigger does; one is due
        // today, still valid.
        ->and(Link::dueForReview()->exists())->toBeFalse()
        ->and(Link::monitorable()->whereDate('next_review_date', today())->exists())->toBeTrue()
        ->and(Link::awaitingReassessment()->count())->toBeGreaterThanOrEqual(2)
        // No link of an unacceptable system is verified.
        ->and(Link::query()->where('verification_status', VerificationStatus::Verified)->whereHas('risk.aiSystem', $unacceptable)->exists())->toBeFalse()
        // Declared links have no review date; verified ones do.
        ->and(Link::query()->where('verification_status', VerificationStatus::Declared)->whereNotNull('next_review_date')->exists())->toBeFalse()
        ->and(Link::query()->where('verification_status', VerificationStatus::Verified)->whereNull('next_review_date')->exists())->toBeFalse()
        // Every link has its opening entry, and every verification its own.
        ->and(Link::query()->whereDoesntHave('statusHistories', fn ($query) => $query->whereNull('previous_status')->whereNotNull('new_status'))->exists())->toBeFalse()
        ->and(Link::query()->where('verification_status', VerificationStatus::Verified)->doesntHave('lastVerification')->exists())->toBeFalse();

    // Both reassessment stories: one refused until new evidence, one ready.
    $problems = Link::awaitingReassessment()->get()->map(fn (Link $link) => app(RecordStatusChange::class)->verificationProblem($link));

    expect($problems->filter(fn (?string $problem) => $problem === null))->not->toBeEmpty()
        ->and($problems->filter(fn (?string $problem) => str_starts_with((string) $problem, 'Registre uma evidência nova')))->not->toBeEmpty();

    // The triggers of 0019: a reversal of every origin, a renewal, an
    // intercepted near miss, a risk not yet mapped, a reclassified system.
    foreach (ChangeOrigin::cases() as $origin) {
        expect(Link::revertedBy($origin)->exists())->toBeTrue("No link awaits reassessment from {$origin->value}.");
    }

    expect(StatusHistory::query()->where('previous_verification', 'verified')->where('new_verification', 'verified')->exists())->toBeTrue()
        ->and(AdverseEvent::query()->where('nature', 'near_miss')->whereNotNull('intercepting_link_id')->exists())->toBeTrue()
        ->and(AdverseEvent::query()->where('nature', 'incident')->exists())->toBeTrue()
        ->and(app(MonitoringProtocol::class)->unmappedRisks())->not->toBeEmpty()
        ->and(StatusHistory::query()->where('origin', 'system_reclassification')->exists())->toBeTrue();

    // The reassessments of 0020: every outcome, an adjustment awaiting its
    // verification, a replacement with its new link, an event only partly
    // reassessed, one in an unacceptable system, and links still waiting
    // for different lengths of time.
    foreach (ReassessmentOutcome::cases() as $outcome) {
        expect(Reassessment::query()->where('outcome', $outcome)->exists())->toBeTrue("No reassessment chose {$outcome->value}.");
    }

    $waitedSince = Link::awaitingReassessment()->with('lastReversal')->get()->map(fn (Link $link) => $link->lastReversal->change_date->toDateString());
    $partlyReassessed = AdverseEvent::query()->with('reversals.reassessment')->get()->contains(
        fn (AdverseEvent $event): bool => $event->reversals->count() > 1
            && $event->reversals->filter(fn (StatusHistory $reversal) => $reversal->reassessment !== null)->count() > 0
            && $event->reversals->contains(fn (StatusHistory $reversal) => $reversal->reassessment === null),
    );

    expect(Reassessment::query()->where('outcome', ReassessmentOutcome::Adjust)->whereNull('verification_id')->whereHas('link', fn ($link) => $link->awaitingVerification())->exists())->toBeTrue()
        ->and(Link::query()->whereNotNull('replaces_link_id')->exists())->toBeTrue()
        ->and($partlyReassessed)->toBeTrue()
        ->and(Reassessment::query()->whereHas('link.risk.aiSystem', $unacceptable)->exists())->toBeTrue()
        ->and($waitedSince->unique()->count())->toBeGreaterThan(1);

    // The system changes of 0021: one naming no subdomain, one naming some,
    // one naming a subdomain with no risk, one in an unacceptable system.
    $changes = SystemChange::query()->with('riskSubdomains', 'aiSystem')->withCount('reversals')->get();

    expect($changes->contains(fn (SystemChange $change) => $change->type === SystemChangeType::ModelVersion && $change->riskSubdomains->isEmpty() && $change->reversals_count > 0))->toBeTrue()
        ->and($changes->contains(fn (SystemChange $change) => $change->type === SystemChangeType::DataChange && $change->riskSubdomains->isNotEmpty() && $change->reversals_count > 0))->toBeTrue()
        ->and($changes->contains(fn (SystemChange $change) => $change->aiSystem->category === AiSystemCategory::Unacceptable && $change->reversals_count === 0))->toBeTrue()
        ->and(collect(app(MonitoringProtocol::class)->unmappedRisks())->contains(fn (array $risk) => in_array('system_change', $risk['sources'], true)))->toBeTrue();
});
