<?php

namespace App\Http\Requests;

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Models\Link;
use App\Models\Owner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only the follow-up fields of a link can be edited.
 *
 * The risk and mitigation pair is the link's identity, and its evidence and
 * history were recorded for it. The status changes only through the status
 * history, so every change leaves a trail (RF09), and the verification only
 * through its own actions (0018), which also set the review date (R-7). The
 * observed cost comes from the evidence (0005). Fields left out here are
 * dropped from validated() and so never reach the model.
 */
class UpdateLinkRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Link $link */
        $link = $this->route('link');

        return [
            // An active owner, or the current one: a link may keep an owner
            // that was retired, but not move to one.
            'owner_id' => [
                'required',
                'integer',
                Rule::exists(Owner::class, 'id')->where(
                    fn ($query) => $query->whereNull('deactivated_at')->orWhere('id', $link->owner_id),
                ),
            ],
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'estimated_cost' => ['required', Rule::enum(CostLevel::class)],
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
        ];
    }
}
