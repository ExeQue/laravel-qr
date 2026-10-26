<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Enums\Module;

/**
 * Module shapes by name.
 */
final class ModuleRegistry
{
    /** @use IsRegistry<ModuleShape> */
    use IsRegistry;

    public function kind(): string
    {
        return 'module';
    }

    protected function contract(): string
    {
        return ModuleShape::class;
    }

    protected function provide(object $value): ?ModuleShape
    {
        return $value instanceof ProvidesQrModule ? $value->qrModule() : null;
    }

    protected function builtIn(): array
    {
        $entries = [];

        foreach (Module::cases() as $case) {
            $entries[$case->value] = $case->options() === [] ? $case->class() : [$case->class(), ...$case->options()];
        }

        return $entries;
    }

    protected function configKey(): string
    {
        return 'modules';
    }
}
