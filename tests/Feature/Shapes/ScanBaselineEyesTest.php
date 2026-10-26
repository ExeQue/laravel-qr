<?php

declare(strict_types=1);

namespace Tests\Feature\Shapes;

use BackedEnum;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;

covers(Module::class, OuterEye::class, InnerEye::class, Eye::class);

pest()->group('shapes');

// One file per kind of shape, so shards and parallel workers share the scans out.
describe('scan baselines', function (): void {
    it('scans every built-in shape as its baseline says', function (string $kind, BackedEnum $shape, bool $mirrored): void {
        expectScansAsBaselineSays($kind, $shape, $mirrored);
    })->with(scanCases('eyes'))->group('scan');
});
