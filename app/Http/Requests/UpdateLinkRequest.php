<?php

namespace App\Http\Requests;

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Models\Owner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only the follow-up fields of a link can be edited.
 *
 * The risk and mitigation pair is the link's identity, and its evidence and
 * history were recorded for it. The status changes only through the status
 * history, so every change leaves a trail (RF09). The creation date anchors
 * the review date, which the system computes (R-7). Fields left out here are
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
        return [
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')],
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'estimated_cost' => ['required', Rule::enum(CostLevel::class)],
            'observed_cost' => ['nullable', Rule::enum(CostLevel::class)],
        ];
    }
}
