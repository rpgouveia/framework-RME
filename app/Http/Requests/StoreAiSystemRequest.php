<?php

namespace App\Http\Requests;

use App\Concerns\AiSystemValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAiSystemRequest extends FormRequest
{
    use AiSystemValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->aiSystemRules();
    }
}
