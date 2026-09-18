<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait OwnerValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function ownerRules(): array
    {
        return [
            'organizational_role' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
        ];
    }
}
