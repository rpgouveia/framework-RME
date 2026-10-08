<?php

namespace App\Models;

use App\Enums\AiSystemCategory;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\VerificationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The link between a risk, the mitigation addressing it and its owner.
 *
 * This is the core entity of the framework: it carries the cost, schedule and
 * status of a mitigation applied to a specific risk. The status has two
 * dimensions (0013): progress of the implementation, and verification
 * (declared or verified), which only RecordStatusChange changes.
 *
 * @property int $id
 * @property LifecyclePhase $lifecycle_phase
 * @property LinkStatus $status
 * @property VerificationStatus $verification_status
 * @property CostLevel $estimated_cost
 * @property CarbonImmutable $creation_date
 * @property CarbonImmutable|null $next_review_date
 * @property int $risk_id
 * @property int $mitigation_id
 * @property int $owner_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Risk $risk
 * @property-read Mitigation $mitigation
 * @property-read Owner $owner
 * @property-read Collection<int, StatusHistory> $statusHistories
 * @property-read int|null $status_histories_count
 * @property-read Collection<int, Evidence> $evidence
 * @property-read int|null $evidence_count
 * @property-read Evidence|null $observedCostEvidence
 * @property-read StatusHistory|null $lastVerification
 * @property-read StatusHistory|null $lastReversal
 */
