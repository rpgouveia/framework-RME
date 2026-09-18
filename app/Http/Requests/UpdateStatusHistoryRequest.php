<?php

namespace App\Http\Requests;

use App\Concerns\StatusHistoryValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusHistoryRequest extends FormRequest
{
    use StatusHistoryValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->statusHistoryRules();
    }
}
