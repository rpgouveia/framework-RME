<?php

namespace App\Concerns;

use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\Owner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait StatusHistoryValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * The previous status is optional because the first entry of a trail has
     * nothing before it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function statusHistoryRules(): array
    {
        return [
            'previous_status' => ['nullable', Rule::enum(LinkStatus::class)],
            'new_status' => ['required', Rule::enum(LinkStatus::class), 'different:previous_status'],
            // Links are closed by cancelling them, never deleted, so the
            // reason is what explains why the chain stopped.
            'trigger_reason' => [
                Rule::requiredIf(fn (): bool => $this->input('new_status') === LinkStatus::Cancelled->value),
                'nullable',
                'string',
                'max:255',
            ],
            'change_date' => ['required', 'date'],
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')],
            'adverse_event_id' => ['nullable', 'integer', Rule::exists(AdverseEvent::class, 'id')],
        ];
    }

    /**
     * Get the custom messages shared by the store and update requests.
     *
     * @return array<string, string>
     */
    protected function statusHistoryMessages(): array
    {
        return [
            'trigger_reason.required' => __('Say why the link is being cancelled.'),
        ];
    }
}
