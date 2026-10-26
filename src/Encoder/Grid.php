<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Encoder\Enums\Role;

/**
 * The symbol while it is being built: function patterns first, then the
 * codewords, then a mask and the format information.
 *
 * Cells are stored row by row, at `y * size + x`.
 *
 * @internal
 */
final class Grid
{
    // BCH(15, 5) generator and mask for the format information, ISO/IEC 18004:2015 section 7.9.1.
    private const int FORMAT_GENERATOR = 0b10100110111;

    private const int FORMAT_MASK = 0b101010000010010;

    // BCH(18, 6) generator for the version information, ISO/IEC 18004:2015 section 7.10.
    private const int VERSION_GENERATOR = 0b1111100100101;

    /**
     * @var positive-int
     */
    public readonly int $size;

    /**
     * @var array<int, int> 1 for dark, 0 for light.
     */
    private array $dark;

    /**
     * @var array<int, Role|null> Null until the cell is given a role.
     */
    private array $roles;

    /**
     * @param int<1, 40> $version
     */
    public function __construct(
        public readonly int $version,
    ) {
        $this->size = 17 + 4 * $version;
        $this->dark = array_fill(0, $this->size ** 2, 0);
        $this->roles = array_fill(0, $this->size ** 2, null);

        $this->drawFinders();
        $this->drawAlignmentPatterns();
        $this->drawTiming();
        $this->reserveFormat();
        $this->drawVersion();
    }

    /**
     * Cells left for data, in modules.
     */
    public function dataModules(): int
    {
        return count(array_filter($this->roles, fn (?Role $role): bool => $role === null || $role === Role::Data));
    }

    /**
     * Fills the data cells in the standard zigzag; cells beyond the codewords (remainder bits) stay light.
     *
     * @param list<int> $codewords
     */
    public function place(array $codewords): void
    {
        $bits = count($codewords) * 8;

        foreach ($this->dataCells() as $bit => [$x, $y]) {
            $index = $y * $this->size + $x;

            $this->roles[$index] = Role::Data;
            $this->dark[$index] = $bit < $bits ? ($codewords[$bit >> 3] >> (7 - ($bit & 7))) & 1 : 0;
        }
    }

    /**
     * The data cells in the order bits go in: the standard zigzag, two columns at a time from
     * the bottom right, skipping the function patterns.
     *
     * @return list<array{int, int}>
     */
    public function dataCells(): array
    {
        $cells = [];

        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            // The vertical timing pattern takes a whole column.
            if ($right === 6) {
                $right = 5;
            }

            $upwards = (($right + 1) & 2) === 0;

            for ($step = 0; $step < $this->size; $step++) {
                $y = $upwards ? $this->size - 1 - $step : $step;

                for ($x = $right; $x > $right - 2; $x--) {
                    $role = $this->roles[$y * $this->size + $x];

                    if ($role === null || $role === Role::Data) {
                        $cells[] = [$x, $y];
                    }
                }
            }
        }

