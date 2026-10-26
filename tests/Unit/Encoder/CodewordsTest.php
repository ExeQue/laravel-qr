<?php

declare(strict_types=1);

namespace Tests\Unit\Encoder;

use ExeQue\Qr\Encoder\BitBuffer;
use ExeQue\Qr\Encoder\Codewords;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Segment;

covers(Codewords::class, Segment::class, BitBuffer::class);

pest()->group('encoding');

describe('codewords', function (): void {
    // Hand-checked vectors: ISO/IEC 18004:2015 annex I and Thonky's tutorial.
    it('builds the data codewords', function (string $content, array $expected): void {
        expect(new Codewords()->data(Segment::for($content), 1, ErrorCorrection::Medium))->toBe($expected);
    })->with([
        'numeric' => ['01234567', [0x10, 0x20, 0x0C, 0x56, 0x61, 0x80, 0xEC, 0x11, 0xEC, 0x11, 0xEC, 0x11, 0xEC, 0x11, 0xEC, 0x11]],
        'alphanumeric' => ['HELLO WORLD', [0x20, 0x5B, 0x0B, 0x78, 0xD1, 0x72, 0xDC, 0x4D, 0x43, 0x40, 0xEC, 0x11, 0xEC, 0x11, 0xEC, 0x11]],
    ]);

    it('interleaves the blocks, data first, then error correction', function (): void {
        // 5-Q: two blocks of 15 data codewords and two of 16, each with 18 error correction codewords.
        $codewords = new Codewords()->for(Segment::for(str_repeat('A', 80)), 5, ErrorCorrection::Quartile);

        expect($codewords)->toHaveCount(134);
    });

});
