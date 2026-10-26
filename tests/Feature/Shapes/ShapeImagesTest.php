<?php

declare(strict_types=1);

namespace Tests\Feature\Shapes;

use BackedEnum;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;

covers(Module::class, OuterEye::class, InnerEye::class, Eye::class);

pest()->group('shapes');

/**
 * @return iterable<string, array{string, BackedEnum}>
 */
function shapeImages(): iterable
{
    foreach (['modules' => Module::cases(), 'outer' => OuterEye::cases(), 'inner' => InnerEye::cases(), 'eyes' => Eye::cases()] as $method => $cases) {
        foreach ($cases as $case) {
            yield "{$method} {$case->value}" => [$method, $case];
        }
    }
}

describe('shape images', function (): void {
    it('looks as it did', function (string $method, BackedEnum $shape): void {
        expect(Qr::make('https://example.com/e/ABCD1234')->{$method}($shape)->png(600)->contents())
            ->toMatchImageSnapshot("shapes/{$method}-{$shape->value}");
    })->with(shapeImages(...))->group('visual');

});
