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
            'trigger_reason' => ['nullable', 'string', 'max:255'],
            'change_date' => ['required', 'date'],
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')],
            'adverse_event_id' => ['nullable', 'integer', Rule::exists(AdverseEvent::class, 'id')],
        ];
    }
}
