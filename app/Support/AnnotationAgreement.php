<?php

namespace App\Support;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Agreement between two annotators who each gave every item a set of codes
 * (0025): Krippendorff's alpha with the MASI distance (Passonneau, 2006) as
 * the main measure, with a bootstrap confidence interval, and, per code taken
 * as a yes or no decision, Guilford's g, Gwet's AC1 and the positive
 * agreement, as in the AGORA. Two annotators and no missing values.
 *
 * Pure arithmetic: the items come as pairs of code lists, and the same pairs
 * always give the same result, the interval included.
 *
 * @phpstan-type Pair array{0: list<string>, 1: list<string>}
 * @phpstan-type Alpha array{alpha: float|null, observed: float, expected: float}
 * @phpstan-type Interval array{lower: float|null, upper: float|null, discarded: int}
 * @phpstan-type CodeAgreement array{code: string, both: int, only_a: int, only_b: int, neither: int, observed: float, g: float, ac1: float, positive: float|null}
 */
class AnnotationAgreement
{
    /** Bootstrap resamples of the items for the interval (0025, item 3). */
    public const RESAMPLES = 10000;

    /** The fixed seed, so the interval is always the same. */
    public const SEED = 2026;

    /** The minimum alpha for tentative conclusions, after Krippendorff. */
    public const MINIMUM = 0.667;

    /** The alpha the group aims for. */
    public const TARGET = 0.8;

    /**
     * The MASI distance between two sets: 1 minus the Jaccard index weighted
     * by how the sets relate (1 equal, 2/3 one contains the other, 1/3 they
     * overlap, 0 disjoint).
     *
     * @param  list<string>  $x
     * @param  list<string>  $y
     */
    public static function masiDistance(array $x, array $y): float
    {
        $x = array_unique($x);
        $y = array_unique($y);
        $intersection = count(array_intersect($x, $y));
        $union = count(array_unique([...$x, ...$y]));

        if ($union === 0) {
            return 0.0;
        }

        $weight = match (true) {
            $intersection === count($x) && $intersection === count($y) => 1.0,
            $intersection === count($x) || $intersection === count($y) => 2 / 3,
            $intersection > 0 => 1 / 3,
            default => 0.0,
        };

        return 1 - ($intersection / $union) * $weight;
    }

    /**
     * Krippendorff's alpha: 1 minus the observed disagreement (the mean
     * distance within each item) over the expected one (the mean distance
     * between distinct positions of the 2N pooled values). Undefined, as
     * null, when every value is the same.
     *
     * @param  list<Pair>  $pairs
     * @return Alpha
     */
    public function alpha(array $pairs): array
    {
        [$values, $items, $distances] = $this->pool($pairs);

        return $this->alphaOf(array_fill(0, count($pairs), 1), $items, $values, $distances);
    }

    /**
     * The 95% percentile interval of alpha: the items resampled with
     * replacement, observed and expected disagreement computed again each
     * time. Resamples where every value is the same have no alpha and are
     * left out, and counted.
     *
     * @param  list<Pair>  $pairs
     * @return Interval
     */
    public function interval(array $pairs, int $resamples = self::RESAMPLES, int $seed = self::SEED): array
    {
        [$values, $items, $distances] = $this->pool($pairs);
        $count = count($pairs);
        $randomizer = new Randomizer(new Mt19937($seed));
        $alphas = [];
        $discarded = 0;

        for ($resample = 0; $resample < $resamples; $resample++) {
            $times = array_fill(0, $count, 0);

            for ($draw = 0; $draw < $count; $draw++) {
                $times[$randomizer->getInt(0, $count - 1)]++;
            }

            $alpha = $this->alphaOf($times, $items, $values, $distances)['alpha'];

            if ($alpha === null) {
                $discarded++;
            } else {
                $alphas[] = $alpha;
            }
        }

        sort($alphas);

        return [
            'lower' => self::percentile($alphas, 0.025),
            'upper' => self::percentile($alphas, 0.975),
            'discarded' => $discarded,
        ];
    }

