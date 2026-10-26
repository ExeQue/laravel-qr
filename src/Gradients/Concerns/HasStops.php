<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Concerns;

use ExeQue\Qr\Colors\Convert;
use ExeQue\Qr\Colors\Rgb;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Exceptions\InvalidGradientException;
use ExeQue\Qr\Gradients\ColorStop;
use ExeQue\Qr\Gradients\Enums\ColorSpace;
use ExeQue\Qr\Gradients\Enums\HueInterpolation;
use ExeQue\Qr\Gradients\Interpolator;

/**
 * Color stops along a line from 0 to 1. A class supplies `position()`:
 * where on that line a point of the box falls.
 *
 * @internal
 */
trait HasStops
{
    use BlendsColors;

    /**
     * @var list<ColorStop>
     */
    private array $stops = [];

    /**
     * A gradient from one color to another, or an empty one to add stops to.
     */
    public static function make(Color|string|null $from = null, Color|string|null $to = null): static
    {
        $gradient = new static();

        if ($from !== null) {
            $gradient = $gradient->stop($from, 0);
        }

        return $to === null ? $gradient : $gradient->stop($to, 1);
    }

    /**
     * Adds a stop after the others.
     *
     * @param float|null $hint Where, before the next stop, the two colors meet halfway.
     */
    public function stop(Color|string $color, float $position, ?float $hint = null): static
    {
        $stop = new ColorStop($color, $position, $hint);
        $previous = $this->stops === [] ? null : $this->stops[array_key_last($this->stops)];

        if ($previous !== null && $position < $previous->position) {
            throw InvalidGradientException::descendingStop($position, $previous->position);
        }

        if ($previous?->hint !== null && ($previous->hint <= $previous->position || $previous->hint >= $position)) {
            throw InvalidGradientException::hintOutsideStops($previous->hint, $previous->position, $position);
        }

        return clone($this, ['stops' => [...$this->stops, $stop]]);
    }

    public function stops(): array
    {
        return $this->stops;
    }

    /**
     * The stops and settings, as plain values; a kind adds its own geometry.
     *
     * @return array<string, mixed>
     */
    private function stopsToArray(): array
    {
        return [
            'stops' => array_map(fn (ColorStop $stop): array => array_filter([
                'color' => $stop->color->toCss(),
                'position' => $stop->position,
                'hint' => $stop->hint,
            ], fn (mixed $value): bool => $value !== null), $this->stops),
            ...$this->settingsToArray(),
        ];
    }

    /**
     * A gradient with the stops and settings in `$data`.
     *
     * @param array<string, mixed> $data
     */
    private static function fromStops(array $data): static
    {
        $gradient = new static();

        foreach ((array) ($data['stops'] ?? []) as $stop) {
            $gradient = $gradient->stop($stop['color'], (float) $stop['position'], isset($stop['hint']) ? (float) $stop['hint'] : null);
        }

        return $gradient->withSettings($data);
    }

    public function colorAt(float $x, float $y): Color
    {
        return $this->colorAtPosition($this->applySpread($this->position($x, $y)));
    }

    /**
     * The color at a position along the gradient, 0-1.
     *
     * @internal
     */
    public function colorAtPosition(float $position): Color
    {
        $stops = $this->checkedStops();

        if ($position <= $stops[0]->position) {
            return $stops[0]->color;
        }

        foreach (array_slice($stops, 1, preserve_keys: true) as $index => $stop) {
            if ($position > $stop->position) {
                continue;
            }

            // A stop at the same position as the one before it is never reached from here:
            // positions up to it end at the earlier stop, so two such stops make a hard edge.
            $previous = $stops[$index - 1];
            $span = $stop->position - $previous->position;
            $amount = $this->applyHint(($position - $previous->position) / $span, $previous, $span);

            return Interpolator::mix($previous->color, $stop->color, $amount, $this->space, $this->hueInterpolation);
        }

        return $stops[array_key_last($stops)]->color;
    }

    /**
     * Stops that blended in sRGB come within `$tolerance` (in Oklab) of the
     * gradient as it is, for writers that can only blend in sRGB.
     *
     * @internal
     *
     * @return list<ColorStop>
     */
    public function srgbStops(float $tolerance = 0.005): array
    {
        $stops = $this->checkedStops();

        if ($this->space === ColorSpace::Srgb && array_filter($stops, fn (ColorStop $stop): bool => $stop->hint !== null) === []) {
            return $stops;
        }

        $result = [$stops[0]];

        foreach (array_slice($stops, 1) as $index => $stop) {
            array_push($result, ...$this->subdivide($stops[$index]->position, $stop->position, $tolerance, 0));
            // The stop's own color: two stops at one place make a hard edge, which a lookup would blur.
            $result[] = new ColorStop($stop->color, $stop->position);
        }

        return $result;
    }

    /**
     * Where a point of the box falls along the gradient; below 0 or above 1 beyond its ends.
     */
    abstract private function position(float $x, float $y): float;

    /**
     * Stops between `$from` and `$to` wherever sRGB blending strays too far.
     *
     * @return list<ColorStop>
     */
    private function subdivide(float $from, float $to, float $tolerance, int $depth): array
    {
        $middle = ($from + $to) / 2;
        $expected = $this->colorAtPosition($middle);
        $blended = Interpolator::mix($this->colorAtPosition($from), $this->colorAtPosition($to), 0.5, ColorSpace::Srgb, HueInterpolation::Shorter);

        if ($depth >= 6 || $to - $from < 0.001 || self::difference($expected, $blended) <= $tolerance) {
            return [];
        }

        return [
            ...$this->subdivide($from, $middle, $tolerance, $depth + 1),
            new ColorStop($expected, $middle),
            ...$this->subdivide($middle, $to, $tolerance, $depth + 1),
        ];
    }

    /**
     * CSS color hints: the blend reaches halfway at the hint, not at the middle.
     */
    private function applyHint(float $amount, ColorStop $stop, float $span): float
    {
        if ($stop->hint === null) {
            return $amount;
        }

        $hint = ($stop->hint - $stop->position) / $span;

        return $amount ** (log(0.5) / log($hint));
    }

    /**
     * @return non-empty-list<ColorStop>
     */
    private function checkedStops(): array
    {
        if (count($this->stops) < 2) {
            throw InvalidGradientException::tooFewStops(count($this->stops));
        }

        return $this->stops;
    }

    private static function difference(Color $a, Color $b): float
    {
        $first = Convert::xyzToOklab(Rgb::from($a)->toXyz());
        $second = Convert::xyzToOklab(Rgb::from($b)->toXyz());

        return sqrt(($first[0] - $second[0]) ** 2 + ($first[1] - $second[1]) ** 2 + ($first[2] - $second[2]) ** 2);
    }
}
