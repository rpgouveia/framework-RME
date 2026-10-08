<?php

namespace App\Http\Requests;

use App\Actions\RecordReassessment;
use App\Enums\CauseStatus;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Models\Link;
use App\Models\Owner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of a reassessment (0020). The preconditions of the link and of
 * each outcome are RecordReassessment's, which checks them again inside the
 * transaction.
 */
class StoreReassessmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $identified = fn (): bool => $this->input('cause_status') === CauseStatus::Identified->value;

        return [
            'outcome' => ['required', Rule::enum(ReassessmentOutcome::class)],
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')->whereNull('deactivated_at')],
            'justification' => ['required', 'string', 'max:2000'],
            // Only for an adverse event or a manual reversal (0020, item 6).
            'cause_status' => [
                Rule::requiredIf(fn (): bool => $this->causeApplies()),
                'nullable',
                Rule::in([CauseStatus::Identified->value, CauseStatus::NotIdentified->value]),
            ],
            'cause' => [Rule::requiredIf($identified), 'nullable', 'string', 'max:2000'],
            'cause_phase' => [Rule::requiredIf($identified), 'nullable', Rule::enum(LifecyclePhase::class)],
            'verify' => ['sometimes', 'boolean'],
            // What an adjustment changes; empty fields stay as they are.
            'changes' => ['sometimes', 'array'],
            'changes.owner_id' => ['nullable', 'integer', Rule::exists(Owner::class, 'id')->whereNull('deactivated_at')],
            'changes.estimated_cost' => ['nullable', Rule::enum(CostLevel::class)],
            'changes.lifecycle_phase' => ['nullable', Rule::enum(LifecyclePhase::class)],
            'changes.status' => ['nullable', Rule::enum(LinkStatus::class)],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'owner_id.exists' => __('Choose an active owner.'),
            'changes.owner_id.exists' => __('Choose an active owner.'),
            'justification.required' => __('Say why the reassessment concluded this.'),
            'cause_status.required' => __('Say whether the cause was identified.'),
            'cause.required' => __('Describe the cause identified.'),
            'cause_phase.required' => __('Choose the lifecycle phase the cause came from.'),
        ];
    }

    /**
     * Whether the reversal being reassessed calls for a cause analysis.
     */
    protected function causeApplies(): bool
    {
        /** @var Link $link */
        $link = $this->route('link');
        $reversal = $link->lastReversal()->first();

        return $reversal !== null && app(RecordReassessment::class)->causeApplies($reversal);
    }
}
