<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Lawshe's content validity (0027): the content validity ratio (CVR) of an
 * item rated by N experts, the critical number of "essential" ratings an item
 * needs to be retained, after Ayre and Scally (2014), and the content
 * validity index (CVI), the mean CVR.
 *
 * Pure arithmetic. The critical value is worked out with integers only, so it
 * is exact and never depends on rounding.
 */
class ContentValidity
{
    /** The one sided significance level of the critical value. */
    public const ALPHA = 0.05;

    /** The smallest panel with a critical value: below it, not even unanimity is enough. */
    public const MINIMUM_PANEL = 5;

    /** The largest panel the integer arithmetic of the critical value is safe for. */
    public const MAXIMUM_PANEL = 60;

    /** 1 / ALPHA, so the test stays in integers. */
    private const CHANCE = 20;

    /**
     * The smallest number of "essential" ratings that chance alone, as a
     * binomial with N trials and p = 0.5, matches or exceeds with probability
     * below ALPHA: the smallest nₑ with 20 · Σ C(N, k), k = nₑ..N, below 2^N.
     * Null when not even unanimity is enough (N below MINIMUM_PANEL).
     *
     * @throws InvalidArgumentException When N is not between 1 and MAXIMUM_PANEL.
     */
    public function criticalEssential(int $n): ?int
    {
        if ($n < 1 || $n > self::MAXIMUM_PANEL) {
            throw new InvalidArgumentException('The panel must have between 1 and '.self::MAXIMUM_PANEL." experts, not {$n}.");
        }

        $binomial = self::binomialRow($n);
        $total = 1 << $n;
        $critical = null;
        $tail = 0;

        // The tail only grows as nₑ goes down, so the first nₑ that fails
        // the test ends the search; the tail is then still far from 2^N.
        for ($essential = $n; $essential >= 0; $essential--) {
            $tail += $binomial[$essential];

            if (self::CHANCE * $tail >= $total) {
                break;
            }

            $critical = $essential;
        }

        return $critical;
    }

    /** The CVR of the critical number of "essential" ratings, or null when there is none. */
    public function criticalCvr(int $n): ?float
    {
        $critical = $this->criticalEssential($n);

        return $critical === null ? null : $this->cvr($critical, $n);
    }

    /** Lawshe's CVR: (nₑ − N/2) / (N/2). */
    public function cvr(int $essential, int $n): float
    {
        return ($essential - $n / 2) / ($n / 2);
    }

    /**
     * Whether an item is retained: at least the critical number of
     * "essential" ratings, compared as integers rather than as rounded CVRs.
     */
    public function retained(int $essential, int $n): bool
    {
        $critical = $this->criticalEssential($n);

        return $critical !== null && $essential >= $critical;
    }

    /**
     * The CVI, mean of the CVRs, or null when there is none.
     *
     * @param  list<float>  $cvrs
     */
    public function cvi(array $cvrs): ?float
    {
        return $cvrs === [] ? null : array_sum($cvrs) / count($cvrs);
    }

    /**
     * The median and the quartiles of some values, by linear interpolation
     * between the closest ranks, as in the percentiles of 0025.
     *
     * @param  list<int|float>  $values
     * @return array{median: float, q1: float, q3: float}
     *
     * @throws InvalidArgumentException When there are no values.
     */
    public function quartiles(array $values): array
    {
        if ($values === []) {
            throw new InvalidArgumentException('The quartiles need at least one value.');
        }

        $sorted = array_map(floatval(...), $values);
        sort($sorted);

        return [
            'median' => (float) AnnotationAgreement::percentile($sorted, 0.5),
            'q1' => (float) AnnotationAgreement::percentile($sorted, 0.25),
            'q3' => (float) AnnotationAgreement::percentile($sorted, 0.75),
        ];
    }

    /**
     * C(N, k) for every k, from Pascal's triangle: additions only, so no
     * intermediate value is larger than the result.
     *
     * @return list<int>
     */
    protected static function binomialRow(int $n): array
    {
        $row = [1];

        for ($size = 1; $size <= $n; $size++) {
            $next = [1];

            for ($k = 1; $k < $size; $k++) {
                $next[] = $row[$k - 1] + $row[$k];
            }

            $next[] = 1;
            $row = $next;
        }

        return $row;
    }
}
