<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Support\Normalizer;
use ExeQue\Qr\Support\Seed;

/**
 * A color picked for each corner from a palette, more often the heavier its weight.
 */
final class EyePalette implements EyeFilter, NamedFilter
{
    use Scoped;

    /**
     * @var non-empty-list<ColorContract>
     */
    private array $colors;

    /**
     * @param list<ColorContract|string|array<string, mixed>> $colors
     * @param list<float>|null $weights One per color; equal when null.
     * @param bool $outer Paint the outer part.
     * @param bool $inner Paint the inner part.
     */
    public function __construct(
        array $colors,
        private ?array $weights = null,
        private int $seed = 0,
        private bool $outer = true,
        private bool $inner = true,
    ) {
        if ($colors === [] || ($weights !== null && count($weights) !== count($colors))) {
            throw InvalidFilterException::weightsDoNotMatch($this->name(), count($colors), $weights === null ? null : count($weights));
        }

        $this->colors = array_map(Color::make(...), $colors);
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        $color = Seed::pick($eye->seed, $this->colors, $this->weights, "{$this->name()}:{$this->seed}");

        return $style
            ->withOuter($this->outer ? $style->outer->withPaint($color) : $style->outer)
            ->withInner($this->inner ? $style->inner->withPaint($color) : $style->inner);
    }

    public function name(): string
    {
        return 'eye-palette';
    }

    public function options(): array
    {
        return [...array_filter(['colors' => Normalizer::value($this->colors), 'weights' => $this->weights, 'seed' => $this->seed, 'outer' => $this->outer, 'inner' => $this->inner], fn (mixed $value): bool => $value !== null), ...$this->scope()];
    }
}
