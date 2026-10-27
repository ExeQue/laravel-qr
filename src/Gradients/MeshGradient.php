<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidGradientException;
use ExeQue\Qr\Gradients\Concerns\BlendsColors;
use ExeQue\Qr\Gradients\Contracts\SerializableGradient;

/**
 * A grid of colors spread evenly over the box, blended between neighbors:
 * soft washes of several colors. A mesh has corners, not stops.
 */
final class MeshGradient implements SerializableGradient
{
    use BlendsColors;

    /**
     * @var list<list<ColorContract>> Top to bottom, each left to right.
     */
    public private(set) array $rows;

    /**
     * @param array<array-key, array<array-key, ColorContract|string>> $rows At least two rows of at least two colors, all as long.
     */
    public function __construct(array $rows)
    {
        // Rows and colors by position, whatever keys they came with.
        $rows = array_values(array_map(array_values(...), $rows));
        $width = count($rows[0] ?? []);

        if (count($rows) < 2 || $width < 2 || array_any($rows, fn (array $row): bool => count($row) !== $width)) {
            throw InvalidGradientException::emptyMesh();
        }

        $this->rows = array_map(fn (array $row): array => array_map(Color::make(...), $row), $rows);
    }

    /**
     * @param list<list<ColorContract|string>> $rows At least two rows of at least two colors, all as long.
     */
    public static function make(array $rows): self
    {
        return new self($rows);
    }

    public function colorAt(float $x, float $y): ColorContract
    {
        [$column, $across] = $this->cell($this->applySpread($x), count($this->rows[0]));
        [$row, $down] = $this->cell($this->applySpread($y), count($this->rows));

        $top = Interpolator::mix($this->rows[$row][$column], $this->rows[$row][$column + 1], $across, $this->space, $this->hueInterpolation);
        $bottom = Interpolator::mix($this->rows[$row + 1][$column], $this->rows[$row + 1][$column + 1], $across, $this->space, $this->hueInterpolation);

        return Interpolator::mix($top, $bottom, $down, $this->space, $this->hueInterpolation);
    }

    public function stops(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'rows' => array_map(fn (array $row): array => array_map(fn (ColorContract $color): string => $color->toCss(), $row), $this->rows),
            ...$this->settingsToArray(),
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self($data['rows'] ?? [])->withSettings($data);
    }

    /**
     * The cell a position 0-1 falls in, among `$count` colors, and how far across it.
     *
     * @return array{int, float}
     */
    private function cell(float $position, int $count): array
    {
        $scaled = $position * ($count - 1);
        $index = min((int) floor($scaled), $count - 2);

        return [$index, $scaled - $index];
    }
}
