<?php

namespace App\Http\Requests;

use App\Concerns\MitigationValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMitigationRequest extends FormRequest
{
    use MitigationValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->mitigationRules();
    }
}
