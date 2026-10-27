<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster\Concerns;

use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Writers\Boxes;

/**
 * The fills a raster writer draws, in order: the same for every way of drawing pixels.
 *
 * @internal
 */
trait GroupsItems
{
    /**
     * Items with the same paint and opacity share one path. A gradient in each
     * shape's own box needs each shape on its own; one sampled per module, a color per shape.
     *
     * @return list<array{paint: Paint, opacity: float, items: non-empty-list<SceneItem>}>
     */
    private function groups(Scene $scene): array
    {
        $groups = [];

        foreach ($scene->items as $index => $item) {
            $layers = $item->paint instanceof Layered ? $item->paint->layers : [new Layer($item->paint)];

            foreach ($layers as $layer => $part) {
                $paint = $part->paint;

                if ($paint instanceof Gradient && $this->isSampledPerModule($paint)) {
                    $paint = $paint->colorAt(...Boxes::middle($paint, $scene, $item));
                }

                $opacity = $part->opacity * $item->opacity;

                // The layers of a layered paint stay apart from plain fills, so each lands above the one under it.
                $key = ($item->paint instanceof Layered ? "layer {$layer} " : '').match (true) {
                    $paint instanceof ColorContract => $paint->toCss(),
                    $paint instanceof Gradient && Boxes::of($paint) === GradientBox::Shape => spl_object_id($paint)."#{$index}",
                    default => (string) spl_object_id($paint),
                }.'@'.$opacity;

                $groups[$key] ??= ['paint' => $paint, 'opacity' => $opacity, 'items' => []];
                $groups[$key]['items'][] = $item;
            }
        }

        return array_values($groups);
    }

    private function isSampledPerModule(Gradient $gradient): bool
    {
        return isset($gradient->sampling) && $gradient->sampling === Sampling::PerModule;
    }
}
