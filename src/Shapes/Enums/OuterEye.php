<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Enums;

use ExeQue\Qr\Factory;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\Shapes\OuterEyes\DottedRing;
use ExeQue\Qr\Shapes\OuterEyes\Hexagon;
use ExeQue\Qr\Shapes\OuterEyes\Octagon;
use ExeQue\Qr\Shapes\OuterEyes\Rounded;
use ExeQue\Qr\Shapes\OuterEyes\Scalloped;
use ExeQue\Qr\Shapes\OuterEyes\Square;
use ExeQue\Qr\Shapes\OuterEyes\Squircle;

/**
 * The built-in outer eyes, by their registry names.
 */
enum OuterEye: string implements ProvidesQrOuterEye
{
    case Square = 'square';
    case SoftSquare = 'soft-square';
    case Soft = 'soft';
    case Rounded = 'rounded';
    case Circle = 'circle';
    case RoundCorner = 'round-corner';
    case SoftRoundCorner = 'soft-round-corner';
    case Squircle = 'squircle';
    case Octagon = 'octagon';
    case Hexagon = 'hexagon';
    case HexagonStanding = 'hexagon-standing';
    case Leaf = 'leaf';
    case Drop = 'drop';
    case Scalloped = 'scalloped';
    case DottedRing = 'dotted-ring';

    /**
     * The shape's class, for building it with options.
     *
     * @return class-string<OuterEyeShape>
     */
    public function class(): string
    {
        return match ($this) {
            self::Square => Square::class,
            self::Squircle => Squircle::class,
            self::Octagon => Octagon::class,
            self::Hexagon, self::HexagonStanding => Hexagon::class,
            self::Scalloped => Scalloped::class,
            self::DottedRing => DottedRing::class,
            default => Rounded::class,
        };
    }

    /**
     * The options this case sets on its class; the class's defaults for the rest.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return match ($this) {
            self::SoftSquare => ['outer' => 0.6, 'hole' => 0.3],
            self::Soft => ['outer' => 1.9, 'hole' => 0.9],
            self::Rounded => ['outer' => 2.7, 'hole' => 1.7],
            self::Circle => ['outer' => 3.5, 'hole' => 2.5],
            self::RoundCorner => ['outer' => [0.0, 0.0, 0.0, 3.5], 'hole' => [0.0, 0.0, 0.0, 2.5]],
            self::SoftRoundCorner => ['outer' => [1.9, 1.9, 1.9, 3.5], 'hole' => [0.9, 0.9, 0.9, 2.5]],
            self::Leaf => ['outer' => [3.0, 0.0, 3.0, 0.0], 'hole' => [2.0, 0.0, 2.0, 0.0]],
            self::Drop => ['outer' => [3.0, 3.0, 3.0, 0.0], 'hole' => [2.0, 2.0, 2.0, 0.0]],
            self::HexagonStanding => ['standing' => true],
            default => [],
        };
    }

    public function qrOuterEye(): OuterEyeShape
    {
        return app(Factory::class)->make($this->class(), $this->options());
    }
}
