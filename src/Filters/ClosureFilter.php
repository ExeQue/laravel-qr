<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use Closure;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

/**
 * A module filter written as a closure. A closure has no identity of its own,
 * so without a name a code using it cannot be cached.
 */
final readonly class ClosureFilter implements ModuleFilter, NamedFilter
{
    /**
     * @param Closure(ModuleContext, ModuleStyle): ModuleStyle $closure
     */
    public function __construct(
        private Closure $closure,
        private ?string $name = null,
    ) {
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        return ($this->closure)($cell, $style);
    }

    public function name(): string
    {
        return $this->name ?? 'closure';
    }

    public function options(): array
    {
        return [];
    }

    public function isCacheable(): bool
    {
        return $this->name !== null;
    }
}
