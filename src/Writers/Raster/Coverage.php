<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Rendering\SceneItem;

/**
 * How much of each pixel a set of paths covers, filled with the nonzero rule: what GD
 * cannot work out itself, as it has no curves, no smoothing for fills and no nonzero rule.
 *
 * Curves are flattened into lines. Each pixel row is sampled on a few lines across it, and
 * along each line the covered part of every pixel is measured exactly, so edges come out
 * smooth and shapes that touch join without a seam.
 *
 * @internal
 */
final class Coverage
{
    // Lines sampled across each pixel row.
    private const int SAMPLES = 4;

    // The longest a flattened curve's segments get, in pixels.
    private const float SEGMENT = 1.5;

    /**
     * @param list<Path|SceneItem> $paths Items are placed on the canvas one at a time, as they are read.
     *
     * @return array<int, array{int, non-empty-list<float>}> Row => the first column covered, and how much of it
     *                                                       and of each column after it is covered, 0-1.
     */
    public static function of(array $paths, float $scale, int $width, int $height): array
    {
        [$tops, $bottoms, $xs, $slopes, $windings] = self::edges($paths, $scale);

        if ($tops === []) {
            return [];
        }

        $top = max(0, (int) floor(min($tops)));
        $bottom = min($height - 1, (int) ceil(max($bottoms ?: [0.0])));
        $rows = [];
        $active = [];

        // Each edge by the row it starts in, rather than sorted: sorting copies them all.
        $starts = [];

        foreach ($tops as $edge => $y) {
            $starts[max($top, (int) floor($y))][] = $edge;
        }

        for ($row = $top; $row <= $bottom; $row++) {
            // The pixels a stretch only partly covers, and where whole ones start and stop.
            [$partial, $whole] = [[], []];
            $active = [...$active, ...$starts[$row] ?? []];
            unset($starts[$row]);

            for ($sample = 0; $sample < self::SAMPLES; $sample++) {
                $y = $row + ($sample + 0.5) / self::SAMPLES;

                $active = array_values(array_filter($active, fn (int $edge): bool => $bottoms[$edge] > $y));
                $crossings = [];

                foreach ($active as $edge) {
                    if ($tops[$edge] <= $y) {
                        $crossings[] = [$xs[$edge] + ($y - $tops[$edge]) * $slopes[$edge], $windings[$edge]];
                    }
                }

                usort($crossings, fn (array $a, array $b): int => $a[0] <=> $b[0]);
                $winding = 0;

                foreach ($crossings as $index => [$x, $direction]) {
                    $winding += $direction;

                    if ($winding !== 0 && isset($crossings[$index + 1])) {
                        self::span($partial, $whole, $x, $crossings[$index + 1][0], $width);
                    }
                }
            }

            if ($partial !== [] || $whole !== []) {
                $rows[$row] = self::row($partial, $whole);
            }
        }

        return $rows;
    }

    /**
     * How much of a pixel is covered, from what `of()` gave.
     *
     * @param array<int, array{int, non-empty-list<float>}> $rows
     */
    public static function at(array $rows, int $column, int $row): float
    {
        [$from, $values] = $rows[$row] ?? [0, [0.0]];

        return $values[$column - $from] ?? 0.0;
    }

    /**
     * One row's coverage, from its partly covered pixels and where whole stretches start and stop.
     *
     * @param array<int, float> $partial
     * @param array<int, float> $whole
     *
     * @return array{int, non-empty-list<float>}
     */
    private static function row(array $partial, array $whole): array
    {
        // Called only for rows something covers, so there is a column.
        /** @var non-empty-list<int> $columns */
        $columns = [...array_keys($partial), ...array_keys($whole)];
        [$from, $to] = [min($columns), max($columns)];
        [$values, $running] = [[], 0.0];

        for ($column = $from; $column <= $to; $column++) {
            $running += $whole[$column] ?? 0.0;
            $values[] = min(1.0, $running + ($partial[$column] ?? 0.0));
        }

        /** @var non-empty-list<float> $values At least the first column's. */
        return [$from, $values];
    }

