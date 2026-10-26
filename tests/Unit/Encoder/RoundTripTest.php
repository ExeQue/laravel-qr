<?php

declare(strict_types=1);

namespace Tests\Unit\Encoder;

use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mode;
use ExeQue\Qr\Encoder\Tables;

covers(Encoder::class);

pest()->group('encoding');

/**
 * The longest content of `$mode` that fits `$version` - every block full.
 */
function fullContent(Mode $mode, int $version, ErrorCorrection $level): string
{
    $bits = Tables::dataCodewords($version, $level) * 8 - 4 - $mode->countBits($version);
    $rest = $bits % match ($mode) {
        Mode::Numeric => 10,
        Mode::Alphanumeric => 11,
        Mode::Byte => 8,
    };

    // Numeric packs three digits in 10 bits, alphanumeric two characters in 11, byte one in 8;
    // a last group of fewer characters takes fewer bits.
    $characters = match ($mode) {
        Mode::Numeric => intdiv($bits, 10) * 3 + ($rest >= 7 ? 2 : ($rest >= 4 ? 1 : 0)),
        Mode::Alphanumeric => intdiv($bits, 11) * 2 + ($rest >= 6 ? 1 : 0),
        Mode::Byte => intdiv($bits, 8),
    };

    $alphabet = match ($mode) {
        Mode::Numeric => '0123456789',
        Mode::Alphanumeric => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:',
        Mode::Byte => 'abcdefghijklmnopqrstuvwxyz0123456789',
    };

    // Stepping through the alphabet keeps the data from being one long run.
    $content = '';

    for ($index = 0; $index < $characters; $index++) {
        $content .= $alphabet[($index * 7 + $version) % strlen($alphabet)];
    }

    return $content;
}

dataset('versions', range(1, 40));
dataset('levels', ErrorCorrection::cases());
dataset('modes', Mode::cases());

describe('round trips', function (): void {
    it('decodes at every version, level and mode', function (int $version, ErrorCorrection $level, Mode $mode): void {
        $content = fullContent($mode, $version, $level);
        $matrix = new Encoder()->encode($content, $level);

        expect($matrix->version)->toBe($version)
            ->and($matrix)->toDecodeAs($content);
    })->with('versions')->with('levels')->with('modes')->group('scan');

    it('decodes text beyond ASCII', function (string $content): void {
        expect(new Encoder()->encode($content))->toDecodeAs($content);
    })->with([
        'within ISO-8859-1' => 'Smørrebrød på Ærø',
        'beyond ISO-8859-1' => 'Hello 👋 こんにちは',
    ])->group('scan');

});
