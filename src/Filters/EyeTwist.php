<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Turns the eyes a step more from corner to corner: top left by nothing, top right by one
 * step, bottom left by two.
 */
final class EyeTwist implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @param float $step Degrees from one corner to the next.
     */
    public function __construct(
        private float $step = 6.0,
        private bool $outer = true,
        private bool $inner = true,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        $degrees = $this->step * (int) array_search($eye->corner, Corner::cases(), true);

        return $style
            ->withOuter($this->outer ? $style->outer->rotateBy($degrees) : $style->outer)
            ->withInner($this->inner ? $style->inner->rotateBy($degrees) : $style->inner);
    }

    public static function optionRules(): array
    {
        return [
            'step' => Options::number(-45, 45),
            'outer' => Options::boolean(),
            'inner' => Options::boolean(),
        ];
    }

    public function name(): string
    {
        return 'eye-twist';
    }

    public function options(): array
    {
        return ['step' => $this->step, 'outer' => $this->outer, 'inner' => $this->inner, ...$this->scope()];
    }
}
