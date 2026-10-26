<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyePartStyle;
use ExeQue\Qr\Rendering\EyeStyle;

/**
 * A little of everything for each eye part: turned, sized and faded by an amount of its own.
 * The eyes stay where they are, as a scanner finds the code by them.
 */
final class EyeJitter implements EyeFilter, NamedFilter
{
    use Scoped;

    /**
     * @param float $rotate Degrees either way.
     * @param float $size A share either way: 0.08 is 92 % to 108 %.
     * @param float $opacity A share to fade by, at most.
     */
    public function __construct(
        private float $rotate = 6.0,
        private float $size = 0.06,
        private float $opacity = 0.0,
        private int $seed = 0,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        $jitter = function (EyePartStyle $part, string $name) use ($eye): EyePartStyle {
            $purpose = "{$this->name()}:{$this->seed}:{$name}";

            return $part
                ->rotateBy($eye->random(-$this->rotate, $this->rotate, "{$purpose}:rotate"))
                ->scaleBy(1 + $eye->random(-$this->size, $this->size, "{$purpose}:size"))
                ->fadeBy(1 - $eye->random(0.0, $this->opacity, "{$purpose}:opacity"));
        };

        return $style->withOuter($jitter($style->outer, 'outer'))->withInner($jitter($style->inner, 'inner'));
    }

    public function name(): string
    {
        return 'eye-jitter';
    }

    public function options(): array
    {
        return ['rotate' => $this->rotate, 'size' => $this->size, 'opacity' => $this->opacity, 'seed' => $this->seed, ...$this->scope()];
    }
}
