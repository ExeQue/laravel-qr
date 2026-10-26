<?php

declare(strict_types=1);

namespace Tests\Unit\Decoding;

use ExeQue\Qr\Decoding\Native\SegmentParser;
use ExeQue\Qr\Exceptions\UnreadableCodeException;

covers(SegmentParser::class, UnreadableCodeException::class);

pest()->group('decoding');

/**
 * Codewords from a bit string, padded with zeros to whole bytes.
 *
 * @return list<int>
 */
function codewords(string $bits): array
{
    $bits = str_pad($bits, (int) ceil(strlen($bits) / 8) * 8, '0');

    return array_map(fn (string $byte): int => (int) bindec($byte), str_split($bits, 8));
}

function bitsOf(int $value, int $length): string
{
    return str_pad(decbin($value), $length, '0', STR_PAD_LEFT);
}

describe('segments', function (): void {
    it('reads every kind of segment', function (string $bits, string $text, int $version = 1): void {
        expect(new SegmentParser()->parse(codewords($bits), $version))->toBe($text);
    })->with([
        'numeric' => ['0001'.bitsOf(8, 10).bitsOf(12, 10).bitsOf(345, 10).bitsOf(67, 7), '01234567'],
        'numeric, one left' => ['0001'.bitsOf(4, 10).bitsOf(123, 10).bitsOf(4, 4), '1234'],
        'alphanumeric' => ['0010'.bitsOf(5, 9).bitsOf(17 * 45 + 14, 11).bitsOf(21 * 45 + 21, 11).bitsOf(24, 6), 'HELLO'],
        'bytes as UTF-8' => ['0100'.bitsOf(2, 8).bitsOf(0xC3, 8).bitsOf(0xA6, 8), 'æ'],
        'bytes as Latin-1' => ['0100'.bitsOf(1, 8).bitsOf(0xE6, 8), 'æ'],
        'ECI Latin-1, one byte' => ['0111'.bitsOf(3, 8).'0100'.bitsOf(1, 8).bitsOf(0xE6, 8), 'æ'],
        'ECI, two bytes' => ['0111'.'10'.bitsOf(26, 14).'0100'.bitsOf(1, 8).bitsOf(0x41, 8), 'A'],
        'ECI, three bytes' => ['0111'.'110'.bitsOf(26, 21).'0100'.bitsOf(1, 8).bitsOf(0x41, 8), 'A'],
        'kanji' => ['1000'.bitsOf(1, 8).bitsOf(0x0D9F, 13), '点'],
        'kanji from the upper range' => ['1000'.bitsOf(1, 8).bitsOf(0x1AAA, 13), mb_convert_encoding("\xE4\xAA", 'UTF-8', 'SJIS')],
        'structured append, FNC1 first and second' => ['0011'.bitsOf(0, 16).'0101'.'1001'.bitsOf(0, 8).'0100'.bitsOf(1, 8).bitsOf(0x41, 8), 'A'],
        'a terminator ends it' => ['0100'.bitsOf(1, 8).bitsOf(0x41, 8).'0000'.'0100', 'A'],
        'a character split over two byte segments' => ['0100'.bitsOf(1, 8).bitsOf(0xC3, 8).'0100'.bitsOf(1, 8).bitsOf(0xA6, 8), 'æ'],
        'GS1 field separators' => ['0101'.'0010'.bitsOf(5, 9).bitsOf(1 * 45 + 38, 11).bitsOf(38 * 45 + 38, 11).bitsOf(2, 6), "1%\x1D2"],
        'GS1, application named' => ['1001'.bitsOf(0, 8).'0010'.bitsOf(1, 9).bitsOf(38, 6), "\x1D"],
        'ECI ASCII' => ['0111'.'10'.bitsOf(170, 14).'0100'.bitsOf(1, 8).bitsOf(0x41, 8), 'A'],
        'counts grow with the version' => ['0100'.bitsOf(1, 16).bitsOf(0x41, 8), 'A', 10],
        'the largest versions' => ['1000'.bitsOf(0, 12), '', 27],
    ]);

    it('refuses what is not a code\'s content', function (string $bits, string $message): void {
        expect(fn () => new SegmentParser()->parse(codewords($bits), 1))->toThrow(UnreadableCodeException::class, $message);
    })->with([
        'an unknown mode' => ['1111', 'an unknown mode'],
        'an unknown character set' => ['0111'.bitsOf(99, 8), 'character set that is not supported'],
        'a number out of range' => ['0001'.bitsOf(3, 10).bitsOf(1000, 10), 'a number is out of range'],
        'a pair out of range' => ['0010'.bitsOf(2, 9).bitsOf(2047, 11), 'a character is out of range'],
        'a single character out of range' => ['0010'.bitsOf(1, 9).bitsOf(63, 6), 'a character is out of range'],
        'a count beyond the data' => ['0100'.bitsOf(9, 8).bitsOf(0x41, 8), 'runs past the end'],
    ]);

});
