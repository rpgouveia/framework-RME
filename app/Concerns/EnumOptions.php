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
     * Get the human readable label for the case.
     *
     * Falls back to the case name; add a `lang/pt.json` entry to translate it.
     */
    public function label(): string
    {
        $label = Str::headline($this->name);

        /*
         * A bare word can collide with a translation group. On a case
         * insensitive filesystem `__('Validation')` resolves to the whole
         * `validation.php` file, so fall back to the label when the
         * translator hands back anything other than a string.
         */
        $translated = __($label);

        return is_string($translated) ? $translated : $label;
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
