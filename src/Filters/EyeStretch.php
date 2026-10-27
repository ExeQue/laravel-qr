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
 * Stretches the eyes wider than tall, or taller than wide. The scan guards hold how far.
 */
final class EyeStretch implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;
    use TakesRanges;

    /**
     * @param float|array{float, float} $width The width, as a share of the shape's own, or a range to pick from per corner.
     * @param float|array{float, float} $height The height, as a share of the shape's own, or a range to pick from per corner.
     */
    public function __construct(
        private float|array $width = 1.12,
        private float|array $height = 0.9,
        private bool $outer = true,
        private bool $inner = true,
        private int $seed = 0,
    ) {
        // Numbers from a form are strings; a range of them is not converted on the way in.
        $this->width = self::range('width', $width);
        $this->height = self::range('height', $height);
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        $share = fn (float|array $share, string $side): float => is_array($share)
            ? $eye->random($share[0], $share[1], "{$this->name()}:{$this->seed}:{$side}")
            : $share;
        [$width, $height] = [$share($this->width, 'width'), $share($this->height, 'height')];

        return $style
            ->withOuter($this->outer ? $style->outer->scaleBy($width, $height) : $style->outer)
            ->withInner($this->inner ? $style->inner->scaleBy($width, $height) : $style->inner);
    }

    public static function optionRules(): array
    {
        return [
            'width' => ['sometimes', new Numbers(0.5, 1.5, 2)],
            'height' => ['sometimes', new Numbers(0.5, 1.5, 2)],
            'outer' => Options::boolean(),
            'inner' => Options::boolean(),
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'eye-stretch';
    }

    public function options(): array
    {
        return ['width' => $this->width, 'height' => $this->height, 'outer' => $this->outer, 'inner' => $this->inner, 'seed' => $this->seed, ...$this->scope()];
    }
}
