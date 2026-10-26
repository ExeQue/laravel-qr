<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Support\Hydrate;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * What is behind the code. Each call returns a changed copy.
 */
final class BackgroundBuilder
{
    use Conditionable;
    use Macroable;
    use Tappable;

    public function __construct(
        private ?Paint $paint,
    ) {
    }

    /**
     * Any color form, a gradient, or a stored gradient's array.
     *
     * @param Paint|string|array<string, mixed> $paint
     */
    public function paint(Paint|string|array $paint): self
    {
        return clone($this, ['paint' => Hydrate::paint($paint)]);
    }

    /**
     * No background: what is behind the image shows.
     */
    public function transparent(): self
    {
        return clone($this, ['paint' => null]);
    }

    public function toPaint(): ?Paint
    {
        return $this->paint;
    }
}
