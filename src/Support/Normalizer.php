<?php

declare(strict_types=1);

namespace ExeQue\Qr\Support;

use BackedEnum;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Registries\GradientRegistry;
use ExeQue\Qr\Shapes\Contracts\Configurable;

/**
 * Plain values for anything a style holds: what caching and storing go by.
 *
 * @internal
 */
final class Normalizer
{
    public static function value(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Color => $value->toCss(),
            $value instanceof Gradient => self::gradient($value),
            $value instanceof Layered => $value->toArray(),
            $value instanceof Configurable => ['class' => $value::class, 'options' => self::value($value->options())],
            $value instanceof BackedEnum => $value->value,
            is_array($value) => array_map(self::value(...), $value),
            is_object($value) => $value::class,
            default => $value,
        };
    }

    /**
     * A gradient as its kind and settings: the kind by its registry name when it has one.
     *
     * @return array<string, mixed>
     */
    public static function gradient(Gradient $gradient): array
    {
        return app(GradientRegistry::class)->dehydrate($gradient);
    }
}
