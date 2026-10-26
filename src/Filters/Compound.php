<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Support\Hydrate;

/**
 * Several filters as one, run in order: module filters on modules, eye filters on eyes.
 * Scoped, it limits all of them at once.
 */
final class Compound implements ModuleFilter, EyeFilter, NamedFilter
{
    use Scoped;

    /**
     * @var list<(ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter)>
     */
    private array $filters;

    /**
     * @param (ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter)|array<array-key, mixed> ...$filters Filters, their names and
     *                                                                                    options, or - as a stored compound holds them - one list of those.
     */
    public function __construct((ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter)|array ...$filters)
    {
        // A stored compound holds its filters as one list, under its option's name.
        if (count($filters) === 1 && is_array($filters[array_key_first($filters)]) && array_is_list($filters[array_key_first($filters)])) {
            $filters = $filters[array_key_first($filters)];
        }

        $this->filters = array_values(array_map(fn (NamedFilter|array $filter): NamedFilter => Hydrate::filter($filter), $filters));
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        foreach ($this->filters as $filter) {
            if ($filter instanceof ModuleFilter) {
                $style = $filter->module($cell, $style);
            }
        }

        return $style;
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye)) {
            return $style;
        }

        foreach ($this->filters as $filter) {
            if ($filter instanceof EyeFilter) {
                $style = $filter->eye($eye, $style);
            }
        }

        return $style;
    }

    public function name(): string
    {
        return 'compound';
    }

    public function options(): array
    {
        return [
            'filters' => array_map(fn (NamedFilter $filter): array => ['name' => $filter->name(), 'options' => $filter->options()], $this->filters),
            ...$this->scope(),
        ];
    }
}
