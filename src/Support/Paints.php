<?php

declare(strict_types=1);

namespace ExeQue\Qr\Support;

use ExeQue\Qr\Colors\Named;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Gradients\ColorStop;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\MeshGradient;
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;

/**
 * @internal
 */
final class Paints
{
    /**
     * The colors a paint can show; none for `currentColor`, whose color the page decides.
     *
     * @return list<Color>
     */
    public static function colors(Paint $paint): array
    {
        $colors = match (true) {
            $paint instanceof Color => [$paint],
            $paint instanceof Layered => array_merge(...array_map(fn (Layer $layer): array => self::colors($layer->paint), $paint->layers)),
            $paint instanceof MeshGradient => array_merge(...$paint->rows),
            $paint instanceof Gradient => array_map(fn (ColorStop $stop): Color => $stop->color, $paint->stops()),
            default => [],
        };

        return array_values(array_filter($colors, fn (Color $color): bool => ! ($color instanceof Named && $color->isCurrentColor())));
    }
}
