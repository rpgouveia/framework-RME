<?php

namespace App\Concerns;

use App\Enums\CostLevel;
use App\Enums\SaeriCategory;
use App\Enums\UncertaintyLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait MitigationValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function mitigationRules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'saeri_category' => ['required', Rule::enum(SaeriCategory::class)],
            'suggested_target_risk' => ['required', 'string', 'max:2000'],
            'expected_evidence' => ['required', 'string', 'max:2000'],
            'suggested_cost' => ['required', Rule::enum(CostLevel::class)],
            'uncertainty_level' => ['required', Rule::enum(UncertaintyLevel::class)],
            'bibliography_source' => ['required', 'string', 'max:255'],
        ];
    }
}
