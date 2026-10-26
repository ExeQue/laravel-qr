<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;

/**
 * Paints each eye's inner part as a lighter or darker echo of its outer part's color.
 */
final class EyeEcho implements EyeFilter, NamedFilter
{
    use Scoped;

    /**
     * @param float $lightness Percent to lighten the inner part by; negative darkens.
     */
    public function __construct(
        private float $lightness = -25.0,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        $outer = $eye->paintOf($style->outer);

        if (! $this->appliesToEye($eye) || ! $outer instanceof Color) {
            return $style;
        }

        return $style->withInner($style->inner->withPaint($this->lightness < 0 ? $outer->darken(-$this->lightness) : $outer->lighten($this->lightness)));
    }

    public function name(): string
    {
        return 'eye-echo';
    }

    public function options(): array
    {
        return ['lightness' => $this->lightness, ...$this->scope()];
    }
}
