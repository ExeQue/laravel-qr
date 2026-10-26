<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries\Concerns;

use BackedEnum;
use Closure;
use ExeQue\Qr\Exceptions\RegistryException;
use ExeQue\Qr\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * Named parts for referring to them by a stable string, in code, config and stored styles.
 *
 * Names registered in code win over those from the config, which win over the
 * built-in ones. The config is read the first time a name is looked up.
 *
 * A name can stand for a class (built through the container, with options), a
 * class with fixed options (`[MyShape::class, 'points' => 6]`), a closure (called
 * through the container with the options), an instance, or an object that provides
 * one, such as an enum case. Registering an enum class registers every case under its value.
 *
 * @internal
 *
 * @template T of object
 */
trait IsRegistry
{
    /**
     * @var array<string, mixed>
     */
    private array $registered = [];

    /**
     * @var array<string, mixed>|null Null until first read.
     */
    private ?array $configured = null;

    /**
     * @var array<string, true> Names forgotten, built-in or configured ones included.
     */
    private array $forgotten = [];

    public function __construct(
        private readonly Factory $factory,
        private readonly Container $container,
        private readonly Repository $config,
    ) {
    }

    /**
     * What the registry holds, for messages: "module", "filter".
     */
    abstract public function kind(): string;

    /**
     * What every entry must give.
     *
     * @return class-string<T>
     */
    abstract protected function contract(): string;

    /**
     * Turns an object that provides one into it; null when it does not.
     *
     * @return T|null
     */
    abstract protected function provide(object $value): ?object;

    /**
     * The built-in entries.
     *
     * @return array<string, mixed>
     */
    abstract protected function builtIn(): array;

    /**
     * Where the config entries are: qr.{key}.
     */
    abstract protected function configKey(): string;

    /**
     * @param string|class-string<BackedEnum> $name A name, or an enum class to register every case of.
     */
    public function register(string $name, mixed $value = null): void
    {
        foreach ($this->expand($name, $value) as $entry => $entryValue) {
            $this->registered[$entry] = $entryValue;
            unset($this->forgotten[$entry]);
        }
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->entries());
    }

    /**
     * @param array<array-key, mixed> $options Named constructor arguments, over any the name fixes.
     *
     * @return T
     */
    public function get(string $name, array $options = []): object
    {
        $entries = $this->entries();

        if (! array_key_exists($name, $entries)) {
            throw RegistryException::unknown($this->kind(), $name, array_keys($entries));
        }

        return $this->resolve($name, $entries[$name], $options);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->entries());
    }

    /**
     * Every entry, built.
     *
     * @return array<string, T>
     */
    public function all(): array
    {
        $all = [];

        foreach ($this->names() as $name) {
            $all[$name] = $this->get($name);
        }

        return $all;
    }

    /**
     * The name a class is registered under on its own, without fixed options; null when none.
     */
    public function nameOf(string $class): ?string
    {
        $name = array_search($class, $this->entries(), true);

        return is_string($name) ? $name : null;
    }

    /**
     * The class a name stands for, without building it; null when it stands for something else.
     *
     * @return class-string|null
     */
    public function classOf(string $name): ?string
    {
        $value = $this->entries()[$name] ?? null;
        $class = is_array($value) ? ($value[0] ?? null) : $value;

        return is_string($class) && class_exists($class) ? $class : null;
    }

    /**
     * Whether the name is one of the package's own entries, not replaced by the application.
     */
    public function isBuiltIn(string $name): bool
    {
        // Loads the configured entries, which may replace a built-in one.
        $this->entries();

        return array_key_exists($name, $this->builtIn())
            && ! array_key_exists($name, $this->registered)
            && ! array_key_exists($name, $this->configured ?? [])
            && ! isset($this->forgotten[$name]);
    }

    public function forget(string $name): void
    {
        unset($this->registered[$name]);
        $this->forgotten[$name] = true;
    }

    /**
     * @return array<string, mixed>
     */
    private function entries(): array
    {
        if ($this->configured === null) {
            $this->configured = [];

            foreach ((array) $this->config->get('qr.'.$this->configKey(), []) as $name => $value) {
                $this->configured = [...$this->configured, ...(is_int($name) ? $this->expand((string) $value, null) : $this->expand($name, $value))];
            }
        }

        return array_diff_key([...$this->builtIn(), ...$this->configured, ...$this->registered], $this->forgotten);
    }

    /**
     * An enum class without a value stands for its cases.
     *
     * @return array<string, mixed>
     */
    private function expand(string $name, mixed $value): array
    {
        if ($value === null && enum_exists($name) && is_subclass_of($name, BackedEnum::class)) {
            $cases = [];

            foreach ($name::cases() as $case) {
                $cases[(string) $case->value] = $case;
            }

            return $cases;
        }

        return [$name => $value];
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return T
     */
    private function resolve(string $name, mixed $value, array $options): object
    {
        $resolved = match (true) {
            $value instanceof Closure => $this->container->call($value, $options),
            is_string($value) && class_exists($value) => $this->factory->make($value, $options),
            is_array($value) && is_string($value[0] ?? null) && class_exists($value[0]) => $this->factory->make($value[0], [...array_slice($value, 1, preserve_keys: true), ...$options]),
            default => $value,
        };

        if (! is_object($resolved)) {
            throw RegistryException::invalid($this->kind(), $name, $this->contract());
        }

        $contract = $this->contract();

        if ($resolved instanceof $contract) {
            return $resolved;
        }

        return $this->provide($resolved) ?? throw RegistryException::invalid($this->kind(), $name, $contract);
    }
}
