<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * How one eye is drawn: its outer ring and its inner square, each on its own.
 */
final readonly class EyeStyle
{
    public function __construct(
        public EyePartStyle $outer,
        public EyePartStyle $inner,
    ) {
    }

    public static function of(OuterEyeShape $outer, InnerEyeShape $inner): self
    {
        return new self(new EyePartStyle($outer), new EyePartStyle($inner));
    }

    public function withOuter(EyePartStyle $outer): self
    {
        return clone($this, ['outer' => $outer]);
    }

    public function withInner(EyePartStyle $inner): self
    {
        return clone($this, ['inner' => $inner]);
    }

    /**
     * Turns both parts.
     */
    public function withRotate(float $degrees): self
    {
        return new self($this->outer->withRotate($degrees), $this->inner->withRotate($degrees));
    }

    /**
     * Turns both parts further.
     */
    public function rotateBy(float $degrees): self
    {
        return new self($this->outer->rotateBy($degrees), $this->inner->rotateBy($degrees));
    }

    /**
     * Paints both parts.
     */
    public function withPaint(?Paint $paint): self
    {
        return new self($this->outer->withPaint($paint), $this->inner->withPaint($paint));
    }
}
