<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients;

use ExeQue\Qr\Colors\Convert;
use ExeQue\Qr\Gradients\Concerns\HasStops;
use ExeQue\Qr\Gradients\Contracts\SerializableGradient;

/**
 * Colors sweeping round a center, clockwise from a start angle, as CSS's `conic-gradient`.
 */
final class ConicGradient implements SerializableGradient
{
    use HasStops;

    /**
     * @var array{float, float}
     */
    public private(set) array $center = [0.5, 0.5];

    /**
     * Degrees: 0 (the default) points up, 90 to the right.
     */
    public private(set) float $startAngle = 0.0;

    public function center(float $x, float $y): static
    {
        return clone($this, ['center' => [$x, $y]]);
    }

    public function startAngle(float $degrees): static
    {
        return clone($this, ['startAngle' => $degrees]);
    }

    public function toArray(): array
    {
        return [...$this->stopsToArray(), 'center' => $this->center, 'startAngle' => $this->startAngle];
    }

    public static function fromArray(array $data): static
    {
        [$x, $y] = $data['center'] ?? [0.5, 0.5];

        return self::fromStops($data)->center((float) $x, (float) $y)->startAngle((float) ($data['startAngle'] ?? 0));
    }

    private function position(float $x, float $y): float
    {
        // Clockwise from straight up, with y downwards.
        $angle = rad2deg(atan2($x - $this->center[0], $this->center[1] - $y));

        return Convert::normalizeHue($angle - $this->startAngle) / 360;
    }
}
