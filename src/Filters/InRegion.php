<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Support\Hydrate;

/**
 * Another filter, inside a region only: calm modules behind a logo, say.
 */
final readonly class InRegion implements ModuleFilter, NamedFilter
{
    private Region $region;

    private ModuleFilter&NamedFilter $filter;

    /**
     * @param Region|array<string, mixed> $region
     * @param ModuleFilter|array<string, mixed> $filter A named module filter, or its name and options.
     */
    public function __construct(Region|array $region, ModuleFilter|array $filter)
    {
        $this->region = Hydrate::region($region);

        $filter = is_array($filter) ? Hydrate::filter($filter) : $filter;
        $this->filter = $filter instanceof ModuleFilter && $filter instanceof NamedFilter ? $filter : throw InvalidFilterException::notAModuleFilter();
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        return $this->region->containsCell($cell) ? $this->filter->module($cell, $style) : $style;
    }

    public function name(): string
    {
        return 'region';
    }

    public function options(): array
    {
        return [
            'region' => $this->region->toArray(),
            'filter' => ['name' => $this->filter->name(), 'options' => $this->filter->options()],
        ];
    }
}
