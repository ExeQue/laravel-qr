<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Support\Seed;

/**
 * One eye being drawn.
 */
final readonly class EyeContext
{
    /**
     * The middle of the eye, in modules from the symbol's top left.
     */
    public float $x;

    public float $y;

    /**
     * @param int $seed Stable per corner, for variation that never changes between renders.
     */
    public function __construct(
        public Corner $corner,
        public int $size,
        public int $seed,
    ) {
        [$this->x, $this->y] = $corner->center($size);
    }

    /**
     * A number from `$min` to `$max` that is the same for this corner every time,
     * different for each `$purpose`.
     */
    public function random(float $min = 0.0, float $max = 1.0, string $purpose = ''): float
    {
        return Seed::between($this->seed, $min, $max, $purpose);
    }

    public static function of(Corner $corner, int $size): self
    {
        return new self($corner, $size, crc32($corner->value));
    }
}
