<?php

namespace App\Concerns;

use App\Models\Owner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

trait OwnerValidationRules
{
    /**
     * Get the validation rules shared by the store and update requests.
     *
     * @return array<string, list<ValidationRule|Closure|string>>
     */
    protected function ownerRules(): array
    {
        /** @var Owner|null $owner The one being updated, if any. */
        $owner = $this->route('owner');

        return [
            'organizational_role' => ['required', 'string', 'max:255', $this->uniqueRoleInArea($owner)],
            'area' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * A role within an area names one owner, ignoring letter case, like the
     * unique index on (lower(organizational_role), lower(area)). When the
     * clash is an inactive owner, point to reactivating it instead.
     */
    protected function uniqueRoleInArea(?Owner $owner): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($owner): void {
            $area = $this->input('area');

            if (! is_string($value) || ! is_string($area)) {
                return;
            }

            $clash = Owner::query()
                ->whereRaw('lower(organizational_role) = lower(?)', [$value])
                ->whereRaw('lower(area) = lower(?)', [$area])
                ->when($owner, fn ($query) => $query->whereKeyNot($owner->id))
                ->first();

            if ($clash === null) {
                return;
            }

            $fail($clash->isActive()
                ? __('An owner with this role already exists in this area.')
                : __('An inactive owner has this role in this area. Reactivate it instead of registering another.'));
        };
    }
}
