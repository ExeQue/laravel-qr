<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Enums;

use ExeQue\Qr\Factory;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\InnerEyes\Blob;
use ExeQue\Qr\Shapes\InnerEyes\ChristmasTree;
use ExeQue\Qr\Shapes\InnerEyes\Clover;
use ExeQue\Qr\Shapes\InnerEyes\Cross;
use ExeQue\Qr\Shapes\InnerEyes\Diamond;
use ExeQue\Qr\Shapes\InnerEyes\Flower;
use ExeQue\Qr\Shapes\InnerEyes\Ghost;
use ExeQue\Qr\Shapes\InnerEyes\Heart;
use ExeQue\Qr\Shapes\InnerEyes\Hexagon;
use ExeQue\Qr\Shapes\InnerEyes\Octagon;
use ExeQue\Qr\Shapes\InnerEyes\Pill;
use ExeQue\Qr\Shapes\InnerEyes\Pumpkin;
use ExeQue\Qr\Shapes\InnerEyes\Rounded;
use ExeQue\Qr\Shapes\InnerEyes\Sparkle;
use ExeQue\Qr\Shapes\InnerEyes\Square;
use ExeQue\Qr\Shapes\InnerEyes\Squircle;
use ExeQue\Qr\Shapes\InnerEyes\Star;

/**
 * The built-in inner eyes, by their registry names.
 */
enum InnerEye: string implements ProvidesQrInnerEye
{
    case Square = 'square';
    case SoftSquare = 'soft-square';
    case Soft = 'soft';
    case Circle = 'circle';
    case RoundCorner = 'round-corner';
    case SoftRoundCorner = 'soft-round-corner';
    case Squircle = 'squircle';
    case Heart = 'heart';
    case Star = 'star';
    case Flower = 'flower';
    case Diamond = 'diamond';
    case Hexagon = 'hexagon';
    case HexagonStanding = 'hexagon-standing';
    case Octagon = 'octagon';
    case Leaf = 'leaf';
    case Drop = 'drop';
    case Sparkle = 'sparkle';
    case Clover = 'clover';
    case Cross = 'cross';
    case Pill = 'pill';
    case Blob = 'blob';
    case ChristmasTree = 'christmas-tree';
    case Ghost = 'ghost';
    case Pumpkin = 'pumpkin';

    /**
     * The shape's class, for building it with options.
     *
     * @return class-string<InnerEyeShape>
     */
    public function class(): string
    {
        return match ($this) {
            self::Square => Square::class,
            self::Squircle => Squircle::class,
            self::Heart => Heart::class,
            self::Star => Star::class,
            self::Flower => Flower::class,
            self::Diamond => Diamond::class,
            self::Hexagon, self::HexagonStanding => Hexagon::class,
            self::Octagon => Octagon::class,
            self::Sparkle => Sparkle::class,
            self::Clover => Clover::class,
            self::Cross => Cross::class,
            self::Pill => Pill::class,
            self::Blob => Blob::class,
            self::ChristmasTree => ChristmasTree::class,
            self::Ghost => Ghost::class,
            self::Pumpkin => Pumpkin::class,
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
            self::SoftSquare => ['radius' => 0.5],
            self::Soft => ['radius' => 0.7],
            self::Circle => ['radius' => 1.5],
            self::RoundCorner => ['radius' => [0.0, 0.0, 0.0, 1.5]],
            self::SoftRoundCorner => ['radius' => [0.7, 0.7, 0.7, 1.5]],
            self::Leaf => ['radius' => [1.3, 0.0, 1.3, 0.0]],
            self::Drop => ['radius' => [1.3, 1.3, 1.3, 0.0]],
            self::HexagonStanding => ['standing' => true],
            default => [],
        };
    }

    public function qrInnerEye(): InnerEyeShape
    {
        return app(Factory::class)->make($this->class(), $this->options());
    }
}
