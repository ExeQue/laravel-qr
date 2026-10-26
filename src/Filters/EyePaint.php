<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\PaintRule;

/**
 * Paints the eyes: both parts, or the outer and inner apart. Limit it to some
 * corners with `corners()`.
 */
final class EyePaint implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    private ?Paint $outer;

    private ?Paint $inner;

    /**
     * @param Paint|string|array<string, mixed>|null $paint Both parts, unless one is given on its own.
     * @param Paint|string|array<string, mixed>|null $outer
     * @param Paint|string|array<string, mixed>|null $inner
     */
    public function __construct(
        Paint|string|array|null $paint = null,
        Paint|string|array|null $outer = null,
        Paint|string|array|null $inner = null,
    ) {
        $this->outer = self::paint($outer ?? $paint);
        $this->inner = self::paint($inner ?? $paint);
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        return $style
            ->withOuter($this->outer === null ? $style->outer : $style->outer->withPaint($this->outer))
            ->withInner($this->inner === null ? $style->inner : $style->inner->withPaint($this->inner));
    }

    public static function optionRules(): array
    {
        return [
            'paint' => ['sometimes', PaintRule::any()->nullable()],
            'outer' => ['sometimes', PaintRule::any()->nullable()],
            'inner' => ['sometimes', PaintRule::any()->nullable()],
        ];
    }

    public function name(): string
    {
        return 'eye-paint';
    }

    public function options(): array
    {
        return [...array_filter(['outer' => Normalizer::value($this->outer), 'inner' => Normalizer::value($this->inner)], fn (mixed $value): bool => $value !== null), ...$this->scope()];
    }

    /**
     * @param Paint|string|array<string, mixed>|null $paint
     */
    private static function paint(Paint|string|array|null $paint): ?Paint
    {
        return $paint === null ? null : Hydrate::paint($paint);
    }
}
