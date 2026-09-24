<?php

namespace App\Concerns;

use App\Enums\AdverseEventType;
use App\Models\AiSystem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AdverseEventValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function adverseEventRules(): array
    {
        return [
            'event_type' => ['required', Rule::enum(AdverseEventType::class)],
            'description' => ['required', 'string', 'max:2000'],
            'occurrence_date' => ['required', 'date', 'before_or_equal:today'],
            'ai_system_id' => ['required', 'integer', Rule::exists(AiSystem::class, 'id')],
        ];
    }
}
