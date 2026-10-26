<?php

declare(strict_types=1);

namespace ExeQue\Qr\Geometry;

use ExeQue\Qr\Support\Number;

/**
 * An affine transform, built up step by step: each step applies after the ones before it.
 * Units are modules; y points down, so a positive rotation turns clockwise on screen.
 */
final readonly class Transform
{
    /**
     * @param array{float, float, float, float, float, float} $matrix `[a, b, c, d, e, f]`: x' = ax + cy + e, y' = bx + dy + f.
     * @param list<string> $steps The steps, for reading.
     */
    private function __construct(
        private array $matrix = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0],
        private array $steps = [],
    ) {
    }

    public static function identity(): self
    {
        return new self();
    }

    public function translate(float $x, float $y): self
    {
        if ($x === 0.0 && $y === 0.0) {
            return $this;
        }

        return $this->then([1, 0, 0, 1, $x, $y], 'translate('.Number::format($x).' '.Number::format($y).')');
    }

    /**
     * About the origin, in degrees.
     */
    public function rotate(float $degrees): self
    {
        if (fmod($degrees, 360) === 0.0) {
            return $this;
        }

        $radians = deg2rad($degrees);
        [$cos, $sin] = [cos($radians), sin($radians)];

        return $this->then([$cos, $sin, -$sin, $cos, 0, 0], 'rotate('.Number::format($degrees).')');
    }

    /**
     * About the origin.
     */
    public function scale(float $x, ?float $y = null): self
    {
        $y ??= $x;

        if ($x === 1.0 && $y === 1.0) {
            return $this;
        }

        return $this->then([$x, 0, 0, $y, 0, 0], 'scale('.Number::format($x).($x === $y ? '' : ' '.Number::format($y)).')');
    }

    /**
     * Along x and y, in degrees.
     */
    public function skew(float $x, float $y = 0.0): self
    {
        if ($x === 0.0 && $y === 0.0) {
            return $this;
        }

        return $this->then([1, tan(deg2rad($y)), tan(deg2rad($x)), 1, 0, 0], 'skew('.Number::format($x).' '.Number::format($y).')');
    }

    /**
     * @return array{float, float}
     */
    public function apply(float $x, float $y): array
    {
        [$a, $b, $c, $d, $e, $f] = $this->matrix;

        return [$a * $x + $c * $y + $e, $b * $x + $d * $y + $f];
    }

    public function isIdentity(): bool
    {
        return $this->steps === [];
    }

    /**
     * The steps in order, such as `rotate(15) translate(3.5 3.5)`; `identity` when there are none.
     */
    public function toString(): string
    {
        return $this->steps === [] ? 'identity' : implode(' ', $this->steps);
    }

    /**
     * @param array{float|int, float|int, float|int, float|int, float|int, float|int} $next
     */
    private function then(array $next, string $step): self
    {
        [$a, $b, $c, $d, $e, $f] = $this->matrix;
        [$na, $nb, $nc, $nd, $ne, $nf] = $next;

        return new self([
            $na * $a + $nc * $b,
            $nb * $a + $nd * $b,
            $na * $c + $nc * $d,
            $nb * $c + $nd * $d,
            $na * $e + $nc * $f + $ne,
            $nb * $e + $nd * $f + $nf,
        ], [...$this->steps, $step]);
    }
}
