<?php

namespace App\Concerns;

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait LinkValidationRules
{
    /**
     * Get the validation rules for creating a link.
     *
     * The status and the creation date are set by the server (CreateLink),
     * and the observed cost is only known later (R-4), so none is asked for.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function linkRules(): array
    {
        return [
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'estimated_cost' => ['required', Rule::enum(CostLevel::class)],
            'risk_id' => ['required', 'integer', Rule::exists(Risk::class, 'id')],
            'mitigation_id' => [
                'required',
                'integer',
                Rule::exists(Mitigation::class, 'id'),
                // R-5 allows many links per risk and per mitigation; R-6 only
                // forbids repeating the same pair.
                Rule::unique(Link::class)
                    ->where('risk_id', $this->input('risk_id')),
            ],
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')->whereNull('deactivated_at')],
        ];
    }

    /**
     * Get the custom messages for the link rules.
     *
     * @return array<string, string>
     */
    protected function linkMessages(): array
    {
        return [
            'mitigation_id.unique' => __('A link between this risk and this mitigation already exists.'),
            'owner_id.exists' => __('Choose an active owner.'),
        ];
    }
}
