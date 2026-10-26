<?php

declare(strict_types=1);

namespace Tests\Unit\Encoder;

use ExeQue\Qr\Encoder\BitBuffer;
use ExeQue\Qr\Encoder\Codewords;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Encoder\Enums\Mode;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Encoder\Grid;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Encoder\ReedSolomon;
use ExeQue\Qr\Encoder\Segment;
use ExeQue\Qr\Encoder\Tables;
use ExeQue\Qr\Exceptions\ContentTooLongException;
use ExeQue\Qr\Exceptions\InvalidVersionException;

covers(
    Encoder::class,
    Grid::class,
    Matrix::class,
    Segment::class,
    Codewords::class,
    BitBuffer::class,
    ReedSolomon::class,
    Tables::class,
    ErrorCorrection::class,
    Mask::class,
    Mode::class,
    ContentTooLongException::class,
    InvalidVersionException::class,
);

pest()->group('encoding');

/**
 * The 15 format bits as written round the top-left finder, most significant first.
 */
function formatBits(Matrix $matrix): string
{
    $cells = [[0, 8], [1, 8], [2, 8], [3, 8], [4, 8], [5, 8], [7, 8], [8, 8], [8, 7], [8, 5], [8, 4], [8, 3], [8, 2], [8, 1], [8, 0]];

    return implode(array_map(fn (array $cell): string => $matrix->isDark(...$cell) ? '1' : '0', $cells));
}

describe('capacity', function (): void {
    it('picks the smallest version that holds the content', function (ErrorCorrection $level, int $version, string $content): void {
        $matrix = new Encoder()->encode($content, $level);

        expect($matrix->version)->toBe($version);

        if ($version < 40) {
            expect(new Encoder()->encode($content.$content[0], $level)->version)->toBe($version + 1);
        }
    })->with([
        // Capacities from ISO/IEC 18004:2015 table 7 (also Thonky's "Character Capacities" table).
        '1-L numeric' => [ErrorCorrection::Low, 1, str_repeat('1', 41)],
        '1-L alphanumeric' => [ErrorCorrection::Low, 1, str_repeat('A', 25)],
        '1-L byte' => [ErrorCorrection::Low, 1, str_repeat('a', 17)],
        '1-H numeric' => [ErrorCorrection::High, 1, str_repeat('1', 17)],
        '1-H byte' => [ErrorCorrection::High, 1, str_repeat('a', 7)],
        '10-M byte' => [ErrorCorrection::Medium, 10, str_repeat('a', 213)],
        '40-L numeric' => [ErrorCorrection::Low, 40, str_repeat('1', 7089)],
        '40-L alphanumeric' => [ErrorCorrection::Low, 40, str_repeat('A', 4296)],
        '40-L byte' => [ErrorCorrection::Low, 40, str_repeat('a', 2953)],
        '40-H byte' => [ErrorCorrection::High, 40, str_repeat('a', 1273)],
    ]);

    it('refuses content beyond version 40', function (): void {
        new Encoder()->encode(str_repeat('a', 1274), ErrorCorrection::High);
    })->throws(ContentTooLongException::class, 'The content needs 10212 bits, but version 40 holds 10208 at error correction High.');

    it('knows how much each level can restore', function (ErrorCorrection $level, float $ratio): void {
        expect($level->recoveryRatio())->toBe($ratio);
    })->with([
        [ErrorCorrection::Low, 0.07],
        [ErrorCorrection::Medium, 0.15],
        [ErrorCorrection::Quartile, 0.25],
        [ErrorCorrection::High, 0.30],
    ]);

    it('keeps a minimum version', function (): void {
        expect(new Encoder()->encode('hi', minVersion: 7)->version)->toBe(7);
    });

    it('refuses a version outside 1-40', function (int $version): void {
        new Encoder()->encode('hi', minVersion: $version);
    })->with([0, 41])->throws(InvalidVersionException::class);
});

describe('modes', function (): void {
    it('picks the most compact mode', function (string $content, Mode $mode): void {
        expect(Segment::for($content)->mode)->toBe($mode);
    })->with([
        ['', Mode::Numeric],
        ['0123', Mode::Numeric],
        ['HTTPS://EXAMPLE.COM/A-1', Mode::Alphanumeric],
        ['https://example.com', Mode::Byte],
        ["\xFF\xFE binary", Mode::Byte],
    ]);

    it('takes only digits as numeric, a trailing line break included', function (): void {
        expect(\ExeQue\Qr\Encoder\Segment::for("123\n")->mode)->toBe(\ExeQue\Qr\Encoder\Enums\Mode::Byte)
            ->and(\ExeQue\Qr\Encoder\Segment::for('123')->mode)->toBe(\ExeQue\Qr\Encoder\Enums\Mode::Numeric);
    });

});

