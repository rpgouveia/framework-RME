<?php

namespace Database\Factories\Concerns;

/**
 * Hands out names that are not taken yet, so factories respect the unique
 * name indexes.
 *
 * A name is taken when a stored record in the same scope already has it, in
 * any letter case, or when this factory already gave it to another model of
 * the batch: create() makes every model before storing any, so the siblings
 * are not in the database yet. When the list runs out, a number is added.
 */
trait PicksUnusedNames
{
    /**
     * Names this factory instance handed out, per scope.
     *
     * @var array<string, list<string>>
     */
    protected array $namesInBatch = [];

    /**
     * @param  list<string>  $names  The names to pick from.
     * @param  array<mixed>  $taken  The names already stored in the scope.
     */
    protected function unusedName(array $names, array $taken, string $scope = ''): string
    {
        $used = array_map(
            fn (mixed $name): string => mb_strtolower((string) $name),
            [...$taken, ...($this->namesInBatch[$scope] ?? [])],
        );

        $free = array_values(array_filter(
            $names,
            fn (string $name): bool => ! in_array(mb_strtolower($name), $used, true),
        ));

        $name = $free !== [] ? fake()->randomElement($free) : $this->numbered($names, $used);

        $this->namesInBatch[$scope][] = $name;

        return $name;
    }

    /**
     * @param  list<string>  $names
     * @param  array<string>  $used  Lowercased names already taken.
     */
    protected function numbered(array $names, array $used): string
    {
        $base = fake()->randomElement($names);

        for ($number = 2; in_array(mb_strtolower("{$base} {$number}"), $used, true); $number++);

        return "{$base} {$number}";
    }
}
