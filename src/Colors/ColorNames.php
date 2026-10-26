<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidColorException;

/**
 * An application's own color names, such as `brand`, usable wherever a CSS
 * name is. They cannot replace a CSS name. One instance lives in the container.
 */
final class ColorNames
{
    /**
     * @var array<string, ColorContract>
     */
    private array $colors = [];

    /**
     * @param array<string, ColorContract|string|array<string, mixed>> $colors
     */
    public function __construct(array $colors = [])
    {
        foreach ($colors as $name => $color) {
            $this->register($name, $color);
        }
    }

    /**
     * @param ColorContract|string|array<string, mixed> $color Taken as it is now; a name given here is resolved straight away.
     */
    public function register(string $name, ColorContract|string|array $color): void
    {
        $name = strtolower(trim($name));

        if (preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1) {
            throw InvalidColorException::invalidName($name);
        }

        if (Named::isBuiltIn($name)) {
            throw InvalidColorException::builtInName($name);
        }

        $color = Color::make($color);

        // An alias of another own name takes that name's color as it is now.
        if ($color instanceof Named && ! Named::isBuiltIn($color->name)) {
            $color = $this->colors[$color->name];
        }

        $this->colors[$name] = $color;
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function get(string $name): ?ColorContract
    {
        return $this->colors[strtolower(trim($name))] ?? null;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->all());
    }

    /**
     * @return array<string, ColorContract>
     */
    public function all(): array
    {
        return $this->colors;
    }

    public function forget(string $name): void
    {
        unset($this->colors[strtolower(trim($name))]);
    }
}
