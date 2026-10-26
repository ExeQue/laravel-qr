<?php

declare(strict_types=1);

namespace Tests\Unit\Decoding;

use ExeQue\Qr\Decoding\Native\Perspective;
use ExeQue\Qr\Exceptions\UnreadableCodeException;

covers(Perspective::class);

pest()->group('decoding');

const UNIT_SQUARE = [[0.0, 0.0], [1.0, 0.0], [1.0, 1.0], [0.0, 1.0]];

describe('perspective', function (): void {
    it('maps corners onto corners', function (): void {
        $to = [[10.0, 20.0], [110.0, 30.0], [100.0, 140.0], [5.0, 120.0]];
        $perspective = Perspective::between(UNIT_SQUARE, $to);

        foreach (UNIT_SQUARE as $index => [$x, $y]) {
            expect($perspective->map($x, $y))->toEqualWithDelta($to[$index], 1e-9);
        }
    });

    it('puts points at infinity off the image', function (): void {
        // For this quadrilateral the line x + y = 3 goes to infinity.
        expect(Perspective::between(UNIT_SQUARE, [[0.0, 0.0], [1.0, 0.0], [2.0, 2.0], [0.0, 1.0]])->map(3, 0))->toBe([-1.0, -1.0]);
    });

    it('refuses corners in a line', function (): void {
        Perspective::between(UNIT_SQUARE, [[0.0, 0.0], [1.0, 1.0], [2.0, 2.0], [3.0, 3.0]]);
    })->throws(UnreadableCodeException::class);

});
