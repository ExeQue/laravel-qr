<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Numbers;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Turns each eye's outer and inner part, separately: by a fixed angle, or by
 * one of their own within a range.
 */
final class EyeTilt implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @param float|array{float, float} $outer Degrees, or a range to pick from per corner.
     * @param float|array{float, float} $inner Degrees, or a range to pick from per corner.
     */
    public function __construct(
        private float|array $outer = 4,
        private float|array $inner = -7,
        private int $seed = 0,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        return $style
            ->withOuter($style->outer->rotateBy($this->degrees($this->outer, $eye, 'outer')))
            ->withInner($style->inner->rotateBy($this->degrees($this->inner, $eye, 'inner')));
    }

    public static function optionRules(): array
    {
        return [
            'outer' => ['sometimes', new Numbers(-45, 45, 2)],
            'inner' => ['sometimes', new Numbers(-45, 45, 2)],
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'eye-tilt';
    }

    public function options(): array
    {
        return ['outer' => $this->outer, 'inner' => $this->inner, 'seed' => $this->seed, ...$this->scope()];
    }

    /**
     * @param float|array{float, float} $angle
     */
    private function degrees(float|array $angle, EyeContext $eye, string $part): float
    {
        return is_array($angle) ? $eye->random($angle[0], $angle[1], "{$this->name()}:{$this->seed}:{$part}") : $angle;
    }
}
