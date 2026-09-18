<?php

namespace App\Concerns;

use App\Enums\SaeriCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait MitigationValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function mitigationRules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'saeri_category' => ['required', Rule::enum(SaeriCategory::class)],
        ];
    }
}
