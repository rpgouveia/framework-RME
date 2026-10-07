<?php

namespace App\Support;

use App\Models\AiSystem;
use App\Models\Risk;
use App\Models\TaxonomyTerm;

/**
 * The monitoring protocol (C3): what may happen to an AI system in production.
 *
 * An adverse event is a risk that materialized, so it is classified by the
 * same MIT AI risk subdomains as the risk register: one or more, never free
 * text and never "other". As the group decided, the EU AI Act tier of the
 * system sets how closely it is monitored (not yet modelled here), and its
 * risk profile sets which events are expected.
 *
 * The risk profile of a system is the set of subdomains of all its risks,
 * linked or not. The event form shows those first; every other subdomain
 * stays available, since an event outside the profile may reveal a risk not
 * yet identified. This is the one place that decides it.
 */
class MonitoringProtocol
{
    /**
     * Every subdomain, grouped by domain and described, for the event form.
     *
     * @return list<array{code: string, name: string, children: list<array<string, mixed>>}>
     */
    public function riskSubdomainOptions(): array
    {
        return array_values(array_map(
            fn (array $domain): array => [...$domain, 'children' => array_values($domain['children'])],
            app(AiRiskDomains::class)->tree(withDescriptions: true),
        ));
    }

    /**
     * The expected subdomains of each system, in taxonomy order, with how
     * many of its risks fall in each. A system with no risks expects none.
     *
     * @param  iterable<AiSystem>  $aiSystems
     * @return array<int, list<array{code: string, risks_count: int}>>
     */
    public function expectedRiskSubdomainsBySystem(iterable $aiSystems): array
    {
        $ids = [];

        foreach ($aiSystems as $aiSystem) {
            $ids[] = $aiSystem->id;
        }

        $profile = array_fill_keys($ids, []);
        $subdomains = app(AiRiskDomains::class)->subdomains()->keyBy('id');

        $counts = Risk::query()
            ->whereIn('ai_system_id', $ids)
            ->selectRaw('ai_system_id, risk_subdomain_id, count(*) as risks_count')
            ->groupBy('ai_system_id', 'risk_subdomain_id')
            ->toBase()
            ->get();

        foreach ($counts as $row) {
            /** @var TaxonomyTerm|null $subdomain */
            $subdomain = $subdomains->get((int) $row->risk_subdomain_id);

            if ($subdomain !== null) {
                $profile[(int) $row->ai_system_id][$subdomain->position] = [
                    'code' => $subdomain->code,
                    'risks_count' => (int) $row->risks_count,
                ];
            }
        }

        return array_map(function (array $expected): array {
            ksort($expected);

            return array_values($expected);
        }, $profile);
    }

    /**
     * The expected subdomains of one system.
     *
     * @return list<array{code: string, risks_count: int}>
     */
    public function expectedRiskSubdomainsFor(AiSystem $aiSystem): array
    {
        return $this->expectedRiskSubdomainsBySystem([$aiSystem])[$aiSystem->id];
    }
}
