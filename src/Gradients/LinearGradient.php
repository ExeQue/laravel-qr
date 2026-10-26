<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients;

use ExeQue\Qr\Gradients\Concerns\HasStops;
use ExeQue\Qr\Gradients\Contracts\SerializableGradient;

/**
 * Colors along a line: at an angle across the box, or between two points.
 */
final class LinearGradient implements SerializableGradient
{
    use HasStops;

    /**
     * Degrees as CSS has them: 0 runs upwards, 90 to the right, 180 (the default) downwards.
     */
    public private(set) float $angle = 180.0;

    /**
     * @var array{array{float, float}, array{float, float}}|null Start and end, when set as points.
     */
    public private(set) ?array $points = null;

    public function angle(float $degrees): static
    {
        return clone($this, ['angle' => $degrees, 'points' => null]);
    }

    /**
     * From one point of the box to another, 0-1 on both axes.
     */
    public function between(float $startX, float $startY, float $endX, float $endY): static
    {
        return clone($this, ['points' => [[$startX, $startY], [$endX, $endY]]]);
    }

    /**
     * @return array{array{float, float}, array{float, float}}
     */
    public function line(): array
    {
        if ($this->points !== null) {
            return $this->points;
        }

        // As CSS draws it: through the middle, long enough that the corners get the end colors.
        $radians = deg2rad($this->angle);
        [$dx, $dy] = [sin($radians), -cos($radians)];
        $half = (abs($dx) + abs($dy)) / 2;

        return [[0.5 - $dx * $half, 0.5 - $dy * $half], [0.5 + $dx * $half, 0.5 + $dy * $half]];
    }

    public function toArray(): array
    {
        return [...$this->stopsToArray(), 'angle' => $this->angle, 'points' => $this->points];
    }

    public static function fromArray(array $data): static
    {
        $gradient = self::fromStops($data)->angle((float) ($data['angle'] ?? 180));

        if (isset($data['points'])) {
            [[$startX, $startY], [$endX, $endY]] = $data['points'];
            $gradient = $gradient->between((float) $startX, (float) $startY, (float) $endX, (float) $endY);
        }

        return $gradient;
    }

    private function position(float $x, float $y): float
    {
        [[$startX, $startY], [$endX, $endY]] = $this->line();
        [$dx, $dy] = [$endX - $startX, $endY - $startY];
        $length = $dx ** 2 + $dy ** 2;

        return $length === 0.0 ? 0.0 : (($x - $startX) * $dx + ($y - $startY) * $dy) / $length;
    }
}
