<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * How the outer or the inner part of an eye is drawn. Every `with*()` returns a changed copy.
 */
final readonly class EyePartStyle
{
    /**
     * @param Paint|null $paint Null takes the modules' paint.
     * @param float $rotation Degrees, about the eye's middle.
     */
    public function __construct(
        public OuterEyeShape|InnerEyeShape $shape,
        public ?Paint $paint = null,
        public float $rotation = 0.0,
        public float $scaleX = 1.0,
        public float $scaleY = 1.0,
        public float $opacity = 1.0,
    ) {
    }

    public function withShape(OuterEyeShape|InnerEyeShape $shape): self
    {
        return clone($this, ['shape' => $shape]);
    }

    public function withPaint(?Paint $paint): self
    {
        return clone($this, ['paint' => $paint]);
    }

    public function withRotation(float $degrees): self
    {
        return clone($this, ['rotation' => $degrees]);
    }

    public function withSize(float $width, ?float $height = null): self
    {
        return clone($this, ['scaleX' => $width, 'scaleY' => $height ?? $width]);
    }

    public function withOpacity(float $opacity): self
    {
        return clone($this, ['opacity' => $opacity]);
    }

    public function rotateBy(float $degrees): self
    {
        return $this->withRotation($this->rotation + $degrees);
    }

    public function scaleBy(float $width, ?float $height = null): self
    {
        return $this->withSize($this->scaleX * $width, $this->scaleY * ($height ?? $width));
    }

    public function fadeBy(float $factor): self
    {
        return $this->withOpacity($this->opacity * $factor);
    }

    public function transform(): Transform
    {
        return Transform::identity()->scale($this->scaleX, $this->scaleY)->rotate($this->rotation);
    }
}
