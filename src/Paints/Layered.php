<?php

declare(strict_types=1);

namespace ExeQue\Qr\Paints;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Paints stacked on the same shapes, the first at the bottom: a gradient with a
 * second one faded over it, a color with a glow on top.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Layered implements Arrayable, Paint
{
    /**
     * @var list<Layer>
     */
    public array $layers;

    /**
     * @param list<Layer> $layers The bottom first.
     */
    public function __construct(array $layers)
    {
        if ($layers === []) {
            throw InvalidStyleException::noLayers();
        }

        $this->layers = $layers;
    }

    /**
     * Paints from the bottom up, each fully shown: give a color with alpha, or use `layer()`, to let
     * the ones under it through.
     *
     * @param Paint|string|array<string, mixed> ...$paints
     */
    public static function make(Paint|string|array ...$paints): self
    {
        return new self(array_values(array_map(fn (Paint|string|array $paint): Layer => new Layer(Hydrate::paint($paint)), $paints)));
    }

    /**
     * This paint with one more on top.
     *
     * @param Paint|string|array<string, mixed> $paint
     * @param float $opacity 0-1.
     */
    public function layer(Paint|string|array $paint, float $opacity = 1.0): self
    {
        return new self([...$this->layers, new Layer(Hydrate::paint($paint), $opacity)]);
    }

    /**
     * @return array{layers: list<array{paint: mixed, opacity: float}>}
     */
    public function toArray(): array
    {
        return ['layers' => array_map(fn (Layer $layer): array => ['paint' => Normalizer::value($layer->paint), 'opacity' => $layer->opacity], $this->layers)];
    }

    /**
     * @param array{layers?: mixed} $data As `toArray()` gives it.
     */
    public static function fromArray(array $data): self
    {
        $layers = is_array($data['layers'] ?? null) ? $data['layers'] : throw InvalidStyleException::unreadable('layered paint', $data);

        return new self(array_values(array_map(
            fn (mixed $layer): Layer => is_array($layer) && array_key_exists('paint', $layer)
                ? new Layer(Hydrate::paint($layer['paint']), (float) ($layer['opacity'] ?? 1.0))
                : throw InvalidStyleException::unreadable('paint layer', $layer),
            $layers,
        )));
    }
}
