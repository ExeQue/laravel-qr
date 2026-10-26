<?php

declare(strict_types=1);

namespace Tests\Feature;

use ExeQue\Qr\Logos\Contracts\SvgSanitizer;
use ExeQue\Qr\Logos\Sanitizers\AutoSvgSanitizer;
use ExeQue\Qr\Logos\Sanitizers\BasicSvgSanitizer;
use ExeQue\Qr\QrServiceProvider;

covers(QrServiceProvider::class);

pest()->group('logos');

it('picks a sanitizer by default', function (): void {
    expect(app(SvgSanitizer::class))->toBeInstanceOf(AutoSvgSanitizer::class);
});

it('takes the sanitizer from the config', function (): void {
    config()->set('qr.logos.sanitizer', BasicSvgSanitizer::class);

    expect(app(SvgSanitizer::class))->toBeInstanceOf(BasicSvgSanitizer::class);
});