#[Fillable([
    'lifecycle_phase',
    'status',
    'verification_status',
    'estimated_cost',
    'creation_date',
    'next_review_date',
    'risk_id',
    'mitigation_id',
    'owner_id',
])]
class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory;

    /**
     * The risk being mitigated.
     *
     * @return BelongsTo<Risk, $this>
     */
    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    /**
     * The mitigation being applied.
     *
     * @return BelongsTo<Mitigation, $this>
     */
    public function mitigation(): BelongsTo
    {
        return $this->belongsTo(Mitigation::class);
    }

    /**
     * The role accountable for this link.
     *
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * The trail of status changes for this link.
     *
     * @return HasMany<StatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    /**
     * The evidence collected for this link.
     *
     * @return HasMany<Evidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /**
     * The latest evidence that reported an observed cost: its cost is the
     * link's observed cost (RF07, 0018). Evidence is append only, so this
     * never drifts from what was recorded.
     *
     * @return HasOne<Evidence, $this>
     */
    public function observedCostEvidence(): HasOne
    {
        return $this->hasOne(Evidence::class)->ofMany(
            ['created_at' => 'max', 'id' => 'max'],
            fn (Builder $query) => $query->whereNotNull('observed_cost'),
        );
    }

    /**
     * The verification changes of the link, oldest first: verifications,
     * renewals and reversals, each with its origin.
     *
     * @return HasMany<StatusHistory, $this>
     */
    public function verificationChanges(): HasMany
    {
        return $this->hasMany(StatusHistory::class)
            ->whereNotNull('new_verification')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * The entry that last verified the link.
     *
     * @return HasOne<StatusHistory, $this>
     */
    public function lastVerification(): HasOne
    {
        return $this->hasOne(StatusHistory::class)->ofMany(
            ['created_at' => 'max', 'id' => 'max'],
            fn (Builder $query) => $query->where('new_verification', VerificationStatus::Verified),
        );
    }

    /**
     * The entry that last took the link from verified back to declared. Only
     * evidence recorded after it can verify the link again (0013).
     *
     * @return HasOne<StatusHistory, $this>
     */
    public function lastReversal(): HasOne
    {
        return $this->hasOne(StatusHistory::class)->ofMany(
            ['created_at' => 'max', 'id' => 'max'],
            fn (Builder $query) => $query
                ->where('previous_verification', VerificationStatus::Verified)
                ->where('new_verification', VerificationStatus::Declared),
        );
    }

    /**
     * Scope the query to the links under periodic review: the one rule the
     * review scope, the daily command and the dashboard share (0017). A
     * link is monitorable when it is not cancelled (closed, it owes nothing),
     * verified (the review clock starts at verification, 0018), has a review
     * date, and its system is not in the unacceptable tier (it never
     * operates). A system reclassified as unacceptable keeps its links'
     * dates: they count again if it returns to an operable tier.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function monitorable(Builder $query): void
    {
        $query->notCancelled()
            ->where($query->qualifyColumn('verification_status'), VerificationStatus::Verified)
            ->whereNotNull($query->qualifyColumn('next_review_date'))
            ->whereHas('risk.aiSystem', fn (Builder $aiSystem) => $aiSystem->whereNot('category', AiSystemCategory::Unacceptable));
    }

    /**
     * Scope the query to the monitorable links past their review date. The
     * review date is the last valid day of the verification (0019, item 1):
     * a link due today is still valid, and is reverted from tomorrow on.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function dueForReview(Builder $query): void
    {
        $query->monitorable()
            ->where($query->qualifyColumn('next_review_date'), '<', today());
    }

    /**
     * Scope the query to the links of a system.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ofSystem(Builder $query, int $aiSystemId): void
    {
        $query->whereHas('risk', fn (Builder $risk) => $risk->where('ai_system_id', $aiSystemId));
    }

    /**
     * Scope the query to the links whose risk is in one of the given MIT
     * subdomains.
     *
     * @param  Builder<self>  $query
     * @param  iterable<int>  $subdomainIds
     */
    #[Scope]
    protected function inRiskSubdomains(Builder $query, iterable $subdomainIds): void
    {
        $query->whereHas('risk', fn (Builder $risk) => $risk->whereIn('risk_subdomain_id', $subdomainIds));
    }

    /**
     * Scope the query to the links awaiting reassessment whose last reversal
     * came from the given origin.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function revertedBy(Builder $query, ChangeOrigin $origin): void
    {
        $query->awaitingReassessment()
            ->whereHas('lastReversal', fn (Builder $reversal) => $reversal->where('origin', $origin));
    }

    /**
     * Scope the query to the links that could be verified: still in the
     * chain and of a system that may operate (0018).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function verifiable(Builder $query): void
    {
        $query->notCancelled()
            ->whereHas('risk.aiSystem', fn (Builder $aiSystem) => $aiSystem->whereNot('category', AiSystemCategory::Unacceptable));
    }

    /**
     * Scope the query to the verifiable links never verified yet.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function awaitingFirstVerification(Builder $query): void
    {
        $query->verifiable()
            ->where($query->qualifyColumn('verification_status'), VerificationStatus::Declared)
            ->whereDoesntHave('statusHistories', fn (Builder $entries) => $entries->where('previous_verification', VerificationStatus::Verified));
    }

    /**
     * Scope the query to the links still in the chain that were verified and
     * then reverted: flagged for reassessment (0018, item 1). Unlike the first
     * verification, this includes links of systems reclassified into the
     * unacceptable tier (0019, item 9): they cannot be verified again, but
     * still await the reassessment that decides what to do with them.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function awaitingReassessment(Builder $query): void
    {
        $query->notCancelled()
            ->where($query->qualifyColumn('verification_status'), VerificationStatus::Declared)
            ->whereHas('statusHistories', fn (Builder $entries) => $entries->where('previous_verification', VerificationStatus::Verified));
    }

    /**
     * Scope the query to the verified links still in the chain.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->notCancelled()
            ->where($query->qualifyColumn('verification_status'), VerificationStatus::Verified);
    }

    /**
     * Scope the query to the links still in the chain. A cancelled link is
     * closed, so it owes nothing: no evidence, no review.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function notCancelled(Builder $query): void
    {
        $query->whereNot($query->qualifyColumn('status'), LinkStatus::Cancelled);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lifecycle_phase' => LifecyclePhase::class,
            'status' => LinkStatus::class,
            'verification_status' => VerificationStatus::class,
            'estimated_cost' => CostLevel::class,
            'creation_date' => 'date',
            'next_review_date' => 'date',
        ];
    }
}
