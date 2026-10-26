<?php

declare(strict_types=1);

namespace Tests\Fixtures\Validation;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

/**
 * An application's filter with no option rules, which checks its own range.
 */
final readonly class RangedFilter implements ModuleFilter, NamedFilter
{
    public function __construct(
        public float $amount = 0.5,
    ) {
        if ($amount > 1) {
            throw InvalidOptionException::outOfRange(self::class, 'amount', 0, 1, $amount);
        }
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        return $style;
    }

    public function name(): string
    {
        return 'ranged';
    }

    public function options(): array
    {
        return ['amount' => $this->amount];
    }
}
