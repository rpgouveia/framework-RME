<?php

namespace App\Concerns;

use App\Enums\EvidenceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait EvidenceValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function evidenceRules(): array
    {
        return [
            'type' => ['required', Rule::enum(EvidenceType::class)],
            'description' => ['required', 'string', 'max:255'],
            'registration_date' => ['required', 'date'],
        ];
    }
}
