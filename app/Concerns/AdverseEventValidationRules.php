<?php

namespace App\Concerns;

use App\Enums\AdverseEventNature;
use App\Enums\LinkStatus;
use App\Models\AiSystem;
use App\Models\Link;
use App\Support\AiRiskDomains;
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
                // Any subdomain, in the system's risk profile or not: one
                // outside it may reveal a risk not yet identified (see
                // MonitoringProtocol).
                Rule::in(app(AiRiskDomains::class)->subdomains()->pluck('code')->all()),
            ],
            // Incident or near miss; both trigger the reassessment (0019).
            'nature' => ['required', Rule::enum(AdverseEventNature::class)],
            'description' => ['required', 'string', 'max:2000'],
            'occurrence_date' => ['required', 'date', 'before_or_equal:today'],
            // Optional: when monitoring noticed it (0019, item 8).
            'detected_at' => ['nullable', 'date', 'after_or_equal:occurrence_date', 'before_or_equal:today'],
            'ai_system_id' => ['required', 'integer', Rule::exists(AiSystem::class, 'id')],
            // Only a near miss names the link that intercepted it (0019,
            // item 6): of the same system, still in the chain, and with its
            // risk in one of the event's subdomains.
            'intercepting_link_id' => [
                'nullable',
                'integer',
                Rule::prohibitedIf(fn (): bool => $this->input('nature') !== AdverseEventNature::NearMiss->value),
                Rule::exists(Link::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $link = Link::query()->with('risk.riskSubdomain')->find((int) $value);

                    if ($link === null) {
                        return;
                    }

                    if ($link->risk->ai_system_id !== (int) $this->input('ai_system_id')) {
                        $fail(__('The intercepting link must belong to the system of the event.'));
                    } elseif ($link->status === LinkStatus::Cancelled) {
                        $fail(__('A cancelled link cannot have intercepted the event.'));
                    } elseif (! in_array($link->risk->riskSubdomain->code, (array) $this->input('risk_subdomains', []), true)) {
                        $fail(__('The risk of the intercepting link must be in one of the subdomains of the event.'));
                    }
                },
            ],
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
            'detected_at.after_or_equal' => __('The detection date cannot be before the occurrence.'),
            'detected_at.before_or_equal' => __('The detection date cannot be in the future.'),
            'intercepting_link_id.prohibited' => __('Only a near miss can name the link that intercepted it.'),
        ];
    }
}
