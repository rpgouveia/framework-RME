<?php

namespace App\Concerns;

use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait EvidenceValidationRules
{
    /**
     * Get the validation rules for registering evidence.
     *
     * The registration date is not asked for: the system stamps it when the
     * evidence is registered (Tela 3), so re-verification can trust that the
     * evidence came after the link was last reverted.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function evidenceRules(): array
    {
        return [
            'type' => ['required', Rule::enum(EvidenceType::class)],
            'description' => ['required', 'string', 'max:255'],
            // Optional (RF07): the latest evidence that reports it is the
            // link's observed cost (0018).
            'observed_cost' => ['nullable', Rule::enum(CostLevel::class)],
        ];
    }
}
