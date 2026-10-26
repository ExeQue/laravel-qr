<?php

declare(strict_types=1);

namespace Tests\Unit\Encoder;

use ExeQue\Qr\Encoder\Grid;
use ExeQue\Qr\Encoder\Tables;

covers(Grid::class, Tables::class);

pest()->group('encoding');

describe('the grid', function (): void {
    it('leaves room for exactly the version\'s codewords and its remainder bits', function (int $version): void {
        // Remainder bits per version, ISO/IEC 18004:2015 table 1.
        $remainder = match (true) {
            $version === 1 => 0,
            $version <= 6 => 7,
            $version <= 13 => 0,
            $version <= 20 => 3,
            $version <= 27 => 4,
            $version <= 34 => 3,
            default => 0,
        };

        $modules = new Grid($version)->dataModules();

        expect(intdiv($modules, 8))->toBe(Tables::totalCodewords($version))
            ->and($modules % 8)->toBe($remainder);
    })->with(range(1, 40));

});
