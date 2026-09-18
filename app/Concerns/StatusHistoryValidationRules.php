<?php

namespace App\Concerns;

use App\Enums\LinkStatus;
use App\Models\Owner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait StatusHistoryValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function statusHistoryRules(): array
    {
        return [
            'previous_status' => ['required', Rule::enum(LinkStatus::class)],
            'new_status' => ['required', Rule::enum(LinkStatus::class), 'different:previous_status'],
            'change_date' => ['required', 'date'],
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')],
        ];
    }
}
