<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Rendering\ModuleContext;
use Illuminate\Contracts\Support\Arrayable;

/**
 * An area of the symbol, in modules from its top left: where a filter applies.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Region implements Arrayable
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(
        public string $kind,
        public array $values,
    ) {
    }

    public static function rect(float $x, float $y, float $width, float $height): self
    {
        return new self('rect', ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);
    }

    /**
     * A rectangle with rounded corners.
     */
    public static function rounded(float $x, float $y, float $width, float $height, float $radius): self
    {
        return new self('rounded', ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height, 'radius' => min($radius, $width / 2, $height / 2)]);
    }

    /**
     * Wherever the code's logo clears its modules; nothing when it has no logo.
     */
    public static function logo(): self
    {
        return new self('logo', []);
    }

    public static function circle(float $centerX, float $centerY, float $radius): self
    {
        return new self('circle', ['x' => $centerX, 'y' => $centerY, 'radius' => $radius]);
    }

    /**
     * Exactly these cells.
     *
     * @param list<array{int, int}> $cells Columns and rows.
     */
    public static function cells(array $cells): self
    {
        return new self('cells', ['cells' => array_map(fn (array $cell): string => "{$cell[0]},{$cell[1]}", $cells)]);
    }

    /**
     * @param array<string, mixed> $data As `toArray()` gives it.
     */
    public static function fromArray(array $data): self
    {
        $kind = (string) ($data['kind'] ?? '');
        unset($data['kind']);

        if (! in_array($kind, ['rect', 'rounded', 'circle', 'cells', 'logo'], true)) {
            throw InvalidFilterException::unknownRegion($kind);
        }

        // Stored as JSON, 0.0 comes back as 0: positions and sizes are floats again.
        $values = $kind === 'cells'
            ? ['cells' => array_map(strval(...), (array) ($data['cells'] ?? []))]
            : array_map(floatval(...), $data);

        // A rounded rectangle's corners are no rounder than half its shorter side, as rounded() makes them.
        if ($kind === 'rounded') {
            $values['radius'] = min((float) ($data['radius'] ?? 0), (float) ($data['width'] ?? 0) / 2, (float) ($data['height'] ?? 0) / 2);
        }

        return new self($kind, $values);
    }

    /**
     * Whether a cell's middle lies inside; the logo region asks the cell where its logo is.
     */
    public function containsCell(ModuleContext $cell): bool
    {
        return $this->kind === 'logo' ? ($cell->logo?->contains($cell->x, $cell->y) ?? false) : $this->contains($cell->x, $cell->y);
    }

    /**
     * Whether a point lies inside.
     */
    public function contains(float $x, float $y): bool
    {
        $values = $this->values;

        return match ($this->kind) {
            'rect' => $x >= $values['x'] && $x <= $values['x'] + $values['width'] && $y >= $values['y'] && $y <= $values['y'] + $values['height'],
            'rounded' => self::inRounded($x, $y, $values),
            'logo' => false,
            'circle' => ($x - $values['x']) ** 2 + ($y - $values['y']) ** 2 <= $values['radius'] ** 2,
            default => in_array((int) floor($x).','.(int) floor($y), $values['cells'], true),
        };
    }

    /**
     * @param array<string, float> $box
     */
    private static function inRounded(float $x, float $y, array $box): bool
    {
        ['x' => $left, 'y' => $top, 'width' => $width, 'height' => $height, 'radius' => $radius] = $box;

        // Inside the rectangle shrunk by the radius, give or take the radius.
        $dx = max($left + $radius - $x, 0, $x - ($left + $width - $radius));
        $dy = max($top + $radius - $y, 0, $y - ($top + $height - $radius));

        return $x >= $left && $x <= $left + $width && $y >= $top && $y <= $top + $height && $dx ** 2 + $dy ** 2 <= $radius ** 2;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind, ...$this->values];
    }
}
