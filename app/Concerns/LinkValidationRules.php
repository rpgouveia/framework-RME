<?php

namespace App\Concerns;

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait LinkValidationRules
{
    /**
     * Get the validation rules for creating a link.
     *
     * The status and the creation date are set by the server (CreateLink),
     * and the observed cost is only known later (R-4), so none is asked for.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function linkRules(): array
    {
        return [
            'lifecycle_phase' => ['required', Rule::enum(LifecyclePhase::class)],
            'estimated_cost' => ['required', Rule::enum(CostLevel::class)],
            'risk_id' => ['required', 'integer', Rule::exists(Risk::class, 'id')],
            'mitigation_id' => [
                'required',
                'integer',
                Rule::exists(Mitigation::class, 'id'),
                // R-5 allows many links per risk and per mitigation; R-6 only
                // forbids repeating the same pair.
                Rule::unique(Link::class)
                    ->where('risk_id', $this->input('risk_id')),
            ],
            'owner_id' => ['required', 'integer', Rule::exists(Owner::class, 'id')->whereNull('deactivated_at')],
            // The link this one replaces (0020): of the same risk, cancelled
            // by a reassessment that chose to replace it, and not replaced
            // yet.
            'replaces_link_id' => [
                'nullable',
                'integer',
                Rule::exists(Link::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $problem = self::replacementProblem(Link::query()->find((int) $value), (int) $this->input('risk_id'));

                    if ($problem !== null) {
                        $fail($problem);
                    }
                },
            ],
        ];
    }

    /**
     * Why a link cannot be replaced by a new link of the risk; null when it
     * can.
     */
    public static function replacementProblem(?Link $replaced, int $riskId): ?string
    {
        if ($replaced === null) {
            return null;
        }

        $problem = match (true) {
            $replaced->risk_id !== $riskId => __('The replaced link must be of the same risk.'),
            $replaced->status !== LinkStatus::Cancelled
                || $replaced->reassessments()->where('outcome', ReassessmentOutcome::Replace)->doesntExist() => __('Only a link cancelled by a reassessment that chose to replace it can be replaced.'),
            $replaced->replacedBy()->exists() => __('This link was already replaced.'),
            default => null,
        };

        return is_string($problem) ? $problem : null;
    }

    /**
     * Get the custom messages for the link rules.
     *
     * @return array<string, string>
     */
    protected function linkMessages(): array
    {
        return [
            'mitigation_id.unique' => __('A link between this risk and this mitigation already exists.'),
            'owner_id.exists' => __('Choose an active owner.'),
        ];
    }
}
