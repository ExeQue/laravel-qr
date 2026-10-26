<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Stretches the eyes wider than tall, or taller than wide. The scan guards hold how far.
 */
final class EyeStretch implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @param float $width The width, as a share of the shape's own.
     * @param float $height The height, as a share of the shape's own.
     */
    public function __construct(
        private float $width = 1.12,
        private float $height = 0.9,
        private bool $outer = true,
        private bool $inner = true,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        return $style
            ->withOuter($this->outer ? $style->outer->scaleBy($this->width, $this->height) : $style->outer)
            ->withInner($this->inner ? $style->inner->scaleBy($this->width, $this->height) : $style->inner);
    }

    public static function optionRules(): array
    {
        return [
            'width' => Options::number(0.5, 1.5),
            'height' => Options::number(0.5, 1.5),
            'outer' => Options::boolean(),
            'inner' => Options::boolean(),
        ];
    }

    public function name(): string
    {
        return 'eye-stretch';
    }

    public function options(): array
    {
        return ['width' => $this->width, 'height' => $this->height, 'outer' => $this->outer, 'inner' => $this->inner, ...$this->scope()];
    }
}
