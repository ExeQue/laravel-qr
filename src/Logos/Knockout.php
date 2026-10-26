<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos;

use Closure;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Writers\Raster\Enums\Driver;
use Imagick;

/**
 * Where a logo sits on a symbol, and which cells it clears. A centered logo
 * shrinks until it covers no function pattern; a placed one must not cover any.
 *
 * Coordinates are in modules from the symbol's top left, quiet zone not counted.
 *
 * @internal
 */
final readonly class Knockout
{
    // An automatic size stays within these, of the symbol's width: larger logos crowd the code.
    public const float AUTO_MAX = 0.3;

    private const float AUTO_MIN = 0.05;


    // A centered logo shrinks by this much at a time while it covers a function pattern.
    private const float SHRINK = 0.95;

    /**
     * @param LogoStyle $style As placed: a centered logo at the size it fits at.
     * @param array{float, float, float, float} $box The logo itself: x, y, width and height.
     * @param array<string, true> $cleared The cleared cells, by "column,row".
     */
    private function __construct(
        public LogoStyle $style,
        public array $box,
        public Region $area,
        private array $cleared,
        public int $clearedDataCells,
    ) {
    }

    /**
     * @param float $maxShare For a logo sized automatically: the share of what the error correction can
     *                        restore that it may clear.
     */
    public static function for(Matrix $matrix, LogoStyle $style, float $maxShare = 0.6): self
    {
        if ($style->size === null) {
            $style = $style->withSize(max(self::AUTO_MIN, min(self::AUTO_MAX, self::maxSize($matrix, $style, $maxShare))));
        }

        return self::fit($matrix, $style);
    }

    /**
     * The logo placed, a centered one shrunk until it covers no function pattern.
     */
    private static function fit(Matrix $matrix, LogoStyle $style): self
    {
        while (true) {
            $knockout = self::place($matrix, $style);

            if (! $knockout->coversFunctionPatterns($matrix)) {
                return $knockout;
            }

            if ($style->position !== null) {
                throw InvalidLogoException::coversFunctionPatterns();
            }

            $style = $style->withSize((float) $style->size * self::SHRINK);
        }
    }

    /**
     * The largest logo, as a share of the symbol's width, that leaves at most
     * `$maxShare` of what the error correction can restore to restore.
     */
    public static function maxSize(Matrix $matrix, LogoStyle $style, float $maxShare): float
    {
        [$low, $high] = [0.0, 1.0];

        // A size shrunk to fit clear of the function patterns is what counts, so compare with that.
        for ($step = 0; $step < 20; $step++) {
            $size = ($low + $high) / 2;
            $knockout = self::fit($matrix, $style->withSize($size));

            $knockout->clearedDataCells <= self::budget($matrix, $maxShare) && (float) $knockout->style->size >= $size * 0.999
                ? $low = $size
                : $high = $size;
        }

        return $low;
    }

    /**
     * The data cells a logo may clear: a share of what the error correction can restore.
     */
    public static function budget(Matrix $matrix, float $maxShare): int
    {
        return (int) floor($maxShare * $matrix->errorCorrection->recoveryRatio() * self::dataCells($matrix));
    }

    public function clears(int $column, int $row): bool
    {
        return isset($this->cleared["{$column},{$row}"]);
    }

    /**
     * @return list<array{int, int}> Columns and rows.
     */
    public function clearedCells(): array
    {
        return array_map(function (string $cell): array {
            [$column, $row] = explode(',', $cell);

            return [(int) $column, (int) $row];
        }, array_keys($this->cleared));
    }

    private static function place(Matrix $matrix, LogoStyle $style): self
    {
        $width = (float) $style->size * $matrix->size;
        $height = $width / $style->logo->aspectRatio();
        [$x, $y] = $style->position ?? [($matrix->size - $width) / 2, ($matrix->size - $height) / 2];
        $box = [$x, $y, $width, $height];

        // The plate fills the padding; the modules clear half a module further, as a module stays
        // when its middle is outside: so none comes nearer the logo than the padding.
        $area = self::area($matrix, $style, $box, $style->padding);
        $clear = $style->shape === LogoShape::Alpha ? $area : self::area($matrix, $style, $box, $style->padding + 0.5);

        $cleared = [];
        $data = 0;

        for ($row = 0; $row < $matrix->size; $row++) {
            for ($column = 0; $column < $matrix->size; $column++) {
                if ($clear->contains($column + 0.5, $row + 0.5)) {
                    $cleared["{$column},{$row}"] = true;
                    $data += $matrix->role($column, $row) === Role::Data ? 1 : 0;
                }
            }
        }

        return new self($style, $box, $area, $cleared, $data);
    }

    /**
     * The logo's box grown by `$padding` modules, in the logo's shape.
     *
     * @param array{float, float, float, float} $box
     */
    private static function area(Matrix $matrix, LogoStyle $style, array $box, float $padding): Region
    {
        [$x, $y, $width, $height] = $box;

        return match ($style->shape) {
            LogoShape::Circle => Region::circle($x + $width / 2, $y + $height / 2, max($width, $height) / 2 + $padding),
            LogoShape::Rounded => Region::rounded($x - $padding, $y - $padding, $width + 2 * $padding, $height + 2 * $padding, min($width, $height) / 4 + $padding),
            LogoShape::Alpha => self::visibleCells($matrix, $style, $box),
            LogoShape::Square => Region::rect($x - $padding, $y - $padding, $width + 2 * $padding, $height + 2 * $padding),
        };
    }

    /**
     * The cells under the logo's visible pixels, grown by the padding. An SVG logo,
     * which cannot be read pixel by pixel here, clears its whole box instead.
     *
     * @param array{float, float, float, float} $box
     */
    private static function visibleCells(Matrix $matrix, LogoStyle $style, array $box): Region
    {
        [$x, $y, $width, $height] = $box;
        $padding = $style->padding;

        if ($style->logo->isSvg()) {
            return Region::rect($x - $padding, $y - $padding, $width + 2 * $padding, $height + 2 * $padding);
        }

        // The logo at one pixel per module, over the cells it covers.
        [$columns, $rows] = [(int) ceil($width), (int) ceil($height)];
        $opacity = self::opacities($style->logo->contents, $columns, $rows);

        $visible = [];

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                if ($opacity($column, $row) > 0.1) {
                    $visible[] = [(int) floor($x + $column + 0.5), (int) floor($y + $row + 0.5)];
                }
            }
        }

        // Grow the visible cells by the padding.
        $cells = [];

        foreach ($visible as [$column, $row]) {
            for ($dy = -$padding; $dy <= $padding; $dy++) {
                for ($dx = -$padding; $dx <= $padding; $dx++) {
                    $cells["{$column},{$row}"] = [$column + $dx, $row + $dy];
                }
            }
        }

        return Region::cells(array_values(array_unique($cells, SORT_REGULAR)));
    }

    private function coversFunctionPatterns(Matrix $matrix): bool
    {
        foreach (array_keys($this->cleared) as $cell) {
            [$column, $row] = array_map(intval(...), explode(',', $cell));

            if ($matrix->role($column, $row) !== Role::Data) {
                return true;
            }
        }

        return false;
    }

    private static function dataCells(Matrix $matrix): int
    {
        $count = 0;

        for ($row = 0; $row < $matrix->size; $row++) {
            for ($column = 0; $column < $matrix->size; $column++) {
                $count += $matrix->role($column, $row) === Role::Data ? 1 : 0;
            }
        }

        return $count;
    }

    /**
     * How opaque the logo is at each pixel, scaled down to `$columns` by `$rows`, 0-1.
     *
     * @return Closure(int, int): float
     */
    private static function opacities(string $logo, int $columns, int $rows): Closure
    {
        if (Driver::resolve() === Driver::Gd) {
            $image = imagecreatefromstring($logo) ?: imagecreatetruecolor(1, 1);
            $small = imagecreatetruecolor(max(1, $columns), max(1, $rows));
            imagealphablending($small, false);
            imagefill($small, 0, 0, 0x7F000000);
            imagecopyresampled($small, $image, 0, 0, 0, 0, $columns, $rows, imagesx($image), imagesy($image));

            return fn (int $column, int $row): float => (127 - ((imagecolorat($small, $column, $row) >> 24) & 0x7F)) / 127;
        }

        $image = new Imagick();
        $image->readImageBlob($logo);
        $image->resizeImage($columns, $rows, Imagick::FILTER_BOX, 1);

        return fn (int $column, int $row): float => $image->getImagePixelColor($column, $row)->getColorValue(Imagick::COLOR_ALPHA);
    }
}
