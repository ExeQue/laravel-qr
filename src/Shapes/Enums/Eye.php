<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Enums;

use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;

/**
 * The built-in eye presets, outer and inner together, by their registry names.
 */
enum Eye: string implements ProvidesQrEye
{
    case Square = 'square';
    case Round = 'round';
    case RoundCorner = 'round-corner';
    case SoftSquare = 'soft-square';
    case Dot = 'dot';
    case Rounded = 'rounded';
    case Mix = 'mix';
    case Heart = 'heart';
    case SoftRoundCorner = 'soft-round-corner';
    case Squircle = 'squircle';
    case Hexagon = 'hexagon';
    case Octagon = 'octagon';
    case Leaf = 'leaf';
    case Drop = 'drop';
    case Sparkle = 'sparkle';
    case Scalloped = 'scalloped';
    case Organic = 'organic';

    public function outer(): OuterEye
    {
        return match ($this) {
            self::Square, self::Round => OuterEye::Square,
            self::RoundCorner => OuterEye::RoundCorner,
            self::SoftSquare => OuterEye::SoftSquare,
            self::Dot => OuterEye::Circle,
            self::Rounded => OuterEye::Soft,
            self::Mix => OuterEye::Rounded,
            self::Heart, self::SoftRoundCorner => OuterEye::SoftRoundCorner,
            self::Squircle => OuterEye::Squircle,
            self::Hexagon => OuterEye::Hexagon,
            self::Octagon => OuterEye::Octagon,
            self::Leaf => OuterEye::Leaf,
            self::Drop => OuterEye::Drop,
            self::Sparkle, self::Organic => OuterEye::Soft,
            self::Scalloped => OuterEye::Scalloped,
        };
    }

    public function inner(): InnerEye
    {
        return match ($this) {
            self::Square => InnerEye::Square,
            self::Round, self::Dot => InnerEye::Circle,
            self::RoundCorner => InnerEye::RoundCorner,
            self::SoftSquare => InnerEye::SoftSquare,
            self::Rounded, self::Mix => InnerEye::Soft,
            self::Heart => InnerEye::Heart,
            self::SoftRoundCorner => InnerEye::SoftRoundCorner,
            self::Squircle => InnerEye::Squircle,
            self::Hexagon => InnerEye::Hexagon,
            self::Octagon => InnerEye::Octagon,
            self::Leaf => InnerEye::Leaf,
            self::Drop => InnerEye::Drop,
            self::Sparkle => InnerEye::Sparkle,
            self::Scalloped => InnerEye::Circle,
            self::Organic => InnerEye::Blob,
        };
    }

    public function qrOuterEye(): OuterEyeShape
    {
        return $this->outer()->qrOuterEye();
    }

    public function qrInnerEye(): InnerEyeShape
    {
        return $this->inner()->qrInnerEye();
    }
}
