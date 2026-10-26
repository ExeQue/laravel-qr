<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;

/**
 * Mirrors the eyes, left to right or top to bottom: a leaf, a drop or a heart then leans the
 * other way. Limit it to some corners with `corners()`.
 */
final class EyeFlip implements EyeFilter, NamedFilter
{
    use Scoped;

    public function __construct(
        private bool $horizontal = true,
        private bool $vertical = false,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        [$width, $height] = [$this->horizontal ? -1.0 : 1.0, $this->vertical ? -1.0 : 1.0];

        return $style
            ->withOuter($style->outer->scaleBy($width, $height))
            ->withInner($style->inner->scaleBy($width, $height));
    }

    public function name(): string
    {
        return 'eye-flip';
    }

    public function options(): array
    {
        return ['horizontal' => $this->horizontal, 'vertical' => $this->vertical, ...$this->scope()];
    }
}
