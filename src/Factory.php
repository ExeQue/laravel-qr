<?php

declare(strict_types=1);

namespace ExeQue\Qr;

use BackedEnum;
use ExeQue\Qr\Exceptions\InvalidOptionException;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Builds the package's parts - shapes, filters, gradients, payloads, writers -
 * through the service container, so an application can bind its own in their place.
 */
final readonly class Factory
{
    public function __construct(
        private Container $container,
    ) {
    }

    /**
     * `$class` with `$options` as named constructor arguments; anything else it
     * needs comes from the container. An option a backed enum is wanted for may
     * be given as the enum's value, as stored styles hold it.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     * @param array<array-key, mixed> $options Named constructor arguments; a positional one is refused.
     *
     * @return T
     */
    public function make(string $class, array $options = []): object
    {
        $this->checkOptions($class, $options);

        return $this->container->make($class, $this->enums($class, $options));
    }

    /**
     * @param class-string $class
     * @param array<array-key, mixed> $options
     *
     * @return array<array-key, mixed>
     */
    private function enums(string $class, array $options): array
    {
        foreach (new ReflectionClass($class)->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $value = $options[$parameter->getName()] ?? null;

            if ((is_string($value) || is_int($value)) && $type instanceof ReflectionNamedType && is_subclass_of($type->getName(), BackedEnum::class)) {
                $options[$parameter->getName()] = $type->getName()::from($value);
            }
        }

        return $options;
    }

    /**
     * @param class-string $class
     * @param array<array-key, mixed> $options
     */
    private function checkOptions(string $class, array $options): void
    {
        if ($options === []) {
            return;
        }

        $parameters = array_map(
            fn (ReflectionParameter $parameter): string => $parameter->getName(),
            new ReflectionClass($class)->getConstructor()?->getParameters() ?? [],
        );

        foreach (array_keys($options) as $option) {
            if (! in_array($option, $parameters, true)) {
                throw InvalidOptionException::unknown($class, (string) $option, $parameters);
            }
        }
    }
}
