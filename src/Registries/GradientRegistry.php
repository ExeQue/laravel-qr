<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Exceptions\RegistryException;
use ExeQue\Qr\Gradients\ConicGradient;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\Contracts\SerializableGradient;
use ExeQue\Qr\Gradients\DiamondGradient;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\MeshGradient;
use ExeQue\Qr\Gradients\RadialGradient;
use ExeQue\Qr\Registries\Concerns\IsRegistry;

/**
 * Gradient kinds by name.
 */
final class GradientRegistry
{
    /** @use IsRegistry<Gradient> */
    use IsRegistry;

    public function kind(): string
    {
        return 'gradient';
    }

    protected function contract(): string
    {
        return Gradient::class;
    }

    protected function provide(object $value): null
    {
        return null;
    }

    protected function builtIn(): array
    {
        return [
            'linear' => LinearGradient::class,
            'radial' => RadialGradient::class,
            'conic' => ConicGradient::class,
            'diamond' => DiamondGradient::class,
            'mesh' => MeshGradient::class,
        ];
    }

    /**
     * The gradient as plain values, its kind by name when registered, by class otherwise.
     *
     * @return array<string, mixed>
     */
    public function dehydrate(Gradient $gradient): array
    {
        if (! $gradient instanceof SerializableGradient) {
            throw RegistryException::notSerializable($gradient::class);
        }

        return ['kind' => $this->nameOf($gradient::class) ?? $gradient::class, ...$gradient->toArray()];
    }

    /**
     * @param array<string, mixed> $data As `dehydrate()` gives it.
     */
    public function hydrate(array $data): Gradient
    {
        $kind = (string) ($data['kind'] ?? '');
        $class = $this->classOf($kind) ?? $kind;

        if (! is_subclass_of($class, SerializableGradient::class)) {
            throw RegistryException::notSerializable($kind);
        }

        unset($data['kind']);

        return $class::fromArray($data);
    }

    protected function configKey(): string
    {
        return 'gradients';
    }
}
