<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use BadMethodCallException;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Qr;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Style;
use UnitEnum;

/**
 * A style described with the style builder's methods, built only when it is used: what a named
 * style in config/qr.php can be, where the registries are not there yet and everything must
 * survive php artisan config:cache.
 *
 * The calls keep plain values only - text, numbers, arrays, enums and colors - so a gradient
 * goes as its array, ['kind' => 'linear', ...], and shapes and filters by their names.
 *
 * @mixin StyleBuilder
 */
final readonly class PendingStyle implements ProvidesQrStyle
{
    /**
     * The builder methods a style can be described with.
     */
    private const array METHODS = [
        'background', 'eye', 'eyes', 'filter', 'filters', 'inner', 'logo', 'margin',
        'mirrored', 'modules', 'outer', 'paint', 'style', 'withoutFilter',
    ];

    /**
     * @param list<array{string, array<array-key, mixed>}> $calls Each method and its arguments, in order.
     */
    private function __construct(
        private array $calls = [],
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __call(string $method, array $arguments): self
    {
        if (! in_array($method, self::METHODS, true)) {
            throw new BadMethodCallException("A style in config cannot call {$method}(): it takes ".implode('(), ', self::METHODS).'().');
        }

        array_walk_recursive($arguments, fn (mixed $value): null => self::isPlain($value) ? null : throw InvalidStyleException::notPlain($method, $value));

        return new self([...$this->calls, [$method, $arguments]]);
    }

    /**
     * The style, the calls made on the package's style builder.
     */
    public function qrStyle(): Style
    {
        $builder = app(Qr::class)->style();

        foreach ($this->calls as [$method, $arguments]) {
            $builder = $builder->{$method}(...$arguments);
        }

        return $builder->toStyle();
    }

    /**
     * Made again from the config cache.
     *
     * @param array{calls: list<array{string, array<array-key, mixed>}>} $state
     */
    public static function __set_state(array $state): self
    {
        return new self($state['calls']);
    }

    /**
     * Whether a value survives the config cache: plain, an enum, or a color.
     */
    private static function isPlain(mixed $value): bool
    {
        return $value === null || is_scalar($value) || $value instanceof UnitEnum || (is_object($value) && method_exists($value, '__set_state'));
    }
}