    /**
     * Adds one sample line's covered stretch, from `$from` to `$to`, to the pixels it crosses: the
     * ends to `$partial`, and the whole pixels between them as a start and a stop in `$whole`.
     *
     * @param array<int, float> $partial
     * @param array<int, float> $whole
     */
    private static function span(array &$partial, array &$whole, float $from, float $to, int $width): void
    {
        // One at a time: opcache's optimizer (PHP 8.5) can get a list assignment that reads what it
        // assigns wrong.
        $from = max(0.0, $from);
        $to = min((float) $width, $to);

        if ($to <= $from) {
            return;
        }

        $weight = 1 / self::SAMPLES;
        [$first, $last] = [(int) floor($from), min($width - 1, (int) floor($to))];

        if ($first === $last) {
            $partial[$first] = ($partial[$first] ?? 0.0) + ($to - $from) * $weight;

            return;
        }

        $partial[$first] = ($partial[$first] ?? 0.0) + ($first + 1 - $from) * $weight;
        $partial[$last] = ($partial[$last] ?? 0.0) + ($to - $last) * $weight;

        if ($last > $first + 1) {
            $whole[$first + 1] = ($whole[$first + 1] ?? 0.0) + $weight;
            $whole[$last] = ($whole[$last] ?? 0.0) - $weight;
        }
    }

    /**
     * Every outline as straight edges in pixels, each closed: their tops, bottoms, x at the top,
     * x per y and winding, a list each; a large code has hundreds of thousands.
     *
     * @param list<Path|SceneItem> $paths
     *
     * @return array{list<float>, list<float>, list<float>, list<float>, list<int>}
     */
    private static function edges(array $paths, float $scale): array
    {
        $edges = [[], [], [], [], []];

        foreach ($paths as $path) {
            foreach (self::outlines($path instanceof SceneItem ? $path->path() : $path, $scale) as $points) {
                $count = count($points);

                for ($index = 0; $index < $count; $index++) {
                    [$x0, $y0] = $points[$index];
                    [$x1, $y1] = $points[($index + 1) % $count];

                    if ($y0 === $y1) {
                        continue;
                    }

                    [$top, $bottom, $x, $winding] = $y0 < $y1 ? [$y0, $y1, $x0, 1] : [$y1, $y0, $x1, -1];
                    $edges[0][] = $top;
                    $edges[1][] = $bottom;
                    $edges[2][] = $x;
                    $edges[3][] = ($x1 - $x0) / ($y1 - $y0);
                    $edges[4][] = $winding;
                }
            }
        }

        return $edges;
    }

    /**
     * A path's outlines as points in pixels, its curves flattened.
     *
     * @return list<list<array{float, float}>>
     */
    private static function outlines(Path $path, float $scale): array
    {
        $outlines = [];
        $points = [];

        foreach ($path->commands() as $command) {
            $values = array_map(fn (float|string $value): float => (float) $value * $scale, array_slice($command, 1));

            if ($command[0] === 'M' && $points !== []) {
                $outlines[] = $points;
                $points = [];
            }

            match ($command[0]) {
                'M', 'L' => $points[] = [$values[0], $values[1]],
                'C' => array_push($points, ...self::flatten(end($points) ?: [0.0, 0.0], $values)),
                default => null,
            };
        }

        return array_values(array_filter([...$outlines, $points], fn (array $outline): bool => count($outline) > 2));
    }

    /**
     * A cubic curve as points, short enough segments that it reads as a curve.
     *
     * @param array{float, float} $start
     * @param list<float> $values The two control points, then the end.
     *
     * @return list<array{float, float}>
     */
    private static function flatten(array $start, array $values): array
    {
        [$x1, $y1, $x2, $y2, $x3, $y3] = $values;
        [$x0, $y0] = $start;
        $length = hypot($x1 - $x0, $y1 - $y0) + hypot($x2 - $x1, $y2 - $y1) + hypot($x3 - $x2, $y3 - $y2);
        $steps = max(2, (int) ceil($length / self::SEGMENT));
        $points = [];

        for ($step = 1; $step <= $steps; $step++) {
            $t = $step / $steps;
            $u = 1 - $t;
            $points[] = [
                $u ** 3 * $x0 + 3 * $u ** 2 * $t * $x1 + 3 * $u * $t ** 2 * $x2 + $t ** 3 * $x3,
                $u ** 3 * $y0 + 3 * $u ** 2 * $t * $y1 + 3 * $u * $t ** 2 * $y2 + $t ** 3 * $y3,
            ];
        }

        return $points;
    }
}
