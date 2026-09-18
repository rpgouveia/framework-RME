<?php

namespace App\Concerns;

use App\Enums\AiSystemCategory;
use App\Enums\SystemSourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AiSystemValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function aiSystemRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'source_type' => ['required', Rule::enum(SystemSourceType::class)],
            'category' => ['required', Rule::enum(AiSystemCategory::class)],
            'registration_date' => ['required', 'date'],
        ];
    }
}
