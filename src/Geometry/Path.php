<?php

declare(strict_types=1);

namespace ExeQue\Qr\Geometry;

use ExeQue\Qr\Support\Number;

/**
 * An outline of straight lines and cubic curves, in modules, y pointing down.
 *
 * Paths fill with the nonzero rule, so overlapping parts simply merge. A hole
 * is drawn in the opposite direction to the outline around it: draw outlines
 * clockwise (as seen on screen) and holes anticlockwise, or use `reversed()`.
 */
final readonly class Path
{
    /**
     * @param list<array<int, string|float>> $commands `['M', x, y]`, `['L', x, y]`, `['C', x1, y1, x2, y2, x, y]` or `['Z']`.
     */
    private function __construct(
        private array $commands = [],
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * A closed outline through the points, in order.
     *
     * @param list<array{float, float}> $points
     */
    public static function polygon(array $points): self
    {
        $path = self::make();

        foreach ($points as $index => [$x, $y]) {
            $path = $index === 0 ? $path->moveTo($x, $y) : $path->lineTo($x, $y);
        }

        return $path->close();
    }

    public function moveTo(float $x, float $y): self
    {
        return new self([...$this->commands, ['M', $x, $y]]);
    }

    /**
     * A line to a point; nothing when already there.
     */
    public function lineTo(float $x, float $y): self
    {
        if (self::same($this->currentPoint(), [$x, $y])) {
            return $this;
        }

        return new self([...$this->commands, ['L', $x, $y]]);
    }

    public function curveTo(float $x1, float $y1, float $x2, float $y2, float $x, float $y): self
    {
        return new self([...$this->commands, ['C', $x1, $y1, $x2, $y2, $x, $y]]);
    }

    /**
     * Along a circle from one angle to another, in degrees (0 pointing right, y down, so growing
     * clockwise), as a cubic curve per quarter turn or less; from a line to its start, or a move
     * to it on an empty path.
     */
    public function arc(float $centerX, float $centerY, float $radius, float $from, float $to): self
    {
        $point = fn (float $degrees): array => [$centerX + $radius * cos(deg2rad($degrees)), $centerY + $radius * sin(deg2rad($degrees))];
        $path = $this->commands === [] ? $this->moveTo(...$point($from)) : $this->lineTo(...$point($from));
        $pieces = max(1, (int) ceil(abs($to - $from) / 90 - 1e-9));
        $step = ($to - $from) / $pieces;

        // The control points lie along the tangents, 4/3 tan(θ/4) of the radius out.
        $handle = 4 / 3 * tan(deg2rad($step) / 4) * $radius;

        for ($piece = 0; $piece < $pieces; $piece++) {
            [$start, $end] = [deg2rad($from + $step * $piece), deg2rad($from + $step * ($piece + 1))];
            [$x0, $y0] = $point(rad2deg($start));
            [$x1, $y1] = $point(rad2deg($end));

            $path = $path->curveTo(
                $x0 - $handle * sin($start),
                $y0 + $handle * cos($start),
                $x1 + $handle * sin($end),
                $y1 - $handle * cos($end),
                $x1,
                $y1,
            );
        }

        return $path;
    }

    /**
     * Closes the outline back to where it started; a last line back there is left to the close.
     */
    public function close(): self
    {
        $commands = $this->commands;
        $last = end($commands);

        if ($last !== false && $last[0] === 'L' && self::same([(float) $last[1], (float) $last[2]], $this->subpathStart())) {
            array_pop($commands);
        }

        return new self([...$commands, ['Z']]);
    }

    public function append(self $path): self
    {
        return new self([...$this->commands, ...$path->commands]);
    }

    /**
     * @param list<self> $paths
     */
    public static function merge(array $paths): self
    {
        return new self(array_merge(...array_map(fn (self $path): array => $path->commands, $paths)));
    }

    public function transform(Transform $transform): self
    {
        if ($transform->isIdentity()) {
            return $this;
        }

        return new self(array_map(function (array $command) use ($transform): array {
            $points = array_chunk(array_slice($command, 1), 2);

            return [$command[0], ...array_merge(...array_map(fn (array $point): array => $transform->apply((float) $point[0], (float) $point[1]), $points))];
        }, $this->commands));
    }

    /**
     * The same outline drawn the other way around, for holes.
     */
    public function reversed(): self
    {
        $reversed = [];

        foreach ($this->subpaths() as $subpath) {
            $reversed = [...$reversed, ...self::reverseSubpath($subpath)];
        }

        return new self($reversed);
    }

    /**
     * The commands in order: `['M', x, y]`, `['L', x, y]`, `['C', x1, y1, x2, y2, x, y]` or `['Z']`.
     *
     * @return list<array<int, string|float>>
     */
    public function commands(): array
    {
        return $this->commands;
    }

    public function isEmpty(): bool
    {
        return $this->commands === [];
    }

    /**
     * The smallest box round every point, control points included:
     * `[minX, minY, maxX, maxY]`, or null for an empty path.
     *
     * @return array{float, float, float, float}|null
     */
    public function bounds(): ?array
    {
        $xs = [];
        $ys = [];

        foreach ($this->commands as $command) {
            foreach (array_chunk(array_slice($command, 1), 2) as [$x, $y]) {
                $xs[] = (float) $x;
                $ys[] = (float) $y;
            }
        }

        if ($xs === [] || $ys === []) {
            return null;
        }

        return [min($xs), min($ys), max($xs), max($ys)];
    }

    /**
     * SVG path data, every coordinate multiplied by `$scale`.
     */
    public function toSvg(int $precision = 3, float $scale = 1.0): string
    {
        return implode(' ', array_map(
            fn (array $command): string => trim($command[0].' '.implode(' ', array_map(
                fn (float|string $value): string => Number::format((float) $value * $scale, $precision),
                array_slice($command, 1),
            ))),
            $this->commands,
        ));
    }

    /**
     * Whether two points are one, give or take rounding in the arithmetic that made them.
     *
     * @param array{float, float}|null $a
     * @param array{float, float} $b
     */
    private static function same(?array $a, ?array $b): bool
    {
        return $a !== null && $b !== null && abs($a[0] - $b[0]) < 1e-9 && abs($a[1] - $b[1]) < 1e-9;
    }

    /**
     * @return array{float, float}|null
     */
    private function currentPoint(): ?array
    {
        $last = $this->commands[array_key_last($this->commands) ?? 0] ?? null;

        return match (true) {
            $last === null => null,
            $last[0] === 'Z' => $this->subpathStart(),
            default => [(float) $last[count($last) - 2], (float) $last[count($last) - 1]],
        };
    }

    /**
     * @return array{float, float}|null
     */
    private function subpathStart(): ?array
    {
        for ($index = count($this->commands) - 1; $index >= 0; $index--) {
            if ($this->commands[$index][0] === 'M') {
                return [(float) $this->commands[$index][1], (float) $this->commands[$index][2]];
            }
        }

        return null;
    }

    /**
     * SVG path data at its smallest; see `CompactPathData`.
     */
    public function toCompactSvg(int $precision = 3, float $scale = 1.0): string
    {
        return new CompactPathData($precision, $scale)->encode($this);
    }

    /**
     * @return list<list<array<int, string|float>>>
     */
    private function subpaths(): array
    {
        $subpaths = [];

        foreach ($this->commands as $command) {
            if ($command[0] === 'M' || $subpaths === []) {
                $subpaths[] = [];
            }

            $subpaths[array_key_last($subpaths)][] = $command;
        }

        return $subpaths;
    }

    /**
     * @param list<array<int, string|float>> $subpath
     *
     * @return list<array<int, string|float>>
     */
    private static function reverseSubpath(array $subpath): array
    {
        $closed = ($subpath[array_key_last($subpath)][0] ?? null) === 'Z';
        $segments = array_values(array_filter($subpath, fn (array $command): bool => $command[0] !== 'Z'));

        if ($segments === []) {
            return $subpath;
        }

        // Each segment ends where the next begins; walking back, every segment runs from its end to the previous end.
        $ends = array_map(fn (array $command): array => array_slice($command, -2), $segments);
        $reversed = [['M', ...$ends[array_key_last($ends)]]];

        for ($index = count($segments) - 1; $index >= 1; $index--) {
            $segment = $segments[$index];
            $to = $ends[$index - 1];

            $reversed[] = $segment[0] === 'C'
                ? ['C', $segment[3], $segment[4], $segment[1], $segment[2], ...$to]
                : ['L', ...$to];
        }

        return $closed ? [...$reversed, ['Z']] : $reversed;
    }
}
