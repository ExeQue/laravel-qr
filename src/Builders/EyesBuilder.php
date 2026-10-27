<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use Closure;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * The three eyes. Each call returns a changed copy.
 */
final class EyesBuilder
{
    use Conditionable;
    use Macroable;
    use Tappable;

    public function __construct(
        private Style $style,
        private readonly Resolve $resolve,
    ) {
    }

    /**
     * The outer eye of every corner.
     */
    public function outer(OuterEyeShape|ProvidesQrOuterEye|string $eye, mixed ...$options): self
    {
        $shape = $this->resolve->outerEye($eye, $options);

        return $this->each(fn (EyeBuilder $corner): EyeBuilder => $corner->outer($shape));
    }

    /**
     * The inner eye of every corner.
     */
    public function inner(InnerEyeShape|ProvidesQrInnerEye|string $eye, mixed ...$options): self
    {
        $shape = $this->resolve->innerEye($eye, $options);

        return $this->each(fn (EyeBuilder $corner): EyeBuilder => $corner->inner($shape));
    }

    /**
     * Paints every eye.
     *
     * @param Paint|string|array<string, mixed> $paint
     */
    public function paint(Paint|string|array $paint): self
    {
        return $this->each(fn (EyeBuilder $corner): EyeBuilder => $corner->paint($paint));
    }

    /**
     * @param Closure(EyeBuilder): EyeBuilder $eye
     */
    public function topLeft(Closure $eye): self
    {
        return $this->corner(Corner::TopLeft, $eye);
    }

    /**
     * @param Closure(EyeBuilder): EyeBuilder $eye
     */
    public function topRight(Closure $eye): self
    {
        return $this->corner(Corner::TopRight, $eye);
    }

    /**
     * @param Closure(EyeBuilder): EyeBuilder $eye
     */
    public function bottomLeft(Closure $eye): self
    {
        return $this->corner(Corner::BottomLeft, $eye);
    }

    /**
     * The same change to every corner's eye.
     *
     * @param Closure(EyeBuilder): EyeBuilder $eye
     */
    public function each(Closure $eye): self
    {
        $builder = $this;

        foreach (Corner::cases() as $corner) {
            $builder = $builder->corner($corner, $eye);
        }

        return $builder;
    }

    /**
     * Turns the top-right and bottom-left eyes so a corner drawn top-left points outward on each.
     */
    public function mirrored(): self
    {
        return clone($this, ['style' => $this->style->mirrored()]);
    }

    /**
     * @param Closure(EyeBuilder): EyeBuilder $eye
     */
    public function corner(Corner $corner, Closure $eye): self
    {
        return clone($this, ['style' => $this->style->withEye($corner, $eye(new EyeBuilder($this->style->eye($corner), $this->resolve))->toEyeStyle())]);
    }

    public function toStyle(): Style
    {
        return $this->style;
    }
}
