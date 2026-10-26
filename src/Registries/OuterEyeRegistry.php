<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\Shapes\Enums\OuterEye;

/**
 * Outer eyes by name.
 */
final class OuterEyeRegistry
{
    /** @use IsRegistry<OuterEyeShape> */
    use IsRegistry;

    public function kind(): string
    {
        return 'outer eye';
    }

    protected function contract(): string
    {
        return OuterEyeShape::class;
    }

    protected function provide(object $value): ?OuterEyeShape
    {
        return $value instanceof ProvidesQrOuterEye ? $value->qrOuterEye() : null;
    }

    protected function builtIn(): array
    {
        $entries = [];

        foreach (OuterEye::cases() as $case) {
            $entries[$case->value] = $case->options() === [] ? $case->class() : [$case->class(), ...$case->options()];
        }

        return $entries;
    }

    protected function configKey(): string
    {
        return 'outer_eyes';
    }
}
