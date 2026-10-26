<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Enums\InnerEye;

/**
 * Inner eyes by name.
 */
final class InnerEyeRegistry
{
    /** @use IsRegistry<InnerEyeShape> */
    use IsRegistry;

    public function kind(): string
    {
        return 'inner eye';
    }

    protected function contract(): string
    {
        return InnerEyeShape::class;
    }

    protected function provide(object $value): ?InnerEyeShape
    {
        return $value instanceof ProvidesQrInnerEye ? $value->qrInnerEye() : null;
    }

    protected function builtIn(): array
    {
        $entries = [];

        foreach (InnerEye::cases() as $case) {
            $entries[$case->value] = $case->options() === [] ? $case->class() : [$case->class(), ...$case->options()];
        }

        return $entries;
    }

    protected function configKey(): string
    {
        return 'inner_eyes';
    }
}
