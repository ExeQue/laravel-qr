<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyePartStyle;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Each eye part tilted in depth by an amount of its own, about both axes, and turned a little.
 * The eyes stay where they are, as a scanner finds the code by them.
 */
final class EyeJitter3d implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @param float $tilt Up to this many degrees either way, about each axis.
     * @param float $rotate Up to this many degrees either way, on the screen.
     */
    public function __construct(
        private float $tilt = 12,
        private float $rotate = 0,
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
                ->tiltBy(
                    $eye->random(-$this->tilt, $this->tilt, "{$purpose}:x"),
                    $eye->random(-$this->tilt, $this->tilt, "{$purpose}:y"),
                )
                ->rotateBy($eye->random(-$this->rotate, $this->rotate, "{$purpose}:rotate"));
        };

        return $style->withOuter($jitter($style->outer, 'outer'))->withInner($jitter($style->inner, 'inner'));
    }

    public static function optionRules(): array
    {
        return [
            'tilt' => Options::number(0, 85),
            'rotate' => Options::number(0, 45),
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'eye-jitter-3d';
    }

    public function options(): array
    {
        return ['tilt' => $this->tilt, 'rotate' => $this->rotate, 'seed' => $this->seed, ...$this->scope()];
    }
}
