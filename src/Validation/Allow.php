<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation;

use BackedEnum;

/**
 * Which of a list may be chosen: shapes, eye presets, gradient kinds, filters or
 * logo shapes, by registered name or enum case.
 */
final readonly class Allow
{
    /**
     * @param list<string>|null $only Null for everything not excepted.
     * @param list<string> $except
     */
    private function __construct(
        private ?array $only,
        private array $except,
    ) {
    }

    /**
     * Everything the registry knows, names registered later included.
     */
    public static function all(): self
    {
        return new self(null, []);
    }

    /**
     * Only these.
     */
    public static function only(BackedEnum|string ...$names): self
    {
        return new self(self::names($names), []);
    }

    /**
     * Everything but these.
     */
    public static function except(BackedEnum|string ...$names): self
    {
        return new self(null, self::names($names));
    }

    public function permits(string $name): bool
    {
        return ($this->only === null || in_array($name, $this->only, true)) && ! in_array($name, $this->except, true);
    }

    /**
     * The names among `$known` that may be chosen.
     *
     * @param list<string> $known
     *
     * @return list<string>
     */
    public function of(array $known): array
    {
        return array_values(array_filter($known, $this->permits(...)));
    }

    /**
     * @param array<array-key, BackedEnum|string> $names
     *
     * @return list<string>
     */
    private static function names(array $names): array
    {
        return array_values(array_map(fn (BackedEnum|string $name): string => $name instanceof BackedEnum ? (string) $name->value : $name, $names));
    }
}
