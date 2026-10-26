<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class InvalidFilterException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function weightsDoNotMatch(string $filter, int $options, ?int $weights): self
    {
        return $options === 0
            ? new self("The {$filter} filter needs at least one option to pick from.")
            : new self("The {$filter} filter has {$options} options but {$weights} weights.");
    }

    public static function unknownRegion(string $kind): self
    {
        return new self("\"{$kind}\" is not a kind of region: rect, rounded, circle, cells or logo.");
    }

    public static function notAModuleFilter(): self
    {
        return new self('A filter limited to roles or a region is a named module filter.');
    }

    public static function needsOptions(string $filter): self
    {
        return new self("The {$filter} filter has no defaults; give it its options.");
    }
}
