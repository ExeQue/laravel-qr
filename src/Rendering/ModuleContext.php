<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Logos\Knockout;
use ExeQue\Qr\Support\Seed;

/**
 * One dark cell being drawn, and what is around it.
 */
final readonly class ModuleContext
{
    /**
     * The middle of the cell, in modules from the symbol's top left.
     */
    public float $x;

    public float $y;

    /**
     * @param array<string, bool> $neighbors Darkness by "dx,dy", for the eight cells around.
     * @param int $seed Stable per cell, for variation that never changes between renders.
     * @param Region|null $logo The area the code's logo clears, if it has one.
     */
    public function __construct(
        public int $column,
        public int $row,
        public int $size,
        public Role $role,
        private array $neighbors,
        public int $seed,
        public ?Region $logo = null,
    ) {
        $this->x = $column + 0.5;
        $this->y = $row + 0.5;
    }

    /**
     * The context of a cell. Finder cells count as light around it: the eyes are drawn on their own.
     */
    public static function of(Matrix $matrix, int $column, int $row, ?Region $logo = null, ?Knockout $knockout = null): self
    {
        $neighbors = [];

        // Cells the logo clears are not drawn, so shapes that join their neighbors stop short of them.
        foreach ([-1, 0, 1] as $dy) {
            foreach ([-1, 0, 1] as $dx) {
                $neighbors["{$dx},{$dy}"] = $matrix->isDark($column + $dx, $row + $dy)
                    && $matrix->role($column + $dx, $row + $dy) !== Role::Finder
                    && ! ($knockout?->clears($column + $dx, $row + $dy) ?? false);
            }
        }

        return new self($column, $row, $matrix->size, $matrix->role($column, $row) ?? Role::Data, $neighbors, crc32("{$column},{$row}"), $logo);
    }

    /**
     * A number from `$min` to `$max` that is the same for this cell every time,
     * different for each `$purpose`.
     */
    public function random(float $min = 0.0, float $max = 1.0, string $purpose = ''): float
    {
        return Seed::between($this->seed, $min, $max, $purpose);
    }

    /**
     * Whether the cell at an offset of -1, 0 or 1 each way is dark; (0, 0) is this cell, which is.
     */
    public function isDark(int $dx, int $dy): bool
    {
        return $dx === 0 && $dy === 0 ? true : ($this->neighbors["{$dx},{$dy}"] ?? false);
    }
}
