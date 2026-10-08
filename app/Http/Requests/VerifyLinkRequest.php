<?php

namespace App\Http\Requests;

use App\Models\Owner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Who verifies the link: one active owner, a role and not a person (0018).
 * The other preconditions are the link's own, checked by RecordStatusChange.
 */
class VerifyLinkRequest extends FormRequest
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
