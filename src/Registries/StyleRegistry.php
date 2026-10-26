<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Builders\StyleBuilder;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Style;

/**
 * Reusable styles by name.
 *
 * Next to the forms every registry takes, a style can be given as its stored
 * array, which may name the styles it builds on: `['extends' => 'brand', ...]`.
 * A style is worked out the first time it is used, and kept.
 */
final class StyleRegistry
{
    /** @use IsRegistry<Style> */
    use IsRegistry {
        get as private build;
        register as private add;
        forget as private remove;
    }

    /**
     * @var array<string, Style>
     */
    private array $resolved = [];

    /**
     * @var list<string> The names being worked out, to catch a style that extends itself.
     */
    private array $resolving = [];

    public function kind(): string
    {
        return 'style';
    }

    public function register(string $name, mixed $value = null): void
    {
        $this->add($name, $value);
        $this->resolved = [];
    }

    public function forget(string $name): void
    {
        $this->remove($name);
        $this->resolved = [];
    }

    /**
     * @param array<array-key, mixed> $options Passed on to a style class or closure.
     */
    public function get(string $name, array $options = []): Style
    {
        if ($options === [] && isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (in_array($name, $this->resolving, true)) {
            throw InvalidStyleException::cycle([...$this->resolving, $name]);
        }

        $this->resolving[] = $name;

        try {
            $value = $this->entries()[$name] ?? null;
            $style = is_array($value) && ! is_string($value[0] ?? null)
                ? $this->hydrate($value)
                : $this->build($name, $options);
        } finally {
            array_pop($this->resolving);
        }

        return $options === [] ? $this->resolved[$name] = $style : $style;
    }

    /**
     * A stored style, on top of the styles it extends.
     *
     * @param array<string, mixed> $data
     */
    public function hydrate(array $data): Style
    {
        $extends = (array) ($data['extends'] ?? []);
        unset($data['extends']);

        $base = array_reduce($extends, fn (?Style $style, string $name): Style => $style?->merge($this->get($name)) ?? $this->get($name), null);
        $style = Style::fromArray($data);

        return $base === null ? $style : $base->merge($style);
    }

    protected function contract(): string
    {
        return Style::class;
    }

    protected function provide(object $value): ?Style
    {
        return match (true) {
            $value instanceof ProvidesQrStyle => $value->qrStyle(),
            $value instanceof StyleBuilder => $value->toStyle(),
            default => null,
        };
    }

    protected function builtIn(): array
    {
        return [];
    }

    protected function configKey(): string
    {
        return 'styles';
    }
}
