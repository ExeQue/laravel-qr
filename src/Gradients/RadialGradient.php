<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients;

use ExeQue\Qr\Exceptions\InvalidGradientException;
use ExeQue\Qr\Gradients\Concerns\HasStops;
use ExeQue\Qr\Gradients\Contracts\SerializableGradient;

/**
 * Colors in circles or ellipses out from a center, optionally seen from a focal point.
 */
final class RadialGradient implements SerializableGradient
{
    use HasStops;

    /**
     * @var array{float, float}
     */
    public private(set) array $center = [0.5, 0.5];

    /**
     * @var array{float, float} Horizontal and vertical.
     */
    public private(set) array $radius = [0.5, 0.5];

    /**
     * @var array{float, float}|null Where the gradient starts, inside the ellipse; the center when null.
     */
    public private(set) ?array $focal = null;

    public function center(float $x, float $y): static
    {
        return (clone($this, ['center' => [$x, $y]]))->checkedFocal();
    }

    /**
     * A circle, or an ellipse when the vertical radius differs.
     */
    public function radius(float $horizontal, ?float $vertical = null): static
    {
        foreach (['radius' => $horizontal, 'vertical radius' => $vertical ?? $horizontal] as $name => $value) {
            if ($value <= 0) {
                throw InvalidGradientException::notPositive($name, $value);
            }
        }

        return (clone($this, ['radius' => [$horizontal, $vertical ?? $horizontal]]))->checkedFocal();
    }

    /**
     * A focal point must stay inside the ellipse whatever moves it.
     */
    private function checkedFocal(): static
    {
        if ($this->focal !== null && $this->unitCircle(...$this->focal) ** 2 >= 1) {
            throw InvalidGradientException::focalOutsideRadius();
        }

        return $this;
    }

    public function focal(float $x, float $y): static
    {
        if ($this->unitCircle($x, $y) ** 2 >= 1) {
            throw InvalidGradientException::focalOutsideRadius();
        }

        return clone($this, ['focal' => [$x, $y]]);
    }

    public function toArray(): array
    {
        return [...$this->stopsToArray(), 'center' => $this->center, 'radius' => $this->radius, 'focal' => $this->focal];
    }

    public static function fromArray(array $data): static
    {
        [$x, $y] = $data['center'] ?? [0.5, 0.5];
        [$horizontal, $vertical] = $data['radius'] ?? [0.5, 0.5];

        $gradient = self::fromStops($data)->center((float) $x, (float) $y)->radius((float) $horizontal, (float) $vertical);

        return isset($data['focal']) ? $gradient->focal((float) $data['focal'][0], (float) $data['focal'][1]) : $gradient;
    }

    private function position(float $x, float $y): float
    {
        if ($this->focal === null) {
            return $this->unitCircle($x, $y);
        }

        // In the ellipse's own units it is a unit circle: find where the ray from the
        // focal point through (x, y) leaves it, and how far along that ray (x, y) is.
        $focal = $this->toUnit(...$this->focal);
        $point = $this->toUnit($x, $y);
        $direction = [$point[0] - $focal[0], $point[1] - $focal[1]];

        $a = $direction[0] ** 2 + $direction[1] ** 2;

        if ($a === 0.0) {
            return 0.0;
        }

        $b = 2 * ($focal[0] * $direction[0] + $focal[1] * $direction[1]);
        $c = $focal[0] ** 2 + $focal[1] ** 2 - 1;
        $exit = (-$b + sqrt($b ** 2 - 4 * $a * $c)) / (2 * $a);

        return 1 / $exit;
    }

    /**
     * Distance from the center, in radii.
     */
    private function unitCircle(float $x, float $y): float
    {
        [$unitX, $unitY] = $this->toUnit($x, $y);

        return sqrt($unitX ** 2 + $unitY ** 2);
    }

    /**
     * @return array{float, float}
     */
    private function toUnit(float $x, float $y): array
    {
        return [($x - $this->center[0]) / $this->radius[0], ($y - $this->center[1]) / $this->radius[1]];
    }
}
