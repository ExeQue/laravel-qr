<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding\Native;

use ExeQue\Qr\Decoding\Decoded;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Encoder\Grid;
use ExeQue\Qr\Encoder\ReedSolomon;
use ExeQue\Qr\Encoder\Tables;
use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * Reads the content of a symbol from its modules (ISO/IEC 18004:2015 section 12): the format
 * and version information, the data under the mask, the blocks put right, then the segments.
 *
 * @internal
 */
final readonly class SymbolReader
{
    public function __construct(
        private ReedSolomon $reedSolomon = new ReedSolomon(),
        private SegmentParser $segments = new SegmentParser(),
    ) {
    }

    /**
     * @param list<list<bool>> $modules Dark modules by row, then column; a square of 21 to 177.
     */
    public function read(array $modules): Decoded
    {
        $size = count($modules);

        if ($size < 21 || $size > 177 || ($size - 17) % 4 !== 0) {
            throw UnreadableCodeException::noCode();
        }

        $version = max(1, min(40, intdiv($size - 17, 4)));

        $grid = new Grid($version);
        [$level, $mask] = $this->format($modules, $grid);

        $codewords = $this->codewords($modules, $grid, $mask, Tables::totalCodewords($version));
        $data = $this->correct($codewords, $version, $level);

        $text = $this->segments->parse($data, $version);

        return new Decoded($text, $version, $level, $mask, bytes: $this->segments->raw());
    }

    /**
     * The level and mask whose format bits are nearest to either copy, if near enough to tell.
     *
     * @param list<list<bool>> $modules
     *
     * @return array{ErrorCorrection, Mask}
     */
    private function format(array $modules, Grid $grid): array
    {
        [$first, $second] = [0, 0];

        foreach ($grid->formatCells() as $bit => [[$x1, $y1], [$x2, $y2]]) {
            $first |= ($modules[$y1][$x1] ? 1 : 0) << $bit;
            $second |= ($modules[$y2][$x2] ? 1 : 0) << $bit;
        }

        $best = null;
        $distance = 4;

        foreach (ErrorCorrection::cases() as $level) {
            foreach (Mask::cases() as $mask) {
                $word = Grid::formatWord($level, $mask);
                $nearest = min(self::bitsApart($word, $first), self::bitsApart($word, $second));

                if ($nearest < $distance) {
                    [$best, $distance] = [[$level, $mask], $nearest];
                }
            }
        }

        return $best ?? throw UnreadableCodeException::badFormat();
    }

    /**
     * The version a symbol's version information says, when it has one and it is near enough
     * to a valid word to tell: a check on a size measured from an image.
     *
     * @param list<list<bool>> $modules
     */
    public static function versionInformation(array $modules): ?int
    {
        $size = count($modules);

        if ($size < 45) {
            return null;
        }

        $grid = new Grid(max(7, min(40, intdiv($size - 17, 4))));
        [$first, $second] = [0, 0];

        foreach ($grid->versionCells() as $bit => [[$x1, $y1], [$x2, $y2]]) {
            $first |= ($modules[$y1][$x1] ? 1 : 0) << $bit;
            $second |= ($modules[$y2][$x2] ? 1 : 0) << $bit;
        }

        $best = null;
        $distance = 4;

        for ($version = 7; $version <= 40; $version++) {
            $word = Grid::versionWord($version);
            $nearest = min(self::bitsApart($word, $first), self::bitsApart($word, $second));

            if ($nearest < $distance) {
                [$best, $distance] = [$version, $nearest];
            }
        }

        return $best;
    }

    /**
     * The codewords in the zigzag, the mask taken off.
     *
     * @param list<list<bool>> $modules
     *
     * @return list<int>
     */
    private function codewords(array $modules, Grid $grid, Mask $mask, int $count): array
    {
        $codewords = array_fill(0, $count, 0);

        foreach ($grid->dataCells() as $bit => [$x, $y]) {
            if ($bit >= $count * 8) {
                break;
            }

            if ($modules[$y][$x] !== $mask->flips($x, $y)) {
                $codewords[$bit >> 3] |= 1 << (7 - ($bit & 7));
            }
        }

        return array_values($codewords);
    }

    /**
     * The data codewords: the blocks taken apart from their interleaving and each put right.
     *
     * @param list<int> $codewords
     *
     * @return list<int>
     */
    private function correct(array $codewords, int $version, ErrorCorrection $level): array
    {
        $blocks = Tables::blocks($version, $level);
        $ecPerBlock = Tables::ecCodewordsPerBlock($version, $level);
        $total = Tables::totalCodewords($version);

        // The first blocks are one data codeword shorter than the rest.
        $shortBlocks = $blocks - $total % $blocks;
        $shortData = intdiv($total, $blocks) - $ecPerBlock;
        $lengths = [];

        for ($block = 0; $block < $blocks; $block++) {
            $lengths[] = $shortData + ($block < $shortBlocks ? 0 : 1);
        }

        $data = array_fill(0, $blocks, []);
        $ec = array_fill(0, $blocks, []);
        $next = 0;

        for ($index = 0; $index <= $shortData; $index++) {
            for ($block = 0; $block < $blocks; $block++) {
                if ($index < $lengths[$block]) {
                    $data[$block][] = $codewords[$next++];
                }
            }
        }

        for ($index = 0; $index < $ecPerBlock; $index++) {
            for ($block = 0; $block < $blocks; $block++) {
                $ec[$block][] = $codewords[$next++];
            }
        }

        $corrected = [];

        foreach ($data as $block => $words) {
            $fixed = $this->reedSolomon->correct([...$words, ...$ec[$block]], $ecPerBlock, self::protectedCodewords($version, $level));
            array_push($corrected, ...array_slice($fixed, 0, $lengths[$block]));
        }

        return $corrected;
    }

    /**
     * The codewords the smallest symbols keep back from correction, to detect a wrong repair
     * instead (ISO/IEC 18004:2015 table 9).
     */
    private static function protectedCodewords(int $version, ErrorCorrection $level): int
    {
        return match ([$version, $level]) {
            [1, ErrorCorrection::Low] => 3,
            [1, ErrorCorrection::Medium], [2, ErrorCorrection::Low] => 2,
            [1, ErrorCorrection::Quartile], [1, ErrorCorrection::High], [3, ErrorCorrection::Low] => 1,
            default => 0,
        };
    }

    private static function bitsApart(int $a, int $b): int
    {
        return substr_count(decbin($a ^ $b), '1');
    }
}
