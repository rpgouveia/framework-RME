<?php

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Shared presentation helpers for the domain's backed enums.
 *
 * @phpstan-require-implements \BackedEnum
 */
trait EnumOptions
{
    /**
     * Get the key of the case's label in `lang/pt_BR.json`: the enum and the
     * case, such as "Uncertainty Level: High", because a case name like High
     * reads differently in each enum (0022).
     */
    public function labelKey(): string
    {
        return Str::headline(class_basename(self::class)).': '.Str::headline($this->name);
    }

    /**
     * Get the human readable label for the case, falling back to its key.
     */
    public function label(): string
    {
        $label = __($this->labelKey());

        return is_string($label) ? $label : $this->labelKey();
    }

    /**
     * Get every case shaped for a select input.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Get every backing value, handy for building validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
