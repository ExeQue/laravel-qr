<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Enums\Eye;

/**
 * Whole eyes by name: an outer and an inner together.
 */
final class EyeRegistry
{
    /** @use IsRegistry<ProvidesQrEye> */
    use IsRegistry;

    public function kind(): string
    {
        return 'eye';
    }

    protected function contract(): string
    {
        return ProvidesQrEye::class;
    }

    protected function provide(object $value): null
    {
        return null;
    }

    protected function builtIn(): array
    {
        return array_column(array_map(fn (Eye $eye): array => [$eye->value, $eye], Eye::cases()), 1, 0);
    }

    protected function configKey(): string
    {
        return 'eyes';
    }
}
