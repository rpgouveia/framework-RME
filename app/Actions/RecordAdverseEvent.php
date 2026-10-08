<?php

namespace App\Actions;

use App\Enums\ChangeOrigin;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Support\AiRiskDomains;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Record an adverse event and run the directed reassessment it triggers
 * (0019, item 3): in the same transaction, the verified links of the system
 * whose risk is in one of the event's subdomains go back to declared, except
 * the link that intercepted a near miss. Every reversal goes through
 * RecordStatusChange (0007), with no author and the event attached.
 */
class RecordAdverseEvent
{
    public function __construct(
        protected RecordStatusChange $recordStatusChange,
        protected AiRiskDomains $riskDomains,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  The validated fields, with the
     *                                            subdomain codes in risk_subdomains.
     */
    public function handle(array $attributes): AdverseEvent
    {
        return DB::transaction(function () use ($attributes): AdverseEvent {
            $adverseEvent = AdverseEvent::create(Arr::except($attributes, 'risk_subdomains'));

            /** @var list<string> $codes */
            $codes = $attributes['risk_subdomains'];
            /** @var list<int> $subdomainIds */
            $subdomainIds = $this->riskDomains->subdomains()->whereIn('code', $codes)->pluck('id')->values()->all();

            $adverseEvent->riskSubdomains()->attach($subdomainIds);

            // The list is worked out here, inside the transaction, whatever
            // the form announced.
            $links = $this->linksToRevert($adverseEvent->ai_system_id, $subdomainIds, $adverseEvent->intercepting_link_id)->get();

            foreach ($links as $link) {
                $this->recordStatusChange->revert(
                    $link,
                    ChangeOrigin::AdverseEvent,
                    reason: __('Adverse event of :date in a subdomain of this link\'s risk.', [
                        'date' => $adverseEvent->occurrence_date->format('d/m/Y'),
                    ]),
                    adverseEvent: $adverseEvent,
                );
            }

            return $adverseEvent;
        });
    }

    /**
     * The links an event on these subdomains reverts: verified, not
     * cancelled, of the system, with the risk in one of the subdomains, and
     * not the one that intercepted it.
     *
     * @param  list<int>  $subdomainIds
     * @return Builder<Link>
     */
    public function linksToRevert(int $aiSystemId, array $subdomainIds, ?int $interceptingLinkId = null): Builder
    {
        return Link::query()
            ->verified()
            ->ofSystem($aiSystemId)
            ->inRiskSubdomains($subdomainIds)
            ->when($interceptingLinkId !== null, fn (Builder $query) => $query->whereKeyNot($interceptingLinkId))
            ->orderBy('id');
    }
}
