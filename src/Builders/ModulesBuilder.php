<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Support\Hydrate;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * How the modules are drawn. Each call returns a changed copy.
 */
final class ModulesBuilder
{
    use Conditionable;
    use Macroable;
    use Tappable;

    public function __construct(
        private ModuleStyle $style,
        private readonly Resolve $resolve,
    ) {
    }

    /**
     * An instance, a provider, a class name or a registered name, with its options.
     */
    public function shape(ModuleShape|ProvidesQrModule|string $shape, mixed ...$options): self
    {
        return $this->with($this->style->withShape($this->resolve->module($shape, $options)));
    }

    /**
     * @param Paint|string|array<string, mixed> $paint
     */
    public function paint(Paint|string|array $paint): self
    {
        return $this->with($this->style->withPaint(Hydrate::paint($paint)));
    }

    public function opacity(float $opacity): self
    {
        return $this->with($this->style->withOpacity($opacity));
    }

    public function size(float $width, ?float $height = null): self
    {
        return $this->with($this->style->withSize($width, $height));
    }

    public function rotate(float $degrees): self
    {
        return $this->with($this->style->withRotate($degrees));
    }

    public function toModuleStyle(): ModuleStyle
    {
        return $this->style;
    }

    private function with(ModuleStyle $style): self
    {
        return clone($this, ['style' => $style]);
    }
}
