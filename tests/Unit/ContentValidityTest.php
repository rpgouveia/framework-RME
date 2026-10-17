<?php

use App\Support\ContentValidity;

test('the critical value follows Ayre and Scally for every panel size of the reference table', function (int $n, int $essential, float $cvr) {
    $validity = new ContentValidity;

    expect($validity->criticalEssential($n))->toBe($essential)
        ->and($validity->criticalCvr($n))->toEqualWithDelta($cvr, 1e-6);
})->with([
    [5, 5, 1.0],
    [7, 7, 1.0],
    [8, 7, 0.75],
    [9, 8, 0.777778],
    [10, 9, 0.8],
    [11, 9, 0.636364],
    [12, 10, 0.666667],
    [15, 12, 0.6],
    [20, 15, 0.5],
    [40, 26, 0.3],
]);

test('the critical value matches an independent floating point computation up to the largest panel', function () {
    $validity = new ContentValidity;

    foreach (range(ContentValidity::MINIMUM_PANEL, ContentValidity::MAXIMUM_PANEL) as $n) {
        // Each tail probability from the binomial terms, each term built as a
        // product of ratios so no factorial is formed.
        $tail = fn (int $from): float => array_sum(array_map(function (int $k) use ($n): float {
            $term = 0.5 ** $n;

            for ($i = 1; $i <= $k; $i++) {
                $term *= ($n - $k + $i) / $i;
            }

            return $term;
        }, range($from, $n)));

        $expected = null;

        foreach (range($n, 0) as $essential) {
            if ($tail($essential) >= ContentValidity::ALPHA) {
                break;
            }

            $expected = $essential;
        }

        expect($validity->criticalEssential($n))->toBe($expected, "N = {$n}");
    }
});

test('below five experts not even unanimity reaches the critical value', function () {
    $validity = new ContentValidity;

    expect($validity->criticalEssential(4))->toBeNull()
        ->and($validity->criticalCvr(4))->toBeNull()
        ->and($validity->retained(4, 4))->toBeFalse();
});

test('a panel larger than the integer arithmetic allows is refused', function () {
    (new ContentValidity)->criticalEssential(ContentValidity::MAXIMUM_PANEL + 1);
})->throws(InvalidArgumentException::class);

test('the cvr is Lawshe\'s and an item is retained by comparing counts, not rounded ratios', function () {
    $validity = new ContentValidity;

    expect($validity->cvr(8, 8))->toBe(1.0)
        ->and($validity->cvr(7, 8))->toBe(0.75)
        ->and($validity->cvr(4, 8))->toBe(0.0)
        ->and($validity->cvr(0, 8))->toBe(-1.0)
        ->and($validity->cvr(9, 11))->toEqualWithDelta(0.636364, 1e-6)
        ->and($validity->retained(7, 8))->toBeTrue()
        ->and($validity->retained(6, 8))->toBeFalse()
        ->and($validity->retained(9, 11))->toBeTrue()
        ->and($validity->retained(8, 11))->toBeFalse();
});

test('the cvi is the mean cvr, undefined without items', function () {
    $validity = new ContentValidity;

    expect($validity->cvi([1.0, 0.75, 0.5]))->toEqualWithDelta(0.75, 1e-12)
        ->and($validity->cvi([]))->toBeNull();
});

test('the quartiles interpolate linearly between the closest ranks, whatever the order', function () {
    $validity = new ContentValidity;

    expect($validity->quartiles([60, 90, 90, 90, 120, 180, 90, 60]))->toBe(['median' => 90.0, 'q1' => 82.5, 'q3' => 97.5])
        ->and($validity->quartiles([90, 120, 180, 180, 240, 180, 150, 180]))->toBe(['median' => 180.0, 'q1' => 142.5, 'q3' => 180.0])
        ->and($validity->quartiles([180, 180, 240, 365, 180, 180, 365, 180]))->toBe(['median' => 180.0, 'q1' => 180.0, 'q3' => 271.25])
        ->and($validity->quartiles([30]))->toBe(['median' => 30.0, 'q1' => 30.0, 'q3' => 30.0]);
});
