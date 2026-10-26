<?php

declare(strict_types=1);

namespace Tests\Unit\Decoding;

use ExeQue\Qr\Decoding\Decoded;
use ExeQue\Qr\Decoding\Native\SymbolReader;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Encoder\Grid;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Encoder\ReedSolomon;
use ExeQue\Qr\Exceptions\UnreadableCodeException;

covers(SymbolReader::class, ReedSolomon::class, Grid::class, Decoded::class, UnreadableCodeException::class);

pest()->group('decoding');

/**
 * @return list<list<bool>>
 */
function modulesOf(Matrix $matrix): array
{
    $rows = [];

    for ($y = 0; $y < $matrix->size; $y++) {
        $row = [];

        for ($x = 0; $x < $matrix->size; $x++) {
            $row[] = $matrix->isDark($x, $y);
        }

        $rows[] = $row;
    }

    return $rows;
}

describe('reading', function (): void {
    it('reads every version at every level', function (int $version): void {
        foreach (ErrorCorrection::cases() as $level) {
            $text = substr(str_repeat('Version '.$version.' at level '.$level->value.'. ', 200), 0, max(1, intdiv(\ExeQue\Qr\Encoder\Tables::dataCodewords($version, $level), 2)));
            $matrix = new Encoder()->encode($text, $level, minVersion: $version);
            $decoded = new SymbolReader()->read(modulesOf($matrix));

            expect($decoded->text)->toBe($text)
                ->and($decoded->version)->toBe($matrix->version)
                ->and($decoded->errorCorrection)->toBe($level)
                ->and($decoded->mask)->toBe($matrix->mask);
        }
    })->with(range(1, 40));

    it('needs a square of a size symbols have', function (int $size): void {
        new SymbolReader()->read(array_fill(0, $size, array_fill(0, $size, false)));
    })->with([20, 22, 181])->throws(UnreadableCodeException::class, 'No QR code');

    it('says what it read', function (): void {
        $decoded = new Decoded('hello', 1, ErrorCorrection::Low, Mask::Pattern0);

        expect((string) $decoded)->toBe('hello')
            ->and($decoded->withDecoder('native')->decoder)->toBe('native')
            ->and($decoded->decoder)->toBeNull();
    });
});

describe('error correction', function (): void {
    it('puts right what its error correction can', function (): void {
        $matrix = new Encoder()->encode('https://example.com/e/ABCD1234', ErrorCorrection::High);
        $modules = modulesOf($matrix);

        // A smudge across the middle.
        for ($y = 10; $y < 14; $y++) {
            for ($x = 9; $x < 20; $x++) {
                $modules[$y][$x] = true;
            }
        }

        expect(new SymbolReader()->read($modules)->text)->toBe('https://example.com/e/ABCD1234');
    });

    it('gives up on a symbol too damaged to put right', function (): void {
        $modules = modulesOf(new Encoder()->encode('https://example.com/e/ABCD1234', ErrorCorrection::Low));

        for ($y = 9; $y < 25; $y++) {
            for ($x = 9; $x < 25; $x++) {
                $modules[$y][$x] = ($x * 7 + $y * 3) % 5 === 0;
            }
        }

        new SymbolReader()->read($modules);
    })->throws(UnreadableCodeException::class, 'too damaged');

    it('repairs blocks up to half their error correction, and no further', function (): void {
        $rs = new ReedSolomon();
        mt_srand(7);

        for ($trial = 0; $trial < 50; $trial++) {
            $data = array_map(fn (): int => mt_rand(0, 255), range(1, 30));
            $block = [...$data, ...$rs->remainder($data, 16)];
            $broken = $block;

            foreach ((array) array_rand($block, mt_rand(1, 8)) as $position) {
                $broken[$position] ^= mt_rand(1, 255);
            }

            expect($rs->correct($broken, 16))->toBe($block);
        }

        $broken = $block;

        foreach (range(0, 17) as $position) {
            $broken[$position * 2] ^= 0x5A;
        }

        expect(fn () => $rs->correct($broken, 16))->toThrow(UnreadableCodeException::class);
    });

    it('keeps back the codewords the smallest symbols protect', function (): void {
        $rs = new ReedSolomon();
        $data = range(1, 19);
        $block = [...$data, ...$rs->remainder($data, 7)];
        $broken = $block;

        foreach ([0, 5, 9] as $position) {
            $broken[$position] ^= 0x33;
        }

        expect($rs->correct($broken, 7))->toBe($block)
            ->and(fn () => $rs->correct($broken, 7, 3))->toThrow(UnreadableCodeException::class, 'too damaged');
    });

});

describe('format and version information', function (): void {
    it('reads the version information of large symbols', function (): void {
        $matrix = new Encoder()->encode(str_repeat('x', 200), ErrorCorrection::Low);
        $modules = modulesOf($matrix);

        expect(SymbolReader::versionInformation($modules))->toBe($matrix->version)
            ->and(SymbolReader::versionInformation(modulesOf(new Encoder()->encode('small'))))->toBeNull();

        // Worn past telling in both copies.
        foreach (new Grid($matrix->version)->versionCells() as $bit => [[$x1, $y1], [$x2, $y2]]) {
            $modules[$y1][$x1] = $modules[$y2][$x2] = in_array($bit, [0, 1, 2, 3, 4, 5, 6, 7, 8], true) !== ((Grid::versionWord($matrix->version) >> $bit) & 1) > 0;
        }

        expect(SymbolReader::versionInformation($modules))->not->toBe($matrix->version)
            ->and(new SymbolReader()->read($modules)->text)->toBe(str_repeat('x', 200));
    });

    it('needs format information it can tell', function (): void {
        $modules = modulesOf(new Encoder()->encode('hello'));

        // A word at least four bits from every valid one.
        $far = 0;

        while (array_any(ErrorCorrection::cases(), fn (ErrorCorrection $level): bool => array_any(Mask::cases(), fn (Mask $mask): bool => substr_count(decbin(Grid::formatWord($level, $mask) ^ $far), '1') < 4))) {
            $far++;
        }

        foreach (new Grid(1)->formatCells() as $bit => [[$x1, $y1], [$x2, $y2]]) {
            $modules[$y1][$x1] = $modules[$y2][$x2] = (($far >> $bit) & 1) === 1;
        }

        new SymbolReader()->read($modules);
    })->throws(UnreadableCodeException::class, 'format information');

    it('writes the format and version words of the standard', function (): void {
        // ISO/IEC 18004:2015 annex C: level M, mask 5; and version 7.
        expect(Grid::formatWord(ErrorCorrection::Medium, Mask::Pattern5))->toBe(0b100000011001110)
            ->and(Grid::versionWord(7))->toBe(0b000111110010010100);
    });
});
