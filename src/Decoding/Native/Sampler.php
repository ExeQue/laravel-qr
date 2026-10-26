<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding\Native;

use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * Reads the grid of modules off the image, given the three finders: the symbol's size from
 * their distance, and a perspective fixed by the finders and the bottom-right alignment
 * pattern, when the symbol has one and it can be found.
 *
 * @internal
 */
final class Sampler
{
    /**
     * @var list<bool>
     */
    private array $dark = [];

    private int $width = 0;

    private int $height = 0;

    /**
     * @param list<bool> $dark
     * @param array{float, float} $topLeft
     * @param array{float, float} $topRight
     * @param array{float, float} $bottomLeft
     *
     * @param int|null $size Modules across, when known; measured from the finders otherwise.
     *
     * @return list<list<bool>> Dark modules by row, then column.
     */
    public function sample(array $dark, int $width, int $height, array $topLeft, array $topRight, array $bottomLeft, float $module, ?int $size = null): array
    {
        [$this->dark, $this->width, $this->height] = [$dark, $width, $height];

        $size ??= self::size($topLeft, $topRight, $bottomLeft, $module);
        $far = $size - 3.5;

        // Without perspective the fourth corner completes the parallelogram.
        $corner = [$topRight[0] + $bottomLeft[0] - $topLeft[0], $topRight[1] + $bottomLeft[1] - $topLeft[1]];
        $transform = Perspective::between(
            [[3.5, 3.5], [$far, 3.5], [$far, $far], [3.5, $far]],
            [$topLeft, $topRight, $corner, $bottomLeft],
        );

        $alignment = $this->alignment($transform, $size, $module);

        if ($alignment !== null) {
            $inner = $size - 6.5;
            $transform = Perspective::between(
                [[3.5, 3.5], [$far, 3.5], [$inner, $inner], [3.5, $far]],
                [$topLeft, $topRight, $alignment, $bottomLeft],
            );
        }

        $modules = [];

        for ($row = 0; $row < $size; $row++) {
            $line = [];

            for ($column = 0; $column < $size; $column++) {
                [$x, $y] = $transform->map($column + 0.5, $row + 0.5);
                $line[] = $this->isDark((int) floor($x), (int) floor($y));
            }

            $modules[] = $line;
        }

        return $modules;
    }

    /**
     * The modules across, from the finders' distance, snapped to a size a symbol can have.
     *
     * @param array{float, float} $topLeft
     * @param array{float, float} $topRight
     * @param array{float, float} $bottomLeft
     */
    private static function size(array $topLeft, array $topRight, array $bottomLeft, float $module): int
    {
        $across = hypot($topRight[0] - $topLeft[0], $topRight[1] - $topLeft[1]) / $module;
        $down = hypot($bottomLeft[0] - $topLeft[0], $bottomLeft[1] - $topLeft[1]) / $module;
        $estimate = (int) round(($across + $down) / 2) + 7;

        // Sizes are 17 + 4 * version: round to the nearest.
        $size = 17 + 4 * (int) round(($estimate - 17) / 4);

        return $size >= 21 && $size <= 177 ? $size : throw UnreadableCodeException::noCode();
    }

    /**
     * The middle of the bottom-right alignment pattern - a dark module ringed light, then dark -
     * found near where the finders put it.
     *
     * @return array{float, float}|null
     */
    private function alignment(Perspective $guess, int $size, float $module): ?array
    {
        $version = intdiv($size - 17, 4);

        if ($version < 2) {
            return null;
        }

        [$expectedX, $expectedY] = $guess->map($size - 6.5, $size - 6.5);
        $reach = (int) ceil($module * 7);
        $best = null;
        $bestDistance = INF;

        for ($y = (int) $expectedY - $reach; $y <= (int) $expectedY + $reach; $y++) {
            for ($x = (int) $expectedX - $reach; $x <= (int) $expectedX + $reach; $x++) {
                if (! $this->isAlignmentMiddle($x, $y, $module)) {
                    continue;
                }

                $distance = hypot($x - $expectedX, $y - $expectedY);

                if ($distance < $bestDistance) {
                    [$best, $bestDistance] = [[$x + 0.5, $y + 0.5], $distance];
                }
            }
        }

        return $best;
    }

    /**
     * Whether a pixel is the middle of an alignment pattern: sampled a module apart, a dark
     * middle in a light ring in a dark ring, five modules across. One module may be off.
     */
    private function isAlignmentMiddle(int $x, int $y, float $module): bool
    {
        $wrong = 0;

        for ($row = -2; $row <= 2; $row++) {
            for ($column = -2; $column <= 2; $column++) {
                $dark = max(abs($row), abs($column)) !== 1;

                if ($this->isDark((int) round($x + $column * $module), (int) round($y + $row * $module)) !== $dark && ++$wrong > 1) {
                    return false;
                }
            }
        }

        return true;
    }

    private function isDark(int $x, int $y): bool
    {
        return $x >= 0 && $y >= 0 && $x < $this->width && $y < $this->height && $this->dark[$y * $this->width + $x];
    }
}
