<?php

namespace App\Http\Requests;

use App\Concerns\LinkValidationRules;
use App\Models\Link;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    use LinkValidationRules;

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
            ...$this->linkRules($link),
            'next_review_date' => ['required', 'date', 'after_or_equal:creation_date'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->linkMessages();
    }
}
