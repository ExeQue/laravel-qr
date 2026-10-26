<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyePartStyle;
use ExeQue\Qr\Rendering\EyeStyle;

/**
 * Each eye a little lighter or darker than its color, and its hue a little turned: one shade
 * per corner, for both its parts. Gradients are left as they are.
 */
final class EyeShade implements EyeFilter, NamedFilter
{
    use Scoped;

    /**
     * @param float $min Lightness to add, in percent; negative darkens.
     * @param float $max
     * @param float $hue Degrees the hue may turn either way.
     */
    public function __construct(
        private float $min = -15.0,
        private float $max = 15.0,
        private float $hue = 0.0,
        private int $seed = 0,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";
        $lightness = $eye->random($this->min, $this->max, "{$purpose}:lightness");
        $turn = $this->hue === 0.0 ? 0.0 : $eye->random(-$this->hue, $this->hue, "{$purpose}:hue");
        $shade = function (EyePartStyle $part) use ($eye, $lightness, $turn): EyePartStyle {
            $color = $eye->paintOf($part);

            if (! $color instanceof Color) {
                return $part;
            }

            $color = $lightness < 0 ? $color->darken(-$lightness) : $color->lighten($lightness);

            return $part->withPaint($turn === 0.0 ? $color : $color->shift($turn));
        };

        return $style->withOuter($shade($style->outer))->withInner($shade($style->inner));
    }

    public function name(): string
    {
        return 'eye-shade';
    }

    public function options(): array
    {
        return ['min' => $this->min, 'max' => $this->max, 'hue' => $this->hue, 'seed' => $this->seed, ...$this->scope()];
    }
}
