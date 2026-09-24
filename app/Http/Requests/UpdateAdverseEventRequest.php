<?php

namespace App\Http\Requests;

use App\Concerns\AdverseEventValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdverseEventRequest extends FormRequest
{
    use AdverseEventValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->adverseEventRules();
    }
}
