<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Enums;

/**
 * The corner a module shape points to, or a corner picked for each module.
 */
enum Direction: string
{
    case TopLeft = 'top-left';
    case TopRight = 'top-right';
    case BottomRight = 'bottom-right';
    case BottomLeft = 'bottom-left';
    case Random = 'random';

    /**
     * The four corners, without Random.
     *
     * @return non-empty-list<self>
     */
    public static function corners(): array
    {
        return [self::TopLeft, self::TopRight, self::BottomRight, self::BottomLeft];
    }

    /**
     * Where the corner falls in a rounded rectangle's radii: top-right, bottom-right, bottom-left, top-left.
     */
    public function radiusIndex(): int
    {
        return match ($this) {
            self::TopRight => 0,
            self::BottomRight => 1,
            self::BottomLeft => 2,
            self::TopLeft, self::Random => 3,
        };
    }

    /**
     * The corner across from this one; Random has none, and stays Random.
     */
    public function opposite(): self
    {
        return match ($this) {
            self::TopLeft => self::BottomRight,
            self::TopRight => self::BottomLeft,
            self::BottomRight => self::TopLeft,
            self::BottomLeft => self::TopRight,
            self::Random => self::Random,
        };
    }
}
