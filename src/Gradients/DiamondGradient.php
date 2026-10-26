<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients;

use ExeQue\Qr\Exceptions\InvalidGradientException;
use ExeQue\Qr\Gradients\Concerns\HasStops;
use ExeQue\Qr\Gradients\Contracts\SerializableGradient;

/**
 * Colors in diamonds out from a center: the distance is across plus along.
 */
final class DiamondGradient implements SerializableGradient
{
    use HasStops;

    /**
     * @var array{float, float}
     */
    public private(set) array $center = [0.5, 0.5];

    public private(set) float $radius = 0.5;

    public function center(float $x, float $y): static
    {
        return clone($this, ['center' => [$x, $y]]);
    }

    /**
     * From the center to a corner of the diamond.
     */
    public function radius(float $radius): static
    {
        if ($radius <= 0) {
            throw InvalidGradientException::notPositive('radius', $radius);
        }

        return clone($this, ['radius' => $radius]);
    }

    public function toArray(): array
    {
        return [...$this->stopsToArray(), 'center' => $this->center, 'radius' => $this->radius];
    }

    public static function fromArray(array $data): static
    {
        [$x, $y] = $data['center'] ?? [0.5, 0.5];

        return self::fromStops($data)->center((float) $x, (float) $y)->radius((float) ($data['radius'] ?? 0.5));
    }

    private function position(float $x, float $y): float
    {
        return (abs($x - $this->center[0]) + abs($y - $this->center[1])) / $this->radius;
    }
}
