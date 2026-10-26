<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering\Enums;

/**
 * The three corners with a finder pattern.
 */
enum Corner: string
{
    case TopLeft = 'top-left';
    case TopRight = 'top-right';
    case BottomLeft = 'bottom-left';

    /**
     * The turn, in degrees, that points an eye's top-left corner out of the code from this corner.
     */
    public function turn(): float
    {
        return match ($this) {
            self::TopLeft => 0.0,
            self::TopRight => 90.0,
            self::BottomLeft => -90.0,
        };
    }

    /**
     * The middle of this corner's eye, in modules from the symbol's top left.
     *
     * @return array{float, float}
     */
    public function center(int $size): array
    {
        return match ($this) {
            self::TopLeft => [3.5, 3.5],
            self::TopRight => [$size - 3.5, 3.5],
            self::BottomLeft => [3.5, $size - 3.5],
        };
    }
}
