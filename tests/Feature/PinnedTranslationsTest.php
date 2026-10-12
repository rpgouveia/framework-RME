<?php

use Illuminate\Support\Arr;

/** @return array{json: array<string, string>, validation: array<string, string>} */
function pinnedTranslations(): array
{
    return require __DIR__.'/../Fixtures/pinned-translations.php';
}

/**
 * The lines that depart from every text Laravel Lang has for the same key.
 *
 * @param  array<string, string>  $lines
 * @param  array<int, string>  $sources  the Laravel Lang locale files to compare with
 * @return array<string, string>
 */
function departuresFromLaravelLang(array $lines, array $sources): array
{
    $texts = [];

    foreach ($sources as $source) {
        foreach (json_decode(file_get_contents($source), true) as $key => $text) {
            $texts[$key][] = $text;
        }
    }

    return array_filter(
        $lines,
        fn (string $text, string $key): bool => isset($texts[$key]) && ! in_array($text, $texts[$key], true),
        ARRAY_FILTER_USE_BOTH,
    );
}

test('a pinned text keeps the wording of the project', function (string $key, string $text) {
    expect(__($key, [], 'pt_BR'))->toBe($text);
})->with(function (): array {
    $pinned = pinnedTranslations();

    $validation = collect($pinned['validation'])->mapWithKeys(
        fn (string $text, string $rule): array => ["validation.$rule" => $text]
    );

    return collect($pinned['json'])->merge($validation)
        ->map(fn (string $text, string $key): array => [$key, $text])
        ->all();
});

test('every text that departs from laravel lang is pinned', function () {
    $sources = base_path('vendor/laravel-lang');

    if (! is_dir($sources)) {
        $this->markTestSkipped('Laravel Lang is a development dependency.');
    }

    $pinned = pinnedTranslations();

    $json = departuresFromLaravelLang(
        json_decode(file_get_contents(lang_path('pt_BR.json')), true),
        glob("$sources/*/locales/pt_BR/json*.json"),
    );

    $validation = departuresFromLaravelLang(
        Arr::dot(require lang_path('pt_BR/validation.php')),
        [...glob("$sources/lang/locales/pt_BR/php*.json"), ...glob("$sources/attributes/locales/pt_BR/php*.json")],
    );

    expect(array_diff_key($json, $pinned['json']))->toBe([])
        ->and(array_diff_key($validation, $pinned['validation']))->toBe([]);
});
