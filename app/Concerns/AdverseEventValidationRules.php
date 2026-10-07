<?php

namespace App\Concerns;

use App\Models\AiSystem;
use App\Support\AiRiskDomains;
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
            // Only an occurrence tied to at least one MIT risk subdomain is an
            // adverse event; there is no "other".
            'risk_subdomains' => ['required', 'list', 'min:1'],
            'risk_subdomains.*' => [
                'string',
                'distinct',
                Rule::in(app(AiRiskDomains::class)->subdomains()->pluck('code')->all()),
                // RF04: the subdomain must be one the monitoring protocol
                // offers for the chosen system.
                function (string $attribute, mixed $value, Closure $fail): void {
                    $aiSystemId = $this->input('ai_system_id');
                    $aiSystem = is_numeric($aiSystemId) ? AiSystem::find((int) $aiSystemId) : null;

                    if ($aiSystem === null || ! is_string($value)) {
                        return;
                    }

                    if (! app(MonitoringProtocol::class)->allows($aiSystem, $value)) {
                        $fail(__('The risk subdomain :code is not offered for this AI system.', ['code' => $value]));
                    }
                },
            ],
            'description' => ['required', 'string', 'max:2000'],
            'occurrence_date' => ['required', 'date', 'before_or_equal:today'],
            'ai_system_id' => ['required', 'integer', Rule::exists(AiSystem::class, 'id')],
        ];
    }

    /**
     * Get the messages for the risk subdomain rules.
     *
     * @return array<string, string>
     */
    protected function adverseEventMessages(): array
    {
        return [
            'risk_subdomains.required' => __('Select at least one risk subdomain the event materializes.'),
            'risk_subdomains.min' => __('Select at least one risk subdomain the event materializes.'),
            'risk_subdomains.list' => __('Select at least one risk subdomain the event materializes.'),
            'risk_subdomains.*.in' => __('The code :input is not a subdomain of the MIT AI risk domain taxonomy.'),
            'risk_subdomains.*.distinct' => __('The risk subdomain :input was selected more than once.'),
        ];
    }
}
