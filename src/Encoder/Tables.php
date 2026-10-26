<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

use ExeQue\Qr\Encoder\Enums\ErrorCorrection;

/**
 * The constants of the symbol's structure, per version 1-40.
 *
 * @internal
 */
final class Tables
{
    // Total codewords per version, ISO/IEC 18004:2015 table 1 (also Thonky, "Error Correction Code Words and Block Information").
    private const array TOTAL_CODEWORDS = [
        26, 44, 70, 100, 134, 172, 196, 242, 292, 346,
        404, 466, 532, 581, 655, 733, 815, 901, 991, 1085,
        1156, 1258, 1364, 1474, 1588, 1706, 1828, 1921, 2051, 2185,
        2323, 2465, 2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706,
    ];

    // Error correction codewords per block, ISO/IEC 18004:2015 table 9 (also Thonky, "Error Correction Code Words and Block Information").
    private const array EC_CODEWORDS_PER_BLOCK = [
        'L' => [
            7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28,
            28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30,
        ],
        'M' => [
            10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26,
            26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28,
        ],
        'Q' => [
            13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30,
            28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30,
        ],
        'H' => [
            17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28,
            30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30,
        ],
    ];

    // Error correction blocks, ISO/IEC 18004:2015 table 9 (also Thonky, "Error Correction Code Words and Block Information").
    private const array EC_BLOCKS = [
        'L' => [
            1, 1, 1, 1, 1, 2, 2, 2, 2, 4, 4, 4, 4, 4, 6, 6, 6, 6, 7, 8,
            8, 9, 9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25,
        ],
        'M' => [
            1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16,
            17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49,
        ],
        'Q' => [
            1, 1, 2, 2, 4, 4, 6, 6, 8, 8, 8, 10, 12, 16, 12, 17, 16, 18, 21, 20,
            23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68,
        ],
        'H' => [
            1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25,
            25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81,
        ],
    ];

    // Row and column centers of the alignment patterns, ISO/IEC 18004:2015 annex E table E.1
    // (also Thonky, "Alignment Pattern Locations").
    private const array ALIGNMENT_CENTERS = [
        [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34],
        [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50], [6, 30, 54], [6, 32, 58], [6, 34, 62],
        [6, 26, 46, 66], [6, 26, 48, 70], [6, 26, 50, 74], [6, 30, 54, 78], [6, 30, 56, 82], [6, 30, 58, 86], [6, 34, 62, 90],
        [6, 28, 50, 72, 94], [6, 26, 50, 74, 98], [6, 30, 54, 78, 102], [6, 28, 54, 80, 106], [6, 32, 58, 84, 110],
        [6, 30, 58, 86, 114], [6, 34, 62, 90, 118],
        [6, 26, 50, 74, 98, 122], [6, 30, 54, 78, 102, 126], [6, 26, 52, 78, 104, 130], [6, 30, 56, 82, 108, 134],
        [6, 34, 60, 86, 112, 138], [6, 30, 58, 86, 114, 142], [6, 34, 62, 90, 118, 146],
        [6, 30, 54, 78, 102, 126, 150], [6, 24, 50, 76, 102, 128, 154], [6, 28, 54, 80, 106, 132, 158],
        [6, 32, 58, 84, 110, 136, 162], [6, 26, 54, 82, 110, 138, 166], [6, 30, 58, 86, 114, 142, 170],
    ];

    public static function totalCodewords(int $version): int
    {
        return self::TOTAL_CODEWORDS[$version - 1];
    }

    /**
     * @return positive-int
     */
    public static function blocks(int $version, ErrorCorrection $level): int
    {
        return self::EC_BLOCKS[$level->value][$version - 1];
    }

    public static function ecCodewordsPerBlock(int $version, ErrorCorrection $level): int
    {
        return self::EC_CODEWORDS_PER_BLOCK[$level->value][$version - 1];
    }

    public static function dataCodewords(int $version, ErrorCorrection $level): int
    {
        return self::totalCodewords($version) - self::blocks($version, $level) * self::ecCodewordsPerBlock($version, $level);
    }

    /**
     * @return list<int>
     */
    public static function alignmentCenters(int $version): array
    {
        return self::ALIGNMENT_CENTERS[$version - 1];
    }
}
