<?php

namespace App\Concerns;

use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait LinkValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function linkRules(): array
    {
        return [
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'status' => ['required', Rule::enum(LinkStatus::class)],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'observed_cost' => ['nullable', 'numeric', 'min:0'],
            'creation_date' => ['required', 'date'],
            'next_review_date' => ['required', 'date', 'after_or_equal:creation_date'],
            'risk_id' => ['required', 'integer', Rule::exists(Risk::class, 'id')],
            'mitigation_id' => ['required', 'integer', Rule::exists(Mitigation::class, 'id')],
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')],
        ];
    }
}
