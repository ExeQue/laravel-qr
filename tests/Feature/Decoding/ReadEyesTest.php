<?php

declare(strict_types=1);

namespace Tests\Feature\Decoding;

use BackedEnum;
use ExeQue\Qr\Decoding\Native\Bitmap;
use ExeQue\Qr\Decoding\Native\FinderLocator;
use ExeQue\Qr\Decoding\Native\Perspective;
use ExeQue\Qr\Decoding\Native\Sampler;
use ExeQue\Qr\Decoding\Native\SymbolReader;
use ExeQue\Qr\Decoding\NativeDecoder;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\OuterEye;

covers(NativeDecoder::class, Bitmap::class, FinderLocator::class, Sampler::class, Perspective::class, SymbolReader::class);

pest()->group('decoding');

// Apart from ReadTest, so shards and parallel workers share the reading out.
describe('reading eyes', function (): void {
    it('reads every built-in eye', function (string $method, BackedEnum $shape): void {
        $decoded = new NativeDecoder()->decode(Qr::make('https://example.com/e/ABCD1234')->{$method}($shape)->png(600)->contents());

        expect($decoded->text)->toBe('https://example.com/e/ABCD1234')
            ->and($decoded->version)->toBe(3);
    })->with(function (): iterable {
        foreach (['outer' => OuterEye::cases(), 'inner' => InnerEye::cases(), 'eyes' => Eye::cases()] as $method => $cases) {
            foreach ($cases as $case) {
                yield "{$method} {$case->value}" => [$method, $case];
            }
        }
    });
});
