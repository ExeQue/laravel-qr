<?php

declare(strict_types=1);

namespace Tests\Fixtures\Validation;

use ExeQue\Qr\Shapes\Enums\Direction;

/**
 * A class without option rules of its own, of every type the resolver reads.
 */
final readonly class ReflectedOptions
{
    /**
     * @param array<array-key, mixed> $list
     */
    public function __construct(
        public float $size,
        public ?int $count = null,
        public bool $flag = false,
        public Direction $point = Direction::TopLeft,
        public array $list = [],
        public float|string $either = 1.0,
    ) {
    }
}
