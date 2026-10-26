<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;

/**
 * Scales each eye's inner part. The outer part keeps its size, which readers measure.
 */
final class EyeSize implements EyeFilter, NamedFilter
{
    use Scoped;

    public function __construct(
        private float $inner = 0.85,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        return $this->appliesToEye($eye) ? $style->withInner($style->inner->scaleBy($this->inner)) : $style;
    }

    public function name(): string
    {
        return 'eye-size';
    }

    public function options(): array
    {
        return ['inner' => $this->inner, ...$this->scope()];
    }
}
