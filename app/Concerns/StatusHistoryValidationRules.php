<?php

namespace App\Concerns;

use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Owner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait StatusHistoryValidationRules
{
    /**
     * Get the validation rules for recording a status change.
     *
     * The previous status is not asked for: it is the link's current status,
     * read when the change is recorded.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function statusHistoryRules(): array
    {
        /** @var Link $link */
        $link = $this->route('link');

        return [
            'new_status' => [
                'required',
                Rule::enum(LinkStatus::class),
                function (string $attribute, mixed $value, Closure $fail) use ($link): void {
                    $status = LinkStatus::tryFrom((string) $value);

                    if ($status === null || $link->status->canTransitionTo($status)) {
                        return;
                    }

                    $fail($link->status === LinkStatus::Cancelled
                        ? __('A cancelled link can only be reactivated, going back to planned.')
                        : __('The new status must differ from the current one.'));
                },
            ],
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
     * Get the custom messages for the status change rules.
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
