<?php

use Illuminate\Support\Facades\File;

/**
 * The keys the frontend looks up through `t()` and `tc()`, by file.
 *
 * A key is the first argument when it is a literal; generated code and the
 * tests of the translator itself are left out.
 *
 * @return array<string, array<int, string>>
 */
function frontendTranslationKeys(): array
{
    $keys = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        $path = $file->getRelativePathname();

        if (preg_match('#^(actions|routes|wayfinder)/|\.test\.ts$#', $path) || ! preg_match('/\.tsx?$/', $path)) {
            continue;
        }

        preg_match_all('/(?<![\w.$])tc?\(\s*([\'"`])((?:\\\\.|(?!\1).)*)\1/s', $file->getContents(), $matches);

        foreach ($matches[2] as $key) {
            $keys[$path][] = stripcslashes($key);
        }
    }

    return $keys;
}

test('every line the frontend looks up is translated', function () {
    $lines = json_decode(File::get(lang_path('pt_BR.json')), true);

    $missing = collect(frontendTranslationKeys())
        ->map(fn (array $keys): array => array_values(array_unique(array_filter(
            $keys,
            fn (string $key): bool => ! array_key_exists($key, $lines),
        ))))
        ->filter()
        ->all();

    expect($missing)->toBe([]);
});

test('a key is a literal, not an interpolated template', function () {
    $interpolated = collect(frontendTranslationKeys())
        ->map(fn (array $keys): array => array_values(array_filter(
            $keys,
            fn (string $key): bool => str_contains($key, '${'),
        )))
        ->filter()
        ->all();

    expect($interpolated)->toBe([]);
});

test('the keys are found in every way the frontend writes them', function () {
    expect(frontendTranslationKeys())
        ->toHaveKey('components/app-sidebar.tsx')
        ->and(frontendTranslationKeys()['components/app-sidebar.tsx'])->toContain('AI systems');
});
