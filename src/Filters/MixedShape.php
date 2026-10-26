<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;
use ExeQue\Qr\Support\Seed;
use ExeQue\Qr\Validation\Allow;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;
use ExeQue\Qr\Validation\Rules\ShapeName;

/**
 * Draws each module with a shape of its own from a list, optionally weighted.
 */
final class MixedShape implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @var non-empty-list<ModuleShape>
     */
    private array $shapes;

    /**
     * @param list<ModuleShape|ProvidesQrModule|string|array<string, mixed>> $shapes Shapes, providers,
     *                                                                             registry names, class names or `['class' => ..., 'options' => [...]]`.
     * @param list<float>|null $weights One per shape; equal when null.
     */
    public function __construct(
        array $shapes,
        private ?array $weights = null,
        private int $seed = 0,
    ) {
        if ($shapes === [] || ($weights !== null && count($weights) !== count($shapes))) {
            throw InvalidFilterException::weightsDoNotMatch($this->name(), count($shapes), $weights === null ? null : count($weights));
        }

        $this->shapes = array_map(fn (ModuleShape|ProvidesQrModule|string|array $shape): ModuleShape => match (true) {
            $shape instanceof ProvidesQrModule => $shape->qrModule(),
            is_string($shape) && app(ModuleRegistry::class)->has($shape) => app(ModuleRegistry::class)->get($shape),
            default => Hydrate::shape($shape, ModuleShape::class),
        }, $shapes);
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        return $style->withShape(Seed::pick($cell->seed, $this->shapes, $this->weights, "{$this->name()}:{$this->seed}"));
    }

    public static function optionRules(): array
    {
        return [
            'shapes' => ['required', 'list', 'min:1', 'max:8'],
            'shapes.*' => [new ShapeName('module', Allow::all())],
            'weights' => ['sometimes', 'nullable', 'list', 'max:8'],
            'weights.*' => ['numeric', 'min:0'],
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'mixed-shape';
    }

    public function options(): array
    {
        return [...array_filter(['shapes' => Normalizer::value($this->shapes), 'weights' => $this->weights, 'seed' => $this->seed], fn (mixed $value): bool => $value !== null), ...$this->scope()];
    }
}
