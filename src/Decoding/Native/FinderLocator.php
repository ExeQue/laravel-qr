<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding\Native;

use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * Finds the three finder patterns: places where a line through the middle, across and down,
 * runs dark, light, dark, light, dark in the ratio 1:1:3:1:1 (ISO/IEC 18004:2015 section 12).
 *
 * @internal
 */
final class FinderLocator
{
    /**
     * @var list<bool>
     */
    private array $dark = [];

    private int $width = 0;

    private int $height = 0;

    /**
     * The middles of the top-left, top-right and bottom-left finders, and the module size.
     *
     * @param list<bool> $dark
     *
     * @return array{array{float, float}, array{float, float}, array{float, float}, float}
     */
    public function locate(array $dark, int $width, int $height): array
    {
        [$this->dark, $this->width, $this->height] = [$dark, $width, $height];

        $candidates = [];

        for ($y = 0; $y < $height; $y++) {
            foreach ($this->rowHits($y) as [$x, $size]) {
                $confirmed = $this->confirm($x, (float) $y, $size);

                if ($confirmed !== null) {
                    $candidates = self::merge($candidates, $confirmed);
                }
            }
        }

        return self::order(self::bestThree($candidates));
    }

    /**
     * Where a row runs 1:1:3:1:1, by the middle's x and the pattern's width.
     *
     * @return list<array{float, float}>
     */
    private function rowHits(int $y): array
    {
        $runs = $this->runs(fn (int $x): bool => $this->isDark($x, $y), $this->width);
        $hits = [];

        for ($index = 0; $index + 4 < count($runs); $index++) {
            [$start, $isDark] = $runs[$index];

            if (! $isDark) {
                continue;
            }

            $lengths = array_map(fn (array $run): int => $run[2], array_slice($runs, $index, 5));

            if (self::isFinderRatio($lengths)) {
                $hits[] = [$runs[$index + 2][0] + $runs[$index + 2][2] / 2, (float) array_sum($lengths)];
            }
        }

        return $hits;
    }

    /**
     * Checks a row hit down the column through its middle, then across again at the middle
     * found, and gives the pattern's middle and module size.
     *
     * @return array{float, float, float}|null
     */
    private function confirm(float $x, float $y, float $width): ?array
    {
        $down = $this->measure((int) $x, (int) $y, 0, 1, $width);

        if ($down === null) {
            return null;
        }

        $across = $this->measure((int) $x, (int) $down[0], 1, 0, $width);

        if ($across === null) {
            return null;
        }

        return [$across[0], $down[0], ($across[1] + $down[1]) / 14];
    }

    /**
     * From a dark middle, the five runs along a line both ways: the middle along it and the
     * pattern's length, if they keep the finder's ratio.
     *
     * @return array{float, float}|null
     */
    private function measure(int $x, int $y, int $dx, int $dy, float $expected): ?array
    {
        $limit = (int) ceil($expected);
        $forward = $this->runsFrom($x, $y, $dx, $dy, $limit);
        $backward = $this->runsFrom($x, $y, -$dx, -$dy, $limit);

        if ($forward === null || $backward === null) {
            return null;
        }

        // Both ways start in the middle run, counting the start once.
        $lengths = [$backward[2], $backward[1], $backward[0] + $forward[0] - 1, $forward[1], $forward[2]];

        if (! self::isFinderRatio($lengths) || abs(array_sum($lengths) - $expected) > $expected) {
            return null;
        }

        $at = $dx !== 0 ? $x : $y;
        $middle = $at - $backward[0] + 1 + $lengths[2] / 2;

        return [$middle, (float) array_sum($lengths)];
    }

    /**
     * The lengths of three runs - dark, light, dark - going one way from a dark pixel.
     *
     * @return array{int, int, int}|null
     */
    private function runsFrom(int $x, int $y, int $dx, int $dy, int $limit): ?array
    {
        $lengths = [0, 0, 0];
        $run = 0;
        $wanted = true;

        for ($step = 0; $step <= $limit * 2; $step++) {
            $here = $this->isDark($x + $dx * $step, $y + $dy * $step);

            if ($here !== $wanted) {
                if ($run === 2) {
                    return [$lengths[0], $lengths[1], $lengths[2]];
                }

                $run++;
                $wanted = ! $wanted;
            }

            $lengths[$run]++;
        }

        return null;
    }

    /**
     * @param callable(int): bool $isDark
     *
     * @return list<array{int, bool, int}> Each run's start, darkness and length.
     */
    private function runs(callable $isDark, int $length): array
    {
        $runs = [];
        $start = 0;
        $current = $isDark(0);

        for ($at = 1; $at <= $length; $at++) {
            $here = $at < $length ? $isDark($at) : ! $current;

            if ($here !== $current) {
                $runs[] = [$start, $current, $at - $start];
                [$start, $current] = [$at, $here];
            }
        }

        return $runs;
    }

    /**
     * @param list<int> $lengths
     */
    private static function isFinderRatio(array $lengths): bool
    {
        $total = array_sum($lengths);

        if ($total < 7) {
            return false;
        }

        $module = $total / 7;
        $slack = $module * 0.7;

        return abs($lengths[0] - $module) < $slack
            && abs($lengths[1] - $module) < $slack
            && abs($lengths[2] - 3 * $module) < $slack * 2
            && abs($lengths[3] - $module) < $slack
            && abs($lengths[4] - $module) < $slack;
    }