        return $cells;
    }

    /**
     * A copy with `$mask` over the data cells and the format information written.
     */
    public function masked(Mask $mask, ErrorCorrection $level): self
    {
        $grid = clone $this;

        foreach ($grid->roles as $index => $role) {
            if ($role === Role::Data && $mask->flips($index % $this->size, intdiv($index, $this->size))) {
                $grid->dark[$index] ^= 1;
            }
        }

        $grid->drawFormat($mask, $level);

        return $grid;
    }

    /**
     * The mask penalty of ISO/IEC 18004:2015 section 7.8.3: runs, blocks,
     * finder-like patterns and the balance of dark and light.
     */
    public function penalty(): int
    {
        $penalty = 0;
        $rows = array_chunk($this->dark, $this->size);
        $columns = array_map(null, ...$rows);

        foreach ([...$rows, ...$columns] as $line) {
            $penalty += $this->runPenalty($line) + $this->finderPenalty($line);
        }

        for ($y = 0; $y < $this->size - 1; $y++) {
            for ($x = 0; $x < $this->size - 1; $x++) {
                $color = $rows[$y][$x];

                if ($rows[$y][$x + 1] === $color && $rows[$y + 1][$x] === $color && $rows[$y + 1][$x + 1] === $color) {
                    $penalty += 3;
                }
            }
        }

        $percent = array_sum($this->dark) * 100 / count($this->dark);

        return $penalty + (int) floor(abs($percent - 50) / 5) * 10;
    }

    public function toMatrix(ErrorCorrection $level, Mask $mask): Matrix
    {
        return new Matrix(
            $this->version,
            $level,
            $mask,
            array_map(fn (int $dark): bool => $dark === 1, $this->dark),
            array_map(fn (?Role $role): Role => $role ?? Role::Data, $this->roles),
        );
    }

    /**
     * @param list<int> $line
     */
    private function runPenalty(array $line): int
    {
        $penalty = 0;
        $run = 1;

        for ($index = 1, $length = count($line); $index <= $length; $index++) {
            if ($index < $length && $line[$index] === $line[$index - 1]) {
                $run++;

                continue;
            }

            if ($run >= 5) {
                $penalty += $run - 2;
            }

            $run = 1;
        }

        return $penalty;
    }

    /**
     * 1:1:3:1:1 dark-light-dark-light-dark with four light modules on either
     * side; cells beyond the edge count as light, as the quiet zone is.
     *
     * @param list<int> $line
     */
    private function finderPenalty(array $line): int
    {
        $penalty = 0;
        $padded = [0, 0, 0, 0, ...$line, 0, 0, 0, 0];

        // Every window of seven that fits on the line, the last one ending on its last module.
        for ($start = 4, $end = count($line) - 2; $start < $end; $start++) {
            if (array_slice($padded, $start, 7) !== [1, 0, 1, 1, 1, 0, 1]) {
                continue;
            }

            $lightBefore = array_sum(array_slice($padded, $start - 4, 4)) === 0;
            $lightAfter = array_sum(array_slice($padded, $start + 7, 4)) === 0;

            if ($lightBefore || $lightAfter) {
                $penalty += 40;
            }
        }

        return $penalty;
    }

    private function drawFinders(): void
    {
        $far = $this->size - 4;

        foreach ([[3, 3], [$far, 3], [3, $far]] as [$centerX, $centerY]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $ring = max(abs($dx), abs($dy));

                    $ring === 4
                        ? $this->set($centerX + $dx, $centerY + $dy, false, Role::Separator)
                        : $this->set($centerX + $dx, $centerY + $dy, $ring !== 2, Role::Finder);
                }
            }
        }
    }

    private function drawAlignmentPatterns(): void
    {
        $centers = Tables::alignmentCenters($this->version);
        $last = end($centers);

        foreach ($centers as $centerY) {
            foreach ($centers as $centerX) {
                // The three corners with a finder pattern have no alignment pattern.
                if (($centerX === 6 && ($centerY === 6 || $centerY === $last)) || ($centerX === $last && $centerY === 6)) {
                    continue;
                }

                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $this->set($centerX + $dx, $centerY + $dy, max(abs($dx), abs($dy)) !== 1, Role::Alignment);
                    }
                }
            }
        }
    }

    private function drawTiming(): void
    {
        for ($index = 8; $index < $this->size - 8; $index++) {
            // Alignment patterns sitting on the timing lines keep their own cells.
            if ($this->roles[6 * $this->size + $index] === null) {
                $this->set($index, 6, $index % 2 === 0, Role::Timing);
            }

            if ($this->roles[$index * $this->size + 6] === null) {
                $this->set(6, $index, $index % 2 === 0, Role::Timing);
            }
        }
    }

    /**
     * Marks the format cells, written per mask later, and the dark module.
     */
    private function reserveFormat(): void
    {
        foreach ($this->formatCells() as [$first, $second]) {
            $this->set($first[0], $first[1], false, Role::Format);
            $this->set($second[0], $second[1], false, Role::Format);
        }

        $this->set(8, $this->size - 8, true, Role::DarkModule);
    }

    /**
     * The 15 format bits for a level and a mask, masked as they are written.
     */
    public static function formatWord(ErrorCorrection $level, Mask $mask): int
    {
        $data = ($level->formatBits() << 3) | $mask->value;

        return (($data << 10) | self::bchRemainder($data, self::FORMAT_GENERATOR, 10)) ^ self::FORMAT_MASK;
    }

    /**
     * The 18 version bits, for versions 7 and up.
     */
    public static function versionWord(int $version): int
    {
        return ($version << 12) | self::bchRemainder($version, self::VERSION_GENERATOR, 12);
    }

    private function drawFormat(Mask $mask, ErrorCorrection $level): void
    {
        $bits = self::formatWord($level, $mask);

        foreach ($this->formatCells() as $bit => [$first, $second]) {
            $dark = (($bits >> $bit) & 1) === 1;

            $this->set($first[0], $first[1], $dark, Role::Format);
            $this->set($second[0], $second[1], $dark, Role::Format);
        }
    }

    /**
     * Both cells of each format bit, least significant first (ISO/IEC 18004:2015 figure 25):
     * one copy round the top-left finder, the other split between the other two.
     *
     * @return list<array{array{int, int}, array{int, int}}>
     */
    public function formatCells(): array
    {
        $cells = [];

        for ($bit = 0; $bit < 15; $bit++) {
            $first = match (true) {
                $bit < 6 => [8, $bit],
                $bit < 8 => [8, $bit + 1],
                $bit === 8 => [7, 8],
                default => [14 - $bit, 8],
            };

            $second = $bit < 8 ? [$this->size - 1 - $bit, 8] : [8, $this->size - 15 + $bit];

            $cells[] = [$first, $second];
        }

        return $cells;
    }

    private function drawVersion(): void
    {
        if ($this->version < 7) {
            return;
        }

        $bits = self::versionWord($this->version);

        foreach ($this->versionCells() as $bit => [$first, $second]) {
            $dark = (($bits >> $bit) & 1) === 1;

            $this->set($first[0], $first[1], $dark, Role::Version);
            $this->set($second[0], $second[1], $dark, Role::Version);
        }
    }

    /**
     * Both cells of each version bit, least significant first: two 6x3 blocks, beside the
     * top-right finder and above the bottom-left one.
     *
     * @return list<array{array{int, int}, array{int, int}}>
     */
    public function versionCells(): array
    {
        $cells = [];

        for ($bit = 0; $bit < 18; $bit++) {
            $across = $this->size - 11 + $bit % 3;
            $along = intdiv($bit, 3);

            $cells[] = [[$across, $along], [$along, $across]];
        }

        return $cells;
    }

    private static function bchRemainder(int $data, int $generator, int $degree): int
    {
        $remainder = $data << $degree;

        // Long division by the generator; the data is at most 6 bits.
        for ($bit = $degree + 5; $bit >= $degree; $bit--) {
            if ((($remainder >> $bit) & 1) === 1) {
                $remainder ^= $generator << ($bit - $degree);
            }
        }

        return $remainder;
    }

    private function set(int $x, int $y, bool $dark, Role $role): void
    {
        if ($x < 0 || $y < 0 || $x >= $this->size || $y >= $this->size) {
            return;
        }

        $this->dark[$y * $this->size + $x] = $dark ? 1 : 0;
        $this->roles[$y * $this->size + $x] = $role;
    }
}
