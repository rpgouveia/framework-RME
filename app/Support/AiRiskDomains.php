<?php

namespace App\Support;

use App\Models\TaxonomyTerm;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Domain Taxonomy of the MIT AI Risk Repository (Slattery et al.): seven
 * domains (level 1) and 24 subdomains (level 2). Risks are classified by
 * subdomain, and catalogue mitigations name the subdomains they treat, the
 * mapping Saeri et al. call the next critical step.
 */
class AiRiskDomains extends ReferenceTaxonomy
{
    public const KEY = 'mit-ai-risk-domains';

    public static function key(): string
    {
        return self::KEY;
    }

    public static function path(): string
    {
        return database_path('data/taxonomies/mit-ai-risk-domains.json');
    }

    /**
     * The domains, in order, each with its subdomains.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function domains(): Collection
    {
        return $this->topLevel();
    }

    /**
     * The subdomains, the level risks are classified at.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function subdomains(): Collection
    {
        return $this->secondLevel();
    }
}
