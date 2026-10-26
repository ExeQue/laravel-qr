<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * How one module is drawn. Every `with*()` returns a changed copy.
 */
final readonly class ModuleStyle
{
    /**
     * @param float $rotation Degrees, about the cell's middle.
     * @param float $offsetX In modules.
     * @param float $skewX Degrees.
     * @param bool $skip Draw nothing.
     */
    public function __construct(
        public ModuleShape $shape,
        public Paint $paint,
        public float $rotation = 0.0,
        public float $scaleX = 1.0,
        public float $scaleY = 1.0,
        public float $offsetX = 0.0,
        public float $offsetY = 0.0,
        public float $skewX = 0.0,
        public float $skewY = 0.0,
        public float $opacity = 1.0,
        public bool $skip = false,
    ) {
    }

    public function withShape(ModuleShape $shape): self
    {
        return clone($this, ['shape' => $shape]);
    }

    public function withPaint(Paint $paint): self
    {
        return clone($this, ['paint' => $paint]);
    }

    public function withRotate(float $degrees): self
    {
        return clone($this, ['rotation' => $degrees]);
    }

    public function withSize(float $width, ?float $height = null): self
    {
        return clone($this, ['scaleX' => $width, 'scaleY' => $height ?? $width]);
    }

    public function withOffset(float $x, float $y): self
    {
        return clone($this, ['offsetX' => $x, 'offsetY' => $y]);
    }

    public function withSkew(float $x, float $y = 0.0): self
    {
        return clone($this, ['skewX' => $x, 'skewY' => $y]);
    }

    public function withOpacity(float $opacity): self
    {
        return clone($this, ['opacity' => $opacity]);
    }

    public function withSkip(bool $skip = true): self
    {
        return clone($this, ['skip' => $skip]);
    }

    /**
     * Turns further, on top of the rotation so far.
     */
    public function rotateBy(float $degrees): self
    {
        return $this->withRotate($this->rotation + $degrees);
    }

    /**
     * Scales the size so far.
     */
    public function scaleBy(float $width, ?float $height = null): self
    {
        return $this->withSize($this->scaleX * $width, $this->scaleY * ($height ?? $width));
    }

    /**
     * Moves further, on top of the offset so far.
     */
    public function offsetBy(float $x, float $y): self
    {
        return $this->withOffset($this->offsetX + $x, $this->offsetY + $y);
    }

    /**
     * Multiplies the opacity so far.
     */
    public function fadeBy(float $factor): self
    {
        return $this->withOpacity($this->opacity * $factor);
    }

    /**
     * Size, skew and rotation about the cell's middle, then the offset.
     */
    public function transform(): Transform
    {
        return Transform::identity()
            ->scale($this->scaleX, $this->scaleY)
            ->skew($this->skewX, $this->skewY)
            ->rotate($this->rotation)
            ->translate($this->offsetX, $this->offsetY);
    }
}
