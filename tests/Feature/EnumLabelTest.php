<?php

use App\Enums\AiSystemCategory;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\RiskCategory;
use App\Enums\SaeriCategory;
use App\Enums\SystemSourceType;
use App\Enums\UncertaintyLevel;

/** @return array<int, class-string> */
function domainEnums(): array
{
    return [
        AiSystemCategory::class,
        EvidenceType::class,
        LifecyclePhase::class,
        LinkStatus::class,
        RiskCategory::class,
        SaeriCategory::class,
        SystemSourceType::class,
        UncertaintyLevel::class,
    ];
}

test('every case has a readable label', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
})->with(domainEnums());

test('options are shaped for a select input', function (string $enum) {
    $options = $enum::options();

    expect($options)->toHaveCount(count($enum::cases()))
        ->and($options[0])->toHaveKeys(['value', 'label']);
})->with(domainEnums());

test('a case whose name matches a translation file still returns a string', function () {
    expect(LifecyclePhase::Validation->label())->toBe('Validation');
});

test('values returns every backing value', function () {
    expect(UncertaintyLevel::values())->toBe(['low', 'medium', 'high']);
});
