<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The name must not already be taken, ignoring case.
 *
 * Laravel's unique rule compares with "=", which is case sensitive on
 * PostgreSQL. This one lowers both sides in the database, the same way the
 * unique indexes on lower(name) do, so validation and index agree.
 */
class UniqueNameIgnoringCase implements ValidationRule
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $scope  Columns the name must be unique within.
     * @param  Model|null  $ignore  The record being updated.
     */
    public function __construct(
        protected string $model,
        protected string $message,
        protected array $scope = [],
        protected ?Model $ignore = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Leave type errors to the string rule.
        if (! is_string($value)) {
            return;
        }

        /** @var Builder<Model> $query */
        $query = $this->model::query()
            ->whereRaw('lower(name) = lower(?)', [$value])
            ->where($this->scope);

        if ($this->ignore !== null) {
            $query->whereKeyNot($this->ignore->getKey());
        }

        if ($query->exists()) {
            $fail($this->message);
        }
    }
}
