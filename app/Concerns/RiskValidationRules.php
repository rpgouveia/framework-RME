<?php

namespace App\Concerns;

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
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
        return [
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(RiskCategory::class)],
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'uncertainty_level' => ['required', Rule::enum(UncertaintyLevel::class)],
            'ai_system_id' => ['required', 'integer', Rule::exists(AiSystem::class, 'id')],
        ];
    }
}
