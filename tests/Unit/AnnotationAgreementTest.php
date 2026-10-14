<?php

use App\Support\AnnotationAgreement;

/**
 * Pairs from a compact notation: items separated by semicolons, each the
 * codes of annotator A and of annotator B separated by a bar.
 *
 * @return list<array{0: list<string>, 1: list<string>}>
 */
function annotationPairs(string $items): array
{
    $set = fn (string $codes): array => $codes === '' ? [] : array_map(trim(...), explode(',', $codes));

    return array_map(function (string $item) use ($set): array {
        [$a, $b] = explode('|', trim($item));

        return [$set($a), $set($b)];
    }, explode(';', $items));
}

test('the masi distance weighs the overlap by how the sets relate, with exact fractions', function () {
    expect(AnnotationAgreement::masiDistance(['1.1', '2.1'], ['2.1', '1.1']))->toBe(0.0)
        ->and(AnnotationAgreement::masiDistance(['2.1'], ['2.1', '7.3']))->toEqualWithDelta(1 - (1 / 2) * (2 / 3), 1e-12)
        ->and(AnnotationAgreement::masiDistance(['1.1', '2.1'], ['2.1', '3.1']))->toEqualWithDelta(1 - (1 / 3) * (1 / 3), 1e-12)
        ->and(AnnotationAgreement::masiDistance(['3.1'], ['4.1']))->toBe(1.0);
});

test('alpha for three mitigations matches the reference values', function () {
    $result = (new AnnotationAgreement)->alpha(annotationPairs('1.1|1.1; 2.1|2.1, 7.3; 3.1|4.1'));

    expect($result['observed'])->toEqualWithDelta(0.555556, 1e-6)
        ->and($result['expected'])->toEqualWithDelta(0.911111, 1e-6)
        ->and($result['alpha'])->toEqualWithDelta(0.390244, 1e-6);
});

test('perfect agreement gives an alpha of one', function () {
    $result = (new AnnotationAgreement)->alpha(annotationPairs('1.1|1.1; 2.1, 7.3|7.3, 2.1; 3.1|3.1; 4.1, 5.2|4.1, 5.2'));

    expect($result['observed'])->toBe(0.0)
        ->and($result['alpha'])->toEqualWithDelta(1.0, 1e-6);
});

test('alpha for twelve mitigations matches the reference value', function () {
    $pairs = annotationPairs(
        '2.1|2.1; 1.2|4.3; 1.1,7.3|1.1,7.3; 5.1|5.1; 1.2|7.4; 2.2|7.4; '.
        '1.1,5.1|7.3; 3.1|3.1; 1.2,3.1|1.2; 2.2,7.4|2.2,7.4; 1.1,1.2|5.1,7.3; 6.5|3.1,4.3',
    );

    expect((new AnnotationAgreement)->alpha($pairs)['alpha'])->toEqualWithDelta(0.405429, 1e-6);
});

test('alpha is undefined when every value is the same', function () {
    expect((new AnnotationAgreement)->alpha(annotationPairs('1.1|1.1; 1.1|1.1'))['alpha'])->toBeNull();
});

test('one subdomain as a yes or no decision gives the AGORA measures and the positive agreement', function () {
    $pairs = array_map(fn (int $item): array => [
        $item <= 5 ? ['3.1'] : [],
        in_array($item, [1, 2, 6, 7, 8], true) ? ['3.1'] : [],
    ], range(1, 40));

    $result = (new AnnotationAgreement)->code($pairs, '3.1');

    expect($result)->toMatchArray(['both' => 2, 'only_a' => 3, 'only_b' => 3, 'neither' => 32])
        ->and($result['observed'])->toEqualWithDelta(0.85, 1e-6)
        ->and($result['g'])->toEqualWithDelta(0.70, 1e-6)
        ->and($result['ac1'])->toEqualWithDelta(0.808, 1e-6)
        ->and($result['positive'])->toEqualWithDelta(0.40, 1e-6);
});

test('the positive agreement is undefined for a subdomain nobody marked', function () {
    $result = (new AnnotationAgreement)->code(annotationPairs('1.1|1.1; 2.1|4.1'), '3.1');

    expect($result['positive'])->toBeNull()
        ->and($result['observed'])->toBe(1.0)
        ->and($result['ac1'])->toEqualWithDelta(1.0, 1e-6);
});

test('the share of identical sets ignores the order of the codes', function () {
    expect((new AnnotationAgreement)->identical(annotationPairs('1.1, 2.1|2.1, 1.1; 2.1|2.1, 7.3; 3.1|4.1; 5.1|5.1')))->toBe(0.5);
});

test('the interval is the same on every run and brackets alpha', function () {
    $pairs = annotationPairs(
        '2.1|2.1; 1.2|4.3; 1.1,7.3|1.1,7.3; 5.1|5.1; 1.2|7.4; 2.2|7.4; '.
        '1.1,5.1|7.3; 3.1|3.1; 1.2,3.1|1.2; 2.2,7.4|2.2,7.4; 1.1,1.2|5.1,7.3; 6.5|3.1,4.3',
    );
    $agreement = new AnnotationAgreement;

    $first = $agreement->interval($pairs);

    expect($agreement->interval($pairs))->toBe($first)
        ->and($first['discarded'])->toBe(0)
        ->and($first['lower'])->toBeLessThan(0.405429)
        ->and($first['upper'])->toBeGreaterThan(0.405429);
});

test('resamples where every value is the same are left out of the interval and counted', function () {
    // Two items: drawing the same one twice leaves a single set, with no alpha.
    $result = (new AnnotationAgreement)->interval(annotationPairs('1.1|1.1; 2.1|2.1'), 1000);

    expect($result['discarded'])->toBeGreaterThan(0)->toBeLessThan(1000)
        ->and($result['lower'])->toEqualWithDelta(1.0, 1e-6)
        ->and($result['upper'])->toEqualWithDelta(1.0, 1e-6);
});
