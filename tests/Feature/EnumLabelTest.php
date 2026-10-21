<?php

use App\Concerns\EnumOptions;
use App\Enums\AiSystemCategory;
use App\Enums\CostLevel;
use App\Enums\UncertaintyLevel;
use Illuminate\Support\Facades\File;

/**
 * A case named like a translation file (`lang/pt_BR/validation.php`), which no
 * domain enum has: a bare `__('Validation')` could resolve to the whole file.
 */
enum TranslationFileCase: string
{
    use EnumOptions;

    case Validation = 'validation';
}

/**
 * Every enum of the domain, keyed by its name. Read from the directory, so a
 * new enum is covered without being listed.
 *
 * @return array<string, array{class-string}>
 */
function domainEnums(): array
{
    $enums = [];

    foreach (glob(__DIR__.'/../../app/Enums/*.php') as $path) {
        $name = basename($path, '.php');
        $enums[$name] = ['App\\Enums\\'.$name];
    }

    return $enums;
}

test('every case of every enum is translated', function (string $enum) {
    $lines = json_decode(File::get(lang_path('pt_BR.json')), true);

    foreach ($enum::cases() as $case) {
        expect($lines)->toHaveKey($case->labelKey())
            ->and($case->label())->toBe($lines[$case->labelKey()]);
    }
})->with(domainEnums());

test('the key names the enum, so a case name reads differently in each one', function () {
    expect(UncertaintyLevel::High->labelKey())->toBe('Uncertainty Level: High')
        ->and(UncertaintyLevel::High->label())->toBe('Alta')
        ->and(CostLevel::High->label())->toBe('Alto')
        ->and(AiSystemCategory::High->label())->toBe('Alto');
});

test('a case whose name matches a translation file is translated too', function () {
    app('translator')->addLines(['*.Translation File Case: Validation' => 'Validação'], app()->getLocale());

    expect(TranslationFileCase::Validation->label())->toBe('Validação');
});

test('options are shaped for a select input', function (string $enum) {
    $options = $enum::options();

    expect($options)->toHaveCount(count($enum::cases()))
        ->and($options[0])->toHaveKeys(['value', 'label']);
})->with(domainEnums());

test('values returns every backing value', function () {
    expect(UncertaintyLevel::values())->toBe(['low', 'medium', 'high']);
});
