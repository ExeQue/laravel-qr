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
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

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
     * A shape from its class name or registered name, or `['class' => ..., 'options' => [...]]` with either;
     * `shape` may stand for `class`, as style input names it.
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
            is_array($shape) => [$shape['class'] ?? $shape['shape'] ?? null, (array) ($shape['options'] ?? [])],
            default => [null, []],
        };

        $made = match (true) {
            is_object($shape) => $shape,
            // Only a class that is a shape is built from its name: stored styles can come from anywhere.
            is_string($class) && is_a($class, $contract, true) => app(Factory::class)->make($class, $options),
            is_string($class) => self::named($class, $options, $contract),
            default => null,
        };

        return $made instanceof $contract ? $made : throw InvalidStyleException::unreadable('shape', $shape);
    }

    /**
     * A shape by the name its registry knows it by; null when none does.
     *
     * @param array<array-key, mixed> $options
     */
    private static function named(string $name, array $options, string $contract): ?object
    {
        $registry = match ($contract) {
            ModuleShape::class => app(ModuleRegistry::class),
            OuterEyeShape::class => app(OuterEyeRegistry::class),
            InnerEyeShape::class => app(InnerEyeRegistry::class),
            default => null,
        };

        return $registry !== null && $registry->has($name) ? $registry->get($name, $options) : null;
    }

    /**
     * @param Region|array<string, mixed> $region
     */
    public static function region(Region|array $region): Region
    {
        return $region instanceof Region ? $region : Region::fromArray($region);
    }
}
