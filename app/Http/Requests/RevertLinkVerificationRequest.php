<?php

namespace App\Http\Requests;

use App\Models\Owner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A manual reversal names who reverts and why (0018, item 7).
 */
class RevertLinkVerificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')->whereNull('deactivated_at')],
            'trigger_reason' => ['required', 'string', 'max:255'],
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
            'trigger_reason.required' => __('Say why the verification is being reverted.'),
        ];
    }
}
