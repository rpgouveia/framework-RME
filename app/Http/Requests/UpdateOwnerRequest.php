<?php

namespace App\Http\Requests;

use App\Concerns\OwnerValidationRules;
use App\Models\Owner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnerRequest extends FormRequest
{
    use OwnerValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Owner $owner */
        $owner = $this->route('owner');

        $rules = $this->ownerRules();

        // The status trail keeps the owner's id, so renaming a used owner
        // would change, after the fact, who each entry says made it.
        if ($owner->isUsed()) {
            $rules['organizational_role'][] = $this->unchanged($owner->organizational_role);
            $rules['area'][] = $this->unchanged($owner->area);
        }

        return $rules;
    }

    /**
     * The field must keep its current value, letter case included.
     */
    protected function unchanged(string $current): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($current): void {
            if ($value !== $current) {
                $fail(__('The role and area cannot change once the owner is used in the traceability chain. Register a new owner and reassign the active links.'));
            }
        };
    }
}
