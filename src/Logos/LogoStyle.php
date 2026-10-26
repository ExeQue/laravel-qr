<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Logos\Enums\LogoShape;

/**
 * Where and how a logo sits on a code. Every `with*()` returns a changed copy.
 */
final readonly class LogoStyle
{
    /**
     * @param float $size The logo's width as a share of the symbol's, quiet zone not counted.
     * @param int $padding Modules of clear space around the logo.
     * @param Paint|null $plate A backing behind the logo and its padding; none when null.
     * @param float $plateRadius The plate's corner radius, in modules, for a square or rounded shape.
     * @param array{float, float}|null $position The logo's top-left corner in modules from the symbol's;
     *                                           null centers it, shrinking it if it would cover a function pattern.
     */
    public function __construct(
        public Logo $logo,
        public float $size = 0.2,
        public int $padding = 1,
        public LogoShape $shape = LogoShape::Square,
        public ?Paint $plate = null,
        public float $plateRadius = 0.0,
        public ?array $position = null,
    ) {
        self::checkSize($size);
        self::checkPadding($padding);
    }

    public function withSize(float $size): self
    {
        self::checkSize($size);

        return clone($this, ['size' => $size]);
    }

    public function withPadding(int $padding): self
    {
        self::checkPadding($padding);

        return clone($this, ['padding' => $padding]);
    }

    public function withShape(LogoShape $shape): self
    {
        return clone($this, ['shape' => $shape]);
    }

    public function withPlate(?Paint $plate, float $radius = 0.0): self
    {
        return clone($this, ['plate' => $plate, 'plateRadius' => $radius]);
    }

    /**
     * @param array{float, float}|null $position
     */
    public function withPosition(?array $position): self
    {
        return clone($this, ['position' => $position]);
    }

    private static function checkSize(float $size): void
    {
        if ($size <= 0 || $size > 1) {
            throw InvalidLogoException::sizeOutOfRange($size);
        }
    }

    private static function checkPadding(int $padding): void
    {
        if ($padding < 0) {
            throw InvalidLogoException::negativePadding($padding);
        }
    }
}
