<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;
use ExeQue\Qr\Support\Seed;
use ExeQue\Qr\Validation\Allow;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;
use ExeQue\Qr\Validation\Rules\ShapeEntry;

/**
 * A shape picked for each corner's outer part, inner part or both, from a few: three eyes
 * alike in spirit, none the same.
 */
final class EyeMix implements EyeFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @var list<OuterEyeShape>
     */
    private array $outer;

    /**
     * @var list<InnerEyeShape>
     */
    private array $inner;

    /**
     * @param list<OuterEyeShape|ProvidesQrOuterEye|string|array<string, mixed>> $outer Shapes for the outer part; none leaves it.
     * @param list<InnerEyeShape|ProvidesQrInnerEye|string|array<string, mixed>> $inner Shapes for the inner part; none leaves it.
     */
    public function __construct(
        array $outer = ['circle', 'rounded', 'leaf'],
        array $inner = ['circle', 'soft', 'leaf'],
        private int $seed = 0,
    ) {
        $this->outer = array_map(fn (OuterEyeShape|ProvidesQrOuterEye|string|array $shape): OuterEyeShape => match (true) {
            $shape instanceof ProvidesQrOuterEye => $shape->qrOuterEye(),
            default => Hydrate::shape($shape, OuterEyeShape::class),
        }, $outer);
        $this->inner = array_map(fn (InnerEyeShape|ProvidesQrInnerEye|string|array $shape): InnerEyeShape => match (true) {
            $shape instanceof ProvidesQrInnerEye => $shape->qrInnerEye(),
            default => Hydrate::shape($shape, InnerEyeShape::class),
        }, $inner);
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";

        return $style
            ->withOuter($this->outer === [] ? $style->outer : $style->outer->withShape(Seed::pick($eye->seed, $this->outer, purpose: "{$purpose}:outer")))
            ->withInner($this->inner === [] ? $style->inner : $style->inner->withShape(Seed::pick($eye->seed, $this->inner, purpose: "{$purpose}:inner")));
    }

    public static function optionRules(): array
    {
        return [
            'outer' => ['sometimes', 'list', 'max:8'],
            'outer.*' => [new ShapeEntry('outer', Allow::all())],
            'inner' => ['sometimes', 'list', 'max:8'],
            'inner.*' => [new ShapeEntry('inner', Allow::all())],
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'eye-mix';
    }

    public function options(): array
    {
        return ['outer' => Normalizer::value($this->outer), 'inner' => Normalizer::value($this->inner), 'seed' => $this->seed, ...$this->scope()];
    }
}
