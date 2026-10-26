<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;

/**
 * Where a gradient's 0-1 box lies on the canvas, for every writer.
 *
 * @internal
 */
final class Boxes
{
    /**
     * The box of the package's own gradients; the code for any other kind.
     */
    public static function of(Gradient $gradient): GradientBox
    {
        return isset($gradient->box) && $gradient->box instanceof GradientBox ? $gradient->box : GradientBox::Code;
    }

    /**
     * `[x, y, width, height]` on the canvas, in modules: for a shape box, the bounds of the item
     * or path painted (a logo's plate), else the canvas.
     *
     * @return array{float, float, float, float}
     */
    public static function on(Gradient $gradient, Scene $scene, SceneItem|Path|null $shape = null): array
    {
        return match (self::of($gradient)) {
            GradientBox::Canvas => [0.0, 0.0, (float) $scene->size, (float) $scene->size],
            GradientBox::Shape => $shape === null ? self::canvas($scene) : self::bounds($shape instanceof SceneItem ? $shape->path() : $shape),
            GradientBox::Code => self::code($scene),
        };
    }

    /**
     * Where an item's middle falls in its gradient's box, 0-1.
     *
     * @return array{float, float}
     */
    public static function middle(Gradient $gradient, Scene $scene, SceneItem $item): array
    {
        [$x, $y, $width, $height] = self::of($gradient) === GradientBox::Shape
            ? [$item->center[0] - 0.5, $item->center[1] - 0.5, 1.0, 1.0]
            : self::on($gradient, $scene, $item);

        return [($item->center[0] - $x) / $width, ($item->center[1] - $y) / $height];
    }

    /**
     * @return array{float, float, float, float}
     */
    public static function code(Scene $scene): array
    {
        $extent = (float) ($scene->size - 2 * $scene->margin);

        return [(float) $scene->margin, (float) $scene->margin, $extent, $extent];
    }

    /**
     * @return array{float, float, float, float}
     */
    private static function canvas(Scene $scene): array
    {
        return [0.0, 0.0, (float) $scene->size, (float) $scene->size];
    }

    /**
     * @return array{float, float, float, float}
     */
    private static function bounds(Path $path): array
    {
        [$minX, $minY, $maxX, $maxY] = $path->bounds() ?? [0.0, 0.0, 1.0, 1.0];

        return [$minX, $minY, max($maxX - $minX, 1e-6), max($maxY - $minY, 1e-6)];
    }
}
