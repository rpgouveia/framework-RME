<?php

namespace App\Actions;

use App\Enums\AiSystemCategory;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\SystemChange;
use App\Support\AiRiskDomains;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Record a change of an AI system and the reassessment it triggers (0021):
 * in the same transaction, the system's verified links still in the chain go
 * back to declared, those whose risk is in one of the affected subdomains
 * when any is given, all of them otherwise, since without an analysis of its
 * reach no evidence can be said to still hold. Every reversal goes through
 * RecordStatusChange (0007), with no author, the origin of the change's type
 * and the change attached. A system in the unacceptable tier has the change
 * recorded and nothing reverted.
 */
class RecordSystemChange
{
    public function __construct(
        protected RecordStatusChange $recordStatusChange,
        protected AiRiskDomains $riskDomains,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  The validated fields, with the
     *                                            subdomain codes, if any, in
     *                                            risk_subdomains.
     */
    public function handle(AiSystem $aiSystem, array $attributes): SystemChange
    {
        return DB::transaction(function () use ($aiSystem, $attributes): SystemChange {
            $systemChange = $aiSystem->systemChanges()->create([
                'type' => $attributes['type'],
                'description' => $attributes['description'],
                'change_date' => $attributes['change_date'],
            ]);

            /** @var list<string> $codes */
            $codes = $attributes['risk_subdomains'] ?? [];
            /** @var list<int> $subdomainIds */
            $subdomainIds = $this->riskDomains->subdomains()->whereIn('code', $codes)->pluck('id')->values()->all();

            $systemChange->riskSubdomains()->attach($subdomainIds);

            // A system that may not operate keeps no verification to revert.
            if ($aiSystem->category === AiSystemCategory::Unacceptable) {
                return $systemChange;
            }

            // Worked out here, inside the transaction, whatever the form
            // announced.
            foreach ($this->linksToRevert($aiSystem->id, $subdomainIds)->get() as $link) {
                $this->recordStatusChange->revert(
                    $link,
                    $systemChange->type->origin(),
                    reason: __(':type of :date.', [
                        'type' => $systemChange->type->label(),
                        'date' => $systemChange->change_date->format('d/m/Y'),
                    ]),
                    systemChange: $systemChange,
                );
            }

            return $systemChange;
        });
    }

    /**
     * The links a change reverts: verified, not cancelled, of the system, and
     * with the risk in one of the subdomains, when any is given.
     *
     * @param  list<int>  $subdomainIds
     * @return Builder<Link>
     */
    public function linksToRevert(int $aiSystemId, array $subdomainIds): Builder
    {
        return Link::query()
            ->verified()
            ->ofSystem($aiSystemId)
            ->when($subdomainIds !== [], fn (Builder $query) => $query->inRiskSubdomains($subdomainIds))
            ->orderBy('id');
    }
}
