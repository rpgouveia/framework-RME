<?php

namespace App\Http\Requests;

use App\Concerns\LinkValidationRules;
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
        return [
            ...$this->linkRules(),
            'next_review_date' => ['required', 'date', 'after_or_equal:creation_date'],
        ];
    }
}