    /**
     * One code taken as a yes or no decision on every item: the counts, the
     * observed agreement, Guilford's g, Gwet's AC1 and the positive agreement
     * (undefined, as null, when neither annotator marked the code).
     *
     * @param  list<Pair>  $pairs
     * @return CodeAgreement
     */
    public function code(array $pairs, string $code): array
    {
        $counts = ['both' => 0, 'only_a' => 0, 'only_b' => 0, 'neither' => 0];

        foreach ($pairs as [$a, $b]) {
            $inA = in_array($code, $a, true);
            $inB = in_array($code, $b, true);
            $counts[match (true) {
                $inA && $inB => 'both',
                $inA => 'only_a',
                $inB => 'only_b',
                default => 'neither',
            }]++;
        }

        $total = count($pairs);
        $observed = (float) (($counts['both'] + $counts['neither']) / $total);
        $yes = (($counts['both'] + $counts['only_a']) / $total + ($counts['both'] + $counts['only_b']) / $total) / 2;
        $chance = 2 * $yes * (1 - $yes);
        $marks = 2 * $counts['both'] + $counts['only_a'] + $counts['only_b'];

        return [
            'code' => $code,
            ...$counts,
            'observed' => $observed,
            'g' => 2 * $observed - 1,
            'ac1' => ($observed - $chance) / (1 - $chance),
            'positive' => $marks === 0 ? null : (float) (2 * $counts['both'] / $marks),
        ];
    }

    /**
     * The share of items both annotators gave the same set.
     *
     * @param  list<Pair>  $pairs
     */
    public function identical(array $pairs): float
    {
        $same = array_filter($pairs, fn (array $pair): bool => self::key($pair[0]) === self::key($pair[1]));

        return (float) (count($same) / count($pairs));
    }

    /**
     * The distinct sets among the 2N values, each item's two values as
     * positions in that list, and the distances between every two of them,
     * so the resamples only add up.
     *
     * @param  list<Pair>  $pairs
     * @return array{list<list<string>>, list<array{int, int}>, list<list<float>>}
     */
    protected function pool(array $pairs): array
    {
        $positions = [];
        $values = [];
        $items = [];

        foreach ($pairs as $pair) {
            $item = [];

            foreach ($pair as $set) {
                $key = self::key($set);

                if (! isset($positions[$key])) {
                    $positions[$key] = count($values);
                    $values[] = $set;
                }

                $item[] = $positions[$key];
            }

            $items[] = [$item[0], $item[1]];
        }

        $distances = array_map(
            fn (array $x): array => array_map(fn (array $y): float => self::masiDistance($x, $y), $values),
            $values,
        );

        return [$values, $items, $distances];
    }

    /**
     * Alpha for the items taken the given number of times each. The expected
     * disagreement sums the distance over ordered pairs of distinct
     * positions; a value's distance to itself is zero, so that is the sum
     * over every two values weighted by how often each appears.
     *
     * @param  array<int, int>  $times
     * @param  list<array{int, int}>  $items
     * @param  list<list<string>>  $values
     * @param  list<list<float>>  $distances
     * @return Alpha
     */
    protected function alphaOf(array $times, array $items, array $values, array $distances): array
    {
        $count = array_sum($times);
        $weights = array_fill(0, count($values), 0);
        $within = 0.0;

        foreach ($items as $index => [$a, $b]) {
            if ($times[$index] === 0) {
                continue;
            }

            $within += $times[$index] * $distances[$a][$b];
            $weights[$a] += $times[$index];
            $weights[$b] += $times[$index];
        }

        $between = 0.0;

        foreach ($weights as $x => $weightX) {
            foreach ($weights as $y => $weightY) {
                $between += $weightX * $weightY * $distances[$x][$y];
            }
        }

        $observed = (float) ($within / $count);
        $expected = $between / (2 * $count * (2 * $count - 1));

        return [
            'alpha' => $expected > 0 ? 1 - $observed / $expected : null,
            'observed' => $observed,
            'expected' => $expected,
        ];
    }

    /**
     * A percentile of sorted values, by linear interpolation between the
     * closest ranks (the default of R and NumPy). Also the quartiles of the
     * expert panel's review intervals (0027).
     *
     * @param  list<float>  $sorted
     */
    public static function percentile(array $sorted, float $fraction): ?float
    {
        if ($sorted === []) {
            return null;
        }

        $rank = (count($sorted) - 1) * $fraction;
        $below = (int) floor($rank);
        $above = min($below + 1, count($sorted) - 1);

        return $sorted[$below] + ($rank - $below) * ($sorted[$above] - $sorted[$below]);
    }

    /**
     * A set as a comparable key, whatever the order of its codes.
     *
     * @param  list<string>  $set
     */
    protected static function key(array $set): string
    {
        $set = array_unique($set);
        sort($set);

        return implode(',', $set);
    }
}
