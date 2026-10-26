<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Gradients\ColorStop;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Support\Number;

/**
 * A code drawn, before any writer: the canvas and every shape on it, in modules.
 */
final readonly class Scene
{
    /**
     * @param int $size The canvas, quiet zone included, in modules.
     * @param int $margin The quiet zone, in modules.
     * @param list<SceneItem> $items In drawing order.
     * @param list<GuardAdjustment> $adjustments What the scan safety guards changed or warn about.
     */
    public function __construct(
        public int $size,
        public int $margin,
        public ?Paint $background,
        public array $items,
        public array $adjustments = [],
        public ?LogoLayer $logo = null,
    ) {
    }

    /**
     * One line per drawn item: what, where, which shape, how placed, which paint.
     */
    public function toSnapshot(): string
    {
        $lines = ["canvas {$this->size}, margin {$this->margin}, background ".($this->background === null ? 'none' : self::describe($this->background)), ''];

        foreach ($this->adjustments as $adjustment) {
            $where = $adjustment->corner->value ?? ($adjustment->cell === null ? '' : implode(',', $adjustment->cell));
            $lines[] = ($adjustment->warning ? 'warning' : 'guard')." {$adjustment->guard} {$where}: {$adjustment->detail}";
        }

        if ($this->adjustments !== []) {
            $lines[] = '';
        }

        foreach ($this->items as $item) {
            $where = $item->corner->value ?? implode(',', $item->cell ?? []).' '.$item->role?->value;
            $opacity = $item->opacity < 1 ? ' opacity '.Number::format($item->opacity) : '';

            $lines[] = "{$item->part->value} {$where} {$item->shapeName} {$item->transform->toString()} ".self::describe($item->paint).$opacity;
        }

        if ($this->logo !== null) {
            $style = $this->logo->style;
            $box = implode(' ', array_map(fn (float $value): string => Number::format($value), $this->logo->box));

            $lines[] = '';
            $lines[] = "logo {$style->logo->mimeType} ".substr($style->logo->hash, 0, 8)." box {$box} size ".Number::format((float) $style->size)
                ." {$style->shape->value} padding {$style->padding} plate ".($style->plate === null ? 'none' : self::describe($style->plate));
        }

        return implode("\n", $lines)."\n";
    }

    public static function describe(Paint $paint): string
    {
        return match (true) {
            $paint instanceof Color => $paint->toCss(),
            $paint instanceof Gradient => substr($paint::class, strrpos($paint::class, '\\') + 1).'('.implode(', ', array_map(
                fn (ColorStop $stop): string => $stop->color->toCss().' '.Number::format($stop->position),
                $paint->stops(),
            )).')',
            $paint instanceof Layered => 'Layered('.implode(', ', array_map(
                fn (Layer $layer): string => self::describe($layer->paint).' '.Number::format($layer->opacity),
                $paint->layers,
            )).')',
            default => $paint::class,
        };
    }
}
