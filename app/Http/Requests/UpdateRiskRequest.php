<?php

namespace App\Http\Requests;

use App\Concerns\RiskValidationRules;
use App\Models\AiSystem;
use App\Models\Risk;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskRequest extends FormRequest
{
    use RiskValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Risk $risk */
        $risk = $this->route('risk');

        return [
            ...$this->riskRules(),
            // Left out, the risk keeps its system. Once the risk has links,
            // cancelled ones included, it belongs to its system's traceability
            // chain and report, so it cannot move to another system.
            'ai_system_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists(AiSystem::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($risk): void {
                    if ((int) $value !== $risk->ai_system_id && $risk->links()->exists()) {
                        $fail(__('The AI system cannot change once the risk has links.'));
                    }
                },
            ],
        ];
    }
}
