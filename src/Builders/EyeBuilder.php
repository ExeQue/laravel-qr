<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\Support\Hydrate;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * One corner's eye. Each call returns a changed copy.
 */
final class EyeBuilder
{
    use Conditionable;
    use Macroable;
    use Tappable;

    public function __construct(
        private EyeStyle $style,
        private readonly Resolve $resolve,
    ) {
    }

    /**
     * A whole eye: an outer and an inner together.
     */
    public function whole(ProvidesQrEye|string $eye): self
    {
        $whole = $this->resolve->eye($eye);

        return $this->outer($whole->qrOuterEye())->inner($whole->qrInnerEye());
    }

    public function outer(OuterEyeShape|ProvidesQrOuterEye|string $eye, mixed ...$options): self
    {
        return $this->with($this->style->withOuter($this->style->outer->withShape($this->resolve->outerEye($eye, $options))));
    }

    public function inner(InnerEyeShape|ProvidesQrInnerEye|string $eye, mixed ...$options): self
    {
        return $this->with($this->style->withInner($this->style->inner->withShape($this->resolve->innerEye($eye, $options))));
    }

    /**
     * Turns both parts, in degrees.
     */
    public function rotate(float $degrees): self
    {
        return $this->with($this->style->withRotation($degrees));
    }

    /**
     * Paints both parts.
     *
     * @param Paint|string|array<string, mixed> $paint
     */
    public function paint(Paint|string|array $paint): self
    {
        return $this->with($this->style->withPaint(Hydrate::paint($paint)));
    }

    /**
     * @param Paint|string|array<string, mixed> $paint
     */
    public function outerPaint(Paint|string|array $paint): self
    {
        return $this->with($this->style->withOuter($this->style->outer->withPaint(Hydrate::paint($paint))));
    }

    /**
     * @param Paint|string|array<string, mixed> $paint
     */
    public function innerPaint(Paint|string|array $paint): self
    {
        return $this->with($this->style->withInner($this->style->inner->withPaint(Hydrate::paint($paint))));
    }

    public function toEyeStyle(): EyeStyle
    {
        return $this->style;
    }

    private function with(EyeStyle $style): self
    {
        return clone($this, ['style' => $style]);
    }
}
