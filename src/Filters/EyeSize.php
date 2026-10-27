<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Concerns\TakesRanges;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Numbers;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Scales each eye's inner part. The outer part keeps its size, which readers measure.
 */
final class EyeSize implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;
    use TakesRanges;

    /**
     * @param float|array{float, float} $inner The inner part's size as a share, or a range to pick from per corner.
     */
    public function __construct(
        private float|array $inner = 0.85,
        private int $seed = 0,
    ) {
        // Numbers from a form are strings; a range of them is not converted on the way in.
        $this->inner = self::range('inner', $inner);
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        $size = is_array($this->inner) ? $eye->random($this->inner[0], $this->inner[1], "{$this->name()}:{$this->seed}") : $this->inner;

        return $style->withInner($style->inner->scaleBy($size));
    }

    public static function optionRules(): array
    {
        return [
            'inner' => ['sometimes', new Numbers(0.5, 1.5, 2)],
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'eye-size';
    }

    public function options(): array
    {
        return ['inner' => $this->inner, 'seed' => $this->seed, ...$this->scope()];
    }
}
