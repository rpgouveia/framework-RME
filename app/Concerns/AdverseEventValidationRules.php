<?php

namespace App\Concerns;

use App\Enums\AdverseEventType;
use App\Models\AiSystem;
use App\Support\MonitoringProtocol;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AdverseEventValidationRules
{
    /**
     * Get the validation rules for recording an adverse event.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function adverseEventRules(): array
    {
        return [
            'event_type' => [
                'required',
                Rule::enum(AdverseEventType::class),
                // RF04: the type must be one the monitoring protocol allows
                // for the chosen system.
                function (string $attribute, mixed $value, Closure $fail): void {
                    $type = AdverseEventType::tryFrom((string) $value);
                    $aiSystemId = $this->input('ai_system_id');
                    $aiSystem = is_numeric($aiSystemId) ? AiSystem::find((int) $aiSystemId) : null;

                    if ($type === null || $aiSystem === null) {
                        return;
                    }

                    if (! app(MonitoringProtocol::class)->allows($aiSystem, $type)) {
                        $fail(__('This event type does not apply to this AI system.'));
                    }
                },
            ],
            'description' => ['required', 'string', 'max:2000'],
            'occurrence_date' => ['required', 'date', 'before_or_equal:today'],
            'ai_system_id' => ['required', 'integer', Rule::exists(AiSystem::class, 'id')],
        ];
    }
}
