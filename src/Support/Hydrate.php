<?php

declare(strict_types=1);

namespace ExeQue\Qr\Support;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Factory;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\GradientRegistry;

/**
 * Makes parts again from the plain values `Normalizer` gives: what stored and
 * cached styles hold. Parts that are already made pass through.
 *
 * @internal
 */
final class Hydrate
{
    /**
     * A color from any color form, or a gradient or layered paint from its array.
     */
    public static function paint(mixed $paint): Paint
    {
        return match (true) {
            $paint instanceof Paint => $paint,
            is_array($paint) && isset($paint['kind']) => self::gradient($paint),
            is_array($paint) && isset($paint['layers']) => Layered::fromArray($paint),
            is_string($paint), is_array($paint) => Color::make($paint),
            default => throw InvalidStyleException::unreadable('paint', $paint),
        };
    }

    /**
     * @param Gradient|array<string, mixed> $gradient
     */
    public static function gradient(Gradient|array $gradient): Gradient
    {
        return $gradient instanceof Gradient ? $gradient : app(GradientRegistry::class)->hydrate($gradient);
    }

    /**
     * A filter from `['name' => ..., 'options' => [...]]`.
     *
     * @param NamedFilter|array<string, mixed> $filter
     *
     * @return (ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter)
     */
    public static function filter(NamedFilter|array $filter): NamedFilter
    {
        $filter = $filter instanceof NamedFilter ? $filter : app(FilterRegistry::class)->hydrate($filter);

        if (! $filter instanceof ModuleFilter && ! $filter instanceof EyeFilter) {
            throw InvalidStyleException::unreadable('filter', $filter->name());
        }

        return $filter;
    }

    /**
     * A shape from its class name, or `['class' => ..., 'options' => [...]]`.
     *
     * @template T of object
     *
     * @param class-string<T> $contract
     *
     * @return T
     */
    public static function shape(mixed $shape, string $contract): object
    {
        [$class, $options] = match (true) {
            is_string($shape) => [$shape, []],
            is_array($shape) => [$shape['class'] ?? null, (array) ($shape['options'] ?? [])],
            default => [null, []],
        };

        $made = match (true) {
            is_object($shape) => $shape,
            is_string($class) && class_exists($class) => app(Factory::class)->make($class, $options),
            default => null,
        };

        return $made instanceof $contract ? $made : throw InvalidStyleException::unreadable('shape', $shape);
    }

    /**
     * @param Region|array<string, mixed> $region
     */
    public static function region(Region|array $region): Region
    {
        return $region instanceof Region ? $region : Region::fromArray($region);
    }
}