describe('matrix', function (): void {
    it('uses a forced mask', function (Mask $mask): void {
        expect(new Encoder()->encode('hi', mask: $mask)->mask)->toBe($mask);
    })->with(Mask::cases());

    // Format information per level and mask, from Thonky's "Format and Version String Tables".
    it('writes the format information', function (ErrorCorrection $level, Mask $mask, string $bits): void {
        expect(formatBits(new Encoder()->encode('hi', $level, mask: $mask)))->toBe($bits);
    })->with([
        [ErrorCorrection::Low, Mask::Pattern0, '111011111000100'],
        [ErrorCorrection::Medium, Mask::Pattern0, '101010000010010'],
        [ErrorCorrection::Quartile, Mask::Pattern0, '011010101011111'],
        [ErrorCorrection::High, Mask::Pattern0, '001011010001001'],
    ]);

    it('writes the version information from version 7', function (int $version, int $bits): void {
        $matrix = new Encoder()->encode('hi', minVersion: $version);
        $read = 0;

        // The block beside the top-right finder, least significant bit first.
        for ($bit = 17; $bit >= 0; $bit--) {
            $read = ($read << 1) | ($matrix->isDark($matrix->size - 11 + $bit % 3, intdiv($bit, 3)) ? 1 : 0);
        }

        expect($read)->toBe($bits);
    })->with([
        // ISO/IEC 18004:2015 annex D table D.1.
        [7, 0x07C94],
        [8, 0x085BC],
        [40, 0x28C69],
    ]);

    it('gives every cell a role', function (): void {
        $matrix = new Encoder()->encode('hi', minVersion: 7);

        expect($matrix->role(0, 0))->toBe(Role::Finder)
            ->and($matrix->role(7, 0))->toBe(Role::Separator)
            ->and($matrix->role(10, 6))->toBe(Role::Timing)
            ->and($matrix->role(22, 22))->toBe(Role::Alignment)
            ->and($matrix->role(8, 0))->toBe(Role::Format)
            ->and($matrix->role(8, $matrix->size - 8))->toBe(Role::DarkModule)
            ->and($matrix->role($matrix->size - 11, 0))->toBe(Role::Version)
            ->and($matrix->role($matrix->size - 1, $matrix->size - 1))->toBe(Role::Data);
    });

    it('treats cells outside the symbol as light and without a role', function (): void {
        $matrix = new Encoder()->encode('hi');

        expect($matrix->isDark(-1, 0))->toBeFalse()
            ->and($matrix->isDark(0, $matrix->size))->toBeFalse()
            ->and($matrix->role(-1, 0))->toBeNull();
    });

    it('encodes the same content the same way every time', function (): void {
        expect(new Encoder()->encode('https://example.com')->toSnapshot())->toBe(new Encoder()->encode('https://example.com')->toSnapshot());
    });
});

it('snapshots the matrix', function (string $content, ErrorCorrection $level, int $minVersion, ?Mask $mask): void {
    expect(new Encoder()->encode($content, $level, $minVersion, $mask))->toMatchSnapshot();
})->with([
    'numeric-m' => ['01234567', ErrorCorrection::Medium, 1, null],
    'alphanumeric-q' => ['HELLO WORLD', ErrorCorrection::Quartile, 1, null],
    'url-m' => ['https://example.com/e/ABCD1234', ErrorCorrection::Medium, 1, null],
    'url-h-v7' => ['https://example.com/e/ABCD1234', ErrorCorrection::High, 7, null],
    'latin-l' => ['Smørrebrød', ErrorCorrection::Low, 1, null],
    'emoji-m' => ['Hello 👋', ErrorCorrection::Medium, 1, null],
    'alphanumeric-q-mask5' => ['HELLO WORLD', ErrorCorrection::Quartile, 1, Mask::Pattern5],
    'alphanumeric-l-v14-mask2' => ['HELLO WORLD', ErrorCorrection::Low, 14, Mask::Pattern2],
]);
