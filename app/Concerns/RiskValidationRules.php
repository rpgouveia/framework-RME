<?php

namespace App\Concerns;

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Risk;
use App\Rules\UniqueNameIgnoringCase;
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
            'category' => ['required', Rule::enum(RiskCategory::class)],
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'uncertainty_level' => ['required', Rule::enum(UncertaintyLevel::class)],
            'ai_system_id' => ['required', 'integer', Rule::exists(AiSystem::class, 'id')],
        ];
    }
}
