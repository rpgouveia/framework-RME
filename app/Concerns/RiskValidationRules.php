<?php

namespace App\Concerns;

use App\Enums\LifecyclePhase;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Risk;
use App\Models\TaxonomyTerm;
use App\Rules\UniqueNameIgnoringCase;
use App\Support\AiRiskDomains;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait RiskValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function riskRules(): array
    {
        /** @var Risk|null $risk The one being updated, if any. */
        $risk = $this->route('risk');

        return [
            // A name identifies a type of risk, which may recur across AI
            // systems but only once within the same one.
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueNameIgnoringCase(
                    Risk::class,
                    __('This AI system already has a risk with this name.'),
                    // An update may leave the system out, keeping the current one.
                    scope: ['ai_system_id' => $this->input('ai_system_id', $risk?->ai_system_id)],
                    ignore: $risk,
                ),
            ],
            'description' => ['required', 'string', 'max:2000'],
            // A subdomain (level 2) of the MIT AI risk domains: a domain, or a
            // term of another taxonomy, is refused.
            'risk_subdomain_id' => [
                'required',
                'integer',
                Rule::exists(TaxonomyTerm::class, 'id')
                    ->where('level', 2)
                    ->where('taxonomy_id', app(AiRiskDomains::class)->taxonomy()->id),
            ],
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'uncertainty_level' => ['required', Rule::enum(UncertaintyLevel::class)],
            'ai_system_id' => ['required', 'integer', Rule::exists(AiSystem::class, 'id')],
        ];
    }

    /**
     * Get the custom messages shared by the store and update requests.
     *
     * @return array<string, string>
     */
    protected function riskMessages(): array
    {
        return [
            'risk_subdomain_id.exists' => __('Choose a subdomain of the MIT AI risk domain taxonomy.'),
        ];
    }
}
