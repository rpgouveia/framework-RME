<?php

use App\Enums\AiSystemCategory;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\UncertaintyLevel;
use Illuminate\Support\Facades\File;

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
        ->and(AiSystemCategory::High->label())->toBe('Alto')
        ->and(LifecyclePhase::Monitoring->label())->toBe('Monitoramento')
        ->and(LinkStatus::Monitoring->label())->toBe('Em monitoramento');
});

test('a case whose name matches a translation file is translated too', function () {
    expect(LifecyclePhase::Validation->label())->toBe('Validação');
});

test('options are shaped for a select input', function (string $enum) {
    $options = $enum::options();

    expect($options)->toHaveCount(count($enum::cases()))
        ->and($options[0])->toHaveKeys(['value', 'label']);
})->with(domainEnums());

test('values returns every backing value', function () {
    expect(UncertaintyLevel::values())->toBe(['low', 'medium', 'high']);
});