    /**
     * Adds a confirmed hit to the candidate it belongs to, or as a new one.
     *
     * @param list<array{float, float, float, int}> $candidates x, y, module size, hits.
     * @param array{float, float, float} $hit
     *
     * @return list<array{float, float, float, int}>
     */
    private static function merge(array $candidates, array $hit): array
    {
        [$x, $y, $size] = $hit;

        foreach ($candidates as $index => [$cx, $cy, $csize, $hits]) {
            if (abs($cx - $x) <= $csize * 2 && abs($cy - $y) <= $csize * 2 && abs($csize - $size) <= $csize * 0.6) {
                $candidates[$index] = [
                    ($cx * $hits + $x) / ($hits + 1),
                    ($cy * $hits + $y) / ($hits + 1),
                    ($csize * $hits + $size) / ($hits + 1),
                    $hits + 1,
                ];

                return $candidates;
            }
        }

        $candidates[] = [$x, $y, $size, 1];

        return $candidates;
    }

    /**
     * The three candidates most like the corners of one square symbol - similar in size, two
     * sides of equal length meeting at a right angle - and most often found.
     *
     * @param list<array{float, float, float, int}> $candidates
     *
     * @return array{array{float, float, float, int}, array{float, float, float, int}, array{float, float, float, int}}
     */
    private static function bestThree(array $candidates): array
    {
        usort($candidates, fn (array $a, array $b): int => $b[3] <=> $a[3]);
        $candidates = array_slice(array_values(array_filter($candidates, fn (array $c): bool => $c[3] >= 2)), 0, 8);

        $best = null;
        $bestScore = INF;
        $mostHits = $candidates[0][3] ?? 1;

        foreach ($candidates as $i => $a) {
            foreach ($candidates as $j => $b) {
                foreach ($candidates as $k => $c) {
                    if (! ($i < $j && $j < $k)) {
                        continue;
                    }

                    // Real finders are hit by every row through their middle three modules; shapes
                    // in the data that happen to fit are hit by a few. Geometry alone can favor
                    // those under perspective.
                    $weakness = 3 - ($a[3] + $b[3] + $c[3]) / $mostHits;
                    $score = self::cornerScore($a, $b, $c) + $weakness / 3;

                    if ($score < $bestScore) {
                        [$best, $bestScore] = [[$a, $b, $c], $score];
                    }
                }
            }
        }

        return $best ?? throw UnreadableCodeException::noCode();
    }

    /**
     * How far three points are from the corners of a square of finders, 0 being exact.
     *
     * @param array{float, float, float, int} $a
     * @param array{float, float, float, int} $b
     * @param array{float, float, float, int} $c
     */
    private static function cornerScore(array $a, array $b, array $c): float
    {
        $sizes = [$a[2], $b[2], $c[2]];
        $sizeSpread = (max($sizes) - min($sizes)) / max($sizes);
        $sides = [self::distance($a, $b), self::distance($b, $c), self::distance($a, $c)];
        sort($sides);

        if ($sides[0] < max($sizes) * 7) {
            return INF;
        }

        $legs = abs($sides[0] - $sides[1]) / $sides[1];
        $hypotenuse = abs($sides[2] - $sides[1] * M_SQRT2) / $sides[2];

        return $sizeSpread + $legs + $hypotenuse;
    }

    /**
     * The corner at the right angle is the top left; the other two are told apart by which
     * side of it they turn to.
     *
     * @param array{array{float, float, float, int}, array{float, float, float, int}, array{float, float, float, int}} $three
     *
     * @return array{array{float, float}, array{float, float}, array{float, float}, float}
     */
    private static function order(array $three): array
    {
        [$a, $b, $c] = $three;
        $pairs = [[self::distance($b, $c), $a, $b, $c], [self::distance($a, $c), $b, $a, $c], [self::distance($a, $b), $c, $a, $b]];
        usort($pairs, fn (array $p, array $q): int => $q[0] <=> $p[0]);
        [, $topLeft, $one, $two] = $pairs[0];

        // With y growing downward, top right then bottom left turns clockwise from the top left.
        $cross = ($one[0] - $topLeft[0]) * ($two[1] - $topLeft[1]) - ($one[1] - $topLeft[1]) * ($two[0] - $topLeft[0]);
        [$topRight, $bottomLeft] = $cross > 0 ? [$one, $two] : [$two, $one];

        return [
            [$topLeft[0], $topLeft[1]],
            [$topRight[0], $topRight[1]],
            [$bottomLeft[0], $bottomLeft[1]],
            ($topLeft[2] + $topRight[2] + $bottomLeft[2]) / 3,
        ];
    }

    /**
     * @param array{float, float, float, int} $a
     * @param array{float, float, float, int} $b
     */
    private static function distance(array $a, array $b): float
    {
        return hypot($a[0] - $b[0], $a[1] - $b[1]);
    }

    private function isDark(int $x, int $y): bool
    {
        return $x >= 0 && $y >= 0 && $x < $this->width && $y < $this->height && $this->dark[$y * $this->width + $x];
    }
}
