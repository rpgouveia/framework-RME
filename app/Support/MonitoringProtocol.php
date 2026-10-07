<?php

namespace App\Support;

use App\Models\AiSystem;
use App\Models\TaxonomyTerm;
use Illuminate\Database\Eloquent\Collection;

/**
 * The monitoring protocol (C3): what may happen to an AI system in production.
 *
 * An adverse event is a risk that materialized, so it is classified by the
 * same MIT AI risk subdomains as the risk register: one or more, never free
 * text and never "other". This is the one place that decides which
 * subdomains a system offers (RF04): the create form shows them and
 * validation enforces them.
 *
 * @todo Every subdomain is offered for every system for now. Ordering them
 *       by the system's risk profile depends on a decision still open, the
 *       class of the system (EU AI Act tier or application domain), and is
 *       left out on purpose. Once it is settled, change it here; the form and
 *       the validation need no change.
 */
class MonitoringProtocol
{
    /**
     * The subdomains offered for a system, in taxonomy order.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function riskSubdomainsFor(AiSystem $aiSystem): Collection
    {
        return app(AiRiskDomains::class)->subdomains();
    }

    public function allows(AiSystem $aiSystem, string $code): bool
    {
        return in_array($code, $this->riskSubdomainsFor($aiSystem)->pluck('code')->all(), true);
    }

    /**
     * The subdomains a system offers, grouped by domain for the form, each
     * with its description to help the classification.
     *
     * @return list<array{code: string, name: string, children: list<array<string, mixed>>}>
     */
    public function riskSubdomainOptionsFor(AiSystem $aiSystem): array
    {
        $offered = $this->riskSubdomainsFor($aiSystem)->pluck('code')->all();
        $domains = [];

        foreach (app(AiRiskDomains::class)->tree(withDescriptions: true) as $domain) {
            $children = array_values(array_filter(
                $domain['children'],
                fn (array $subdomain): bool => in_array($subdomain['code'], $offered, true),
            ));

            if ($children !== []) {
                $domains[] = [...$domain, 'children' => $children];
            }
        }

        return $domains;
    }
}
